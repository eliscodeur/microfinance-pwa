<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoriesCharge extends Model
{
    protected $table = 'categories_charges';

    protected $fillable = [
        'ulid',
        'types_charge_id',
        'libelle',
        'code_analytique',
        'description',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    /**
     * Une sous-catégorie appartient à un seul type macro de charge.
     */
    public function typeCharge(): BelongsTo
    {
        return $this->belongsTo(TypesCharge::class, 'types_charge_id');
    }
}
