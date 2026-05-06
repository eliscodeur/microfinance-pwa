<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;
    protected $fillable = [
       'user_id', 'code_agent', 'nom', 'telephone', 'image', 'actif', 'can_sync', 'portefeuille_virtuel'
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function clients(): HasMany
    {
        // Vérifie bien que ta table 'clients' possède une colonne 'agent_id'
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

    /**
     * Calcule les commissions automatiques basées sur les cycles terminés.
     * Pour chaque cycle terminé, l'agent reçoit 5% de la somme des collectes.
     */
    public function calculateAutomaticCommissions()
    {
        $totalCommission = 0;

        // Récupérer tous les cycles terminés de l'agent via ses clients
        $cyclesTermines = Cycle::whereHas('carnet.client', function ($query) {
            $query->where('agent_id', $this->id);
        })->where('statut', 'termine')->get();

        foreach ($cyclesTermines as $cycle) {
            $totalCollectes = $cycle->collectes()->sum('montant');
            $commission = $totalCollectes * 0.05; // 5%
            $totalCommission += $commission;
        }

        return $totalCommission;
    }

    /**
     * Vérifie si l'agent dépasse le plafond de caisse (ex: 1 000 000 FCFA non reversé).
     */
    public function checkPlafondCaisse($plafond = 1000000)
    {
        $totalNonReversed = $this->portefeuille_virtuel; // Supposons que c'est le montant non reversé
        return $totalNonReversed > $plafond;
    }
}
