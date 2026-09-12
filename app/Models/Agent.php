<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ulid',
        'user_id',
        'code_agent',
        'pin_hash',
        'nom',
        'telephone',
        'image',
        'actif',
        'can_sync',
        'portefeuille_virtuel',
    ];

    protected $casts = [
        'actif'                => 'boolean',
        'can_sync'             => 'boolean',
        'portefeuille_virtuel' => 'float',
    ];

    /**
     * Génère l'ULID dans la colonne 'ulid' lors de la création.
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Utilise l'ULID pour la résolution des URLs et API routes.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function syncBatches(): HasMany
    {
        return $this->hasMany(SyncBatch::class);
    }

    public function bonuses(): HasMany
    {
        return $this->hasMany(Bonus::class);
    }

    /* -------------------------------------------------------------------------- */
    /* LOGIQUE MÉTIER                                                             */
    /* -------------------------------------------------------------------------- */

    /**
     * Calcule les commissions automatiques basées sur les cycles terminés.
     * Pour chaque cycle terminé, l'agent reçoit 5% de la somme des collectes.
     */
    public function calculateAutomaticCommissions(): float
    {
        $totalCommission = 0;

        $cyclesTermines = Cycle::whereHas('carnet.client', function ($query) {
            $query->where('agent_id', $this->id);
        })->where('statut', 'termine')->get();

        foreach ($cyclesTermines as $cycle) {
            $totalCollectes   = $cycle->collectes()->sum('montant');
            $totalCommission += $totalCollectes * 0.05;
        }

        return $totalCommission;
    }

    /**
     * Vérifie si l'agent dépasse le plafond de caisse (ex: 1 000 000 FCFA non reversé).
     */
    public function checkPlafondCaisse(float $plafond = 1000000): bool
    {
        return $this->portefeuille_virtuel > $plafond;
    }

    /**
     * Génère un code agent unique au format NEC-00001.
     */
    public static function generateNecCode(): string
    {
        $count = self::count() + 1;
        return 'NEC-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
