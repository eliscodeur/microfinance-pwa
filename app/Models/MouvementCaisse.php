<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MouvementCaisse extends Model
{
    use HasFactory;

    protected $table = 'mouvements_caisses';

    protected $fillable = [
        'ulid',
        'date_mouvement',
        'type_operation',
        'sens',
        'montant',
        'mode_paiement',
        'reference',
        'libelle',
        'source_type',
        'source_id',
        'user_id',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
