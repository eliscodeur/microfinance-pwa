<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Importe Str pour générer l'ULID automatiquement

class MouvementStockCarnets extends Model
{
    use HasFactory;
    // Note : On retire "HasUlids" d'ici pour garder l'ID en auto-increment classique !

    protected $table = 'mouvement_stock_carnets';

    protected $fillable = [
        'ulid',
        'categories_tontine_id',
        'type',
        'quantite',
        'prix_unitaire_achat',
        'prix_unitaire_vente',
        'motif',
    ];

    // Générer automatiquement l'ULID avant la création de l'enregistrement
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = strtolower((string) Str::ulid());
            }
        });
    }

    public function categoryTontine()
    {
        return $this->belongsTo(CategoryTontine::class, 'categories_tontine_id');
    }
}
