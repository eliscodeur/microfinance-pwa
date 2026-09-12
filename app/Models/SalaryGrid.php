<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryGrid extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ulid',
        'seuil_min',
        'seuil_max',
        'salaire_base',
        'commission_travail',
        'taux_carnet',
        'date_debut',
        'date_fin',
        'est_actif',
    ];

    protected $casts = [
        'seuil_min'          => 'decimal:2',
        'seuil_max'          => 'decimal:2',
        'salaire_base'       => 'decimal:2',
        'commission_travail' => 'decimal:2',
        'taux_carnet'        => 'decimal:2',
        'date_debut'         => 'date',
        'date_fin'           => 'date',
        'est_actif'          => 'boolean',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    // Scope pour récupérer facilement la grille active à une date donnée
    public function scopeActiveAt($query, $date = null)
    {
        $date = $date ?? now();
        return $query->where('est_actif', true)
            ->where('date_debut', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('date_fin')
                    ->orWhere('date_fin', '>=', $date);
            });
    }

    public function getGrilleForAmount(float $montant, $date = null)
    {
        // On s'appuie sur ton scope pour être sûr de cibler la grille active à la période voulue
        return SalaryGrid::activeAt($date)
            ->where(function ($query) use ($montant) {

                // Cas 1 : Les lignes normales avec un intervalle min et max (ex: 50000 - 59999)
                $query->where(function ($q) use ($montant) {
                    $q->whereNotNull('seuil_min')
                        ->where('seuil_min', '<=', $montant)
                        ->where('seuil_max', '>=', $montant);
                })

                // Cas 2 : La première ligne où seuil_min est NULL (ex: <= 40000)
                    ->orWhere(function ($q) use ($montant) {
                        $q->whereNull('seuil_min')
                            ->where('seuil_max', '>=', $montant);
                    });

            })
            ->first();
    }

}
