<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depense extends Model
{
    protected $table = 'depenses';

    protected $fillable = [
        'ulid',
        'categories_charge_id',
        'montant',
        'date_depense',
        'beneficiaire',
        'mode_paiement',
        'reference_piece',
        'motif',
        'user_id',
    ];

    protected $casts = [
        'montant'      => 'decimal:2',
        'date_depense' => 'date',
    ];

    /**
     * Utiliser l'ULID à la place de l'ID pour le Route Model Binding
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Relation avec la catégorie de charge.
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategoriesCharge::class, 'categories_charge_id');
    }

    /**
     * Relation avec l'utilisateur (agent/admin) ayant saisi la dépense.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
