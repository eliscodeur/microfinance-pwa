<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditType extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'code',
        'is_active',
    ];

    /**
     * Un type de crédit possède plusieurs produits de crédit.
     */
    public function products(): HasMany
    {
        return $this->hasMany(CreditProduct::class);
    }
}