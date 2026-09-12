<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ulid',
        'agent_id',
        'montant_total',
        'type',
        'reference',
        'validated_by',
        'inclus_dans_salaire_at',
    ];

    /**
     * Cast des attributs pour standardiser les types de données envoyés à la PWA.
     */
    protected $casts = [
        'montant_total'          => 'decimal:2',
        'created_at'             => 'datetime:Y-m-d H:i:s',
        'updated_at'             => 'datetime:Y-m-d H:i:s',
        'inclus_dans_salaire_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

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
