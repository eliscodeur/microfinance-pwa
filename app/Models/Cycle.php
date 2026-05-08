<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Retrait;
use Illuminate\Database\Eloquent\Builder;

class Cycle extends Model
{
    use HasFactory;
    protected $fillable = [
        'cycle_uid',
        'carnet_id',
        'agent_id',
        'client_id',
        'montant_journalier',
        'nombre_jours_objectif',
        'statut',
        'commission_genere',
        'date_debut',
        'date_fin_prevue',
        'completed_at',
        'retire_at'
    ];

    protected $casts = [
        'date_debut' => 'date:Y-m-d',
        'date_fin_prevue' => 'date:Y-m-d',
        'completed_at' => 'datetime',
        'retire_at' => 'datetime',
    ];

    public function carnet() {
        return $this->belongsTo(Carnet::class);
    }

    public function agent() {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function collectes()
    {
        return $this->hasMany(Collecte::class);
    }

    public function retraits()
    {
        return $this->hasMany(Retrait::class, 'cycle_id');
    }

    public function scopeVisibleForAgentSync(Builder $query): Builder
    {
        return $query->where('statut', 'en_cours');
    }
    
    public function getSoldeBrutRestantAttribute()
    {
        $totalCollecte = $this->collectes()->sum('montant');
        $totalRetire = $this->retraits()->sum('montant_net');
        
        return $totalCollecte - $totalRetire;
    }

    /**
     * Calcul du solde net (après déduction de la commission/mise journalière)
     */
    public function getSoldeNetRestantAttribute()
    {
        $commission = $this->montant_journalier ?? 0;
        return $this->solde_brut_restant - $commission;
    }

}
