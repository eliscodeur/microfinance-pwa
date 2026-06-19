<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CreditObject extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'secteur_activite',
        'is_active',
    ];

    /**
     * Les produits de crédit qui acceptent ce motif (Relation Pivot Inverse).
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(CreditProduct::class);
    }
    public function creditProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            CreditProduct::class, 
            'credit_object_credit_product', 
            'credit_object_id', 
            'credit_product_id'
        )->withTimestamps();
    }
}