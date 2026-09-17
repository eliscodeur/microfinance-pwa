<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CreditType extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'nom',
        'code',
        'is_active',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = strtolower((string) Str::ulid());
            }
        });
    }

    /**
     * Un type de crédit possède plusieurs produits de crédit.
     */
    public function products(): HasMany
    {
        return $this->hasMany(CreditProduct::class);
    }
}
