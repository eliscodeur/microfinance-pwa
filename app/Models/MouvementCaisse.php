<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MouvementCaisse extends Model
{
    use HasFactory;

    protected $table = 'mouvements_caisses';

    protected $fillable = [
        'ulid',
        'date_mouvement',
        'type_operation',
        'sens',
        'montant',
        'mode_paiement',
        'reference',
        'libelle',
        'source_type',
        'source_id',
        'user_id',
        'cash_session_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = strtolower((string) Str::ulid());
            }

            if (empty($model->cash_session_id) && ! empty($model->user_id)) {
                $model->cash_session_id = CashSession::where('user_id', $model->user_id)
                    ->where('statut', 'ouverte')
                    ->latest('date_ouverture')
                    ->value('id');
            }

            if ($model->sens === 'sortie' && static::isCashPayment($model->mode_paiement)) {
                DB::transaction(function () use ($model) {
                    $session = CashSession::whereKey($model->cash_session_id)
                        ->where('statut', 'ouverte')
                        ->lockForUpdate()
                        ->first();

                    if (! $session) {
                        throw new \DomainException('Aucune session de caisse ouverte ne peut financer ce décaissement en espèces.');
                    }

                    app(\App\Services\CashClosingService::class)
                        ->assertSufficientCash($session, (float) $model->montant);
                });
            }
        });
    }

    public static function isCashPayment(?string $paymentMode): bool
    {
        $normalized = strtr(mb_strtolower(trim((string) $paymentMode)), ['é' => 'e', 'è' => 'e']);

        return in_array($normalized, ['especes', 'espece'], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }
}
