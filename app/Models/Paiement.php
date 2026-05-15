<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id', 
        'montant_total', 
        'type',        // 'deboursement' ou 'rejet'
        'reference', 
        'validated_by' // ID de l'admin qui a validé
    ];

    /**
     * Cast des attributs pour standardiser les types de données envoyés à la PWA.
     */
    protected $casts = [
        'montant_total' => 'decimal:2',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Force le formatage de toutes les dates en chaînes de caractères pures 
     * lors de la conversion en JSON (via toArray()). Crucial pour IndexedDB / Dexie.
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    // --- RELATIONS ---

    /**
     * Un paiement peut être lié à plusieurs lignes de Bonus/Commissions
     */
    public function bonuses()
    {
        return $this->hasMany(Bonus::class);
    }

    /**
     * L'agent qui reçoit le paiement
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * L'administrateur qui a effectué l'action
     */
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // --- LOGIQUE MÉTIER ---

    /**
     * Vérifier si c'est un rejet en un clin d'œil
     */
    public function isRejet()
    {
        return $this->type === 'rejet';
    }
}