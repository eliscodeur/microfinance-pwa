<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'cycle_id',
        'paiement_id', // Ajout nécessaire
        'montant',
        'statut',      // Ajout nécessaire (en_attente, valide, refuse)
        'motif',
        'commission_genere',
        'admin_id',
        'validated_at',
        'validated_by',
        'date_attribution',
    ];
    
    protected $casts = [
        'montant' => 'decimal:2',
        'date_attribution' => 'date:Y-m-d', // 👈 Force le format texte standard pour le JSON
        'validated_at' => 'datetime:Y-m-d H:i:s', // 👈 Idem pour le datetime
    ];

    // --- RELATIONS ---

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function validator() // Celui qui a validé/refusé
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    // --- SCOPES DE FILTRAGE ---
    /**
     * Filtrer uniquement les commissions (liées à un cycle spécifique)
     */
    public function scopeCommissions($query)
    {
        // Une commission est obligatoirement rattachée à un cycle de tontine
        return $query->whereNotNull('cycle_id');
    }

    /**
     * Filtrer uniquement les bonus manuels (non liés à un cycle)
     */
    public function scopeManuels($query)
    {
        // Un bonus manuel est une gratification indépendante des cycles de collecte
        return $query->whereNull('cycle_id');
    }
    /**
     * Filtrer les bonus par statut (ex: $bonus->status('en_attente'))
     */
    public function scopeStatus($query, $statut)
    {
        return $query->where('statut', $statut);
    }
}