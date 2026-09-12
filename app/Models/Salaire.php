<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salaire extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'salaires';

    protected $fillable = [
        'reference',
        'agent_id',
        'mois',
        'annee',
        'periode_debut',
        'periode_fin',
        'montant_net',
        'statut',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'periode_debut' => 'date',
        'periode_fin'   => 'date',
        'validated_at'  => 'datetime',
        'montant_net'   => 'decimal:2',
    ];

    /**
     * Boot the model to auto-generate the reference on creation.
     */
    protected static function booted()
    {
        static::creating(function ($salaire) {
            if (empty($salaire->reference)) {
                $moisFormate = str_pad($salaire->mois, 2, '0', STR_PAD_LEFT);
                $anneeMois   = $salaire->annee . $moisFormate;

                // Génération d'une référence unique (ex: SAL-202609-001)
                $dernierId      = self::max('id') ?? 0;
                $numeroSequence = str_pad(substr(preg_replace('/[^0-9]/', '', $dernierId), -4) + 1, 5, '0', STR_PAD_LEFT);

                $salaire->reference = 'SAL-' . $anneeMois . '-' . $numeroSequence;
            }
        });
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
