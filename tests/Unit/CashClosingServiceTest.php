<?php

namespace Tests\Unit;

use App\Models\CashSession;
use App\Models\MouvementCaisse;
use App\Services\CashClosingService;
use App\Services\CashExpenseGuard;
use App\Services\CashOperationAuditService;
use App\Services\CashSessionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashClosingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'cash_test',
            'database.connections.cash_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('cash_test');

        Schema::connection('cash_test')->create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('fond_caisse_initial', 15, 2);
            $table->dateTime('date_ouverture');
            $table->dateTime('date_fermeture')->nullable();
            $table->string('statut');
            $table->timestamps();
        });

        Schema::connection('cash_test')->create('mouvements_caisses', function (Blueprint $table) {
            $table->id();
            $table->string('ulid')->nullable();
            $table->dateTime('date_mouvement')->nullable();
            $table->string('type_operation')->nullable();
            $table->string('sens')->nullable();
            $table->decimal('montant', 15, 2);
            $table->string('mode_paiement')->default('especes');
            $table->string('reference')->nullable();
            $table->text('libelle')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cash_session_id')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();
        });

        Schema::connection('cash_test')->create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('user_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('cash_test');
        parent::tearDown();
    }

    public function test_session_summary_reconciles_only_its_cash_movements(): void
    {
        $sessionId = $this->createSession(10, 100);
        $otherSessionId = $this->createSession(20, 500);

        $this->createMovement($sessionId, 'entree', 70, 'especes', 10);
        $this->createMovement($sessionId, 'sortie', 30, 'especes', 10);
        $this->createMovement($sessionId, 'entree', 400, 'mobile_money', 10);
        $this->createMovement($sessionId, 'sortie', 200, 'Virement Bancaire', 10);
        $this->createMovement($otherSessionId, 'entree', 999, 'especes', 20);

        $summary = (new CashClosingService())->calculateSummary(CashSession::findOrFail($sessionId));

        $this->assertSame(70.0, (float) $summary['total_entrees']);
        $this->assertSame(30.0, (float) $summary['total_sorties']);
        $this->assertSame(400.0, (float) $summary['total_entrees_hors_especes']);
        $this->assertSame(200.0, (float) $summary['total_sorties_hors_especes']);
        $this->assertSame(140.0, (float) $summary['solde_theorique']);
    }

    public function test_new_movement_is_attached_to_the_users_open_session(): void
    {
        $sessionId = $this->createSession(30, 250);

        $movement = MouvementCaisse::create([
            'user_id' => 30,
            'date_mouvement' => now(),
            'type_operation' => 'depot_epargne',
            'sens' => 'entree',
            'montant' => 50,
            'mode_paiement' => 'especes',
        ]);

        $this->assertSame($sessionId, (int) $movement->cash_session_id);
    }

    public function test_closing_requires_a_reason_when_the_physical_balance_differs(): void
    {
        $sessionId = $this->createSession(40, 100);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Une justification est obligatoire');

        (new CashClosingService())->closeSession(CashSession::findOrFail($sessionId), 90);
    }

    public function test_cash_disbursement_cannot_make_the_session_balance_negative(): void
    {
        $sessionId = $this->createSession(50, 100);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Fonds espèces insuffisants');

        MouvementCaisse::create([
            'user_id' => 50,
            'cash_session_id' => $sessionId,
            'date_mouvement' => now(),
            'type_operation' => 'retrait_epargne',
            'sens' => 'sortie',
            'montant' => 101,
            'mode_paiement' => 'Espèces',
        ]);
    }

    public function test_expense_guard_rejects_cash_payment_without_sufficient_funds(): void
    {
        $this->createSession(55, 100);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Fonds espèces insuffisants');

        (new CashExpenseGuard())->assertCanBeRecorded(55, 'Espèces', 101);
    }

    public function test_expense_guard_returns_open_session_for_funded_cash_payment(): void
    {
        $sessionId = $this->createSession(57, 100);

        $session = (new CashExpenseGuard())->assertCanBeRecorded(57, 'Espèces', 75);

        $this->assertSame($sessionId, $session->id);
    }

    public function test_expense_guard_does_not_require_cash_session_for_bank_payment(): void
    {
        $session = (new CashExpenseGuard())->assertCanBeRecorded(56, 'Virement Bancaire', 100);

        $this->assertNull($session);
        $this->assertSame(0, DB::table('cash_sessions')->count());
    }

    public function test_replenishment_increases_the_cash_available_for_a_later_disbursement(): void
    {
        $sessionId = $this->createSession(60, 100);
        $service = new CashSessionService();

        $movement = $service->replenishCash(60, 50, 'coffre principal', 'réapprovisionnement de caisse');
        $this->assertSame('approvisionnement_caisse', $movement->type_operation);
        $this->assertSame($sessionId, (int) $movement->cash_session_id);

        $disbursement = MouvementCaisse::create([
            'user_id' => 60,
            'cash_session_id' => $sessionId,
            'date_mouvement' => now(),
            'type_operation' => 'retrait_epargne',
            'sens' => 'sortie',
            'montant' => 140,
            'mode_paiement' => 'especes',
        ]);

        $this->assertSame(140.0, (float) $disbursement->montant);
        $this->assertSame(10.0, (float) (new CashClosingService())->calculateSummary(CashSession::findOrFail($sessionId))['solde_theorique']);
    }

    public function test_rejected_expense_is_recorded_in_audit_after_the_operation_rolls_back(): void
    {
        $sessionId = $this->createSession(70, 100);

        try {
            DB::transaction(function () {
                throw new \DomainException('Fonds espèces insuffisants.');
            });
        } catch (\DomainException $exception) {
            (new CashOperationAuditService())->recordRejectedExpense(
                70,
                $sessionId,
                ['montant' => 500, 'mode_paiement' => 'Espèces', 'motif' => 'Frais de transport'],
                $exception,
                'http://localhost/admin/depenses',
                '127.0.0.1',
                'Unit test'
            );
        }

        $audit = DB::table('audits')->first();

        $this->assertNotNull($audit);
        $this->assertSame('cash_expense_rejected', $audit->event);
        $this->assertSame(CashSession::class, $audit->auditable_type);
        $this->assertSame($sessionId, (int) $audit->auditable_id);
        $this->assertSame(500, json_decode($audit->new_values, true)['montant']);
        $this->assertSame('rejected', json_decode($audit->new_values, true)['result']);
    }

    private function createSession(int $userId, int $openingFund): int
    {
        return DB::table('cash_sessions')->insertGetId([
            'user_id' => $userId,
            'fond_caisse_initial' => $openingFund,
            'date_ouverture' => now()->subHour(),
            'statut' => 'ouverte',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createMovement(int $sessionId, string $direction, int $amount, string $paymentMode, int $userId): void
    {
        DB::table('mouvements_caisses')->insert([
            'cash_session_id' => $sessionId,
            'user_id' => $userId,
            'date_mouvement' => now(),
            'type_operation' => 'test',
            'sens' => $direction,
            'montant' => $amount,
            'mode_paiement' => $paymentMode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
