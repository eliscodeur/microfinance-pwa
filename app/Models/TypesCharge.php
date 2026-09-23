<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypesCharge extends Model
{
    protected $table = 'types_charges';

    protected $fillable = [
        'ulid',
        'libelle',
        'code',
        'description',
    ];

    /**
     * Une grande catégorie de charge possède plusieurs sous-catégories.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(CategoriesCharge::class, 'types_charge_id');
    }
}
