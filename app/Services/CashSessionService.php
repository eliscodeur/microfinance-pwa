<?php
namespace App\Services;

use App\Models\CashSession;
use App\Models\MouvementCaisse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashSessionService
{
    public function openSession(int $userId, float $fondInitial): CashSession
    {
        return DB::transaction(function () use ($userId, $fondInitial) {
            User::whereKey($userId)->lockForUpdate()->firstOrFail();

            if (CashSession::where('user_id', $userId)->where('statut', 'ouverte')->exists()) {
                throw new \Exception("Vous possédez déjà une session de caisse active.");
            }

            return CashSession::create([
                'ulid'                => strtolower((string) Str::ulid()),
                'user_id'             => $userId,
                'fond_caisse_initial' => $fondInitial,
                'date_ouverture'      => now(),
                'statut'              => 'ouverte',
            ]);
        });
    }

    public function hasActiveSession(int $userId): bool
    {
        return CashSession::where('user_id', $userId)
            ->where('statut', 'ouverte')
            ->exists();
    }

    public function replenishCash(int $userId, int $amount, string $origin, string $reason, ?string $reference = null): MouvementCaisse
    {
        return DB::transaction(function () use ($userId, $amount, $origin, $reason, $reference) {
            $session = CashSession::where('user_id', $userId)
                ->where('statut', 'ouverte')
                ->lockForUpdate()
                ->first();

            if (! $session) {
                throw new \DomainException('Aucune session de caisse ouverte. Ouvrez une caisse avant de l’approvisionner.');
            }

            return MouvementCaisse::create([
                'user_id' => $userId,
                'cash_session_id' => $session->id,
                'date_mouvement' => now(),
                'type_operation' => 'approvisionnement_caisse',
                'sens' => 'entree',
                'montant' => $amount,
                'mode_paiement' => 'especes',
                'reference' => $reference ?: 'APP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
                'libelle' => 'Approvisionnement depuis ' . $origin . ' — ' . $reason,
            ]);
        });
    }
}
