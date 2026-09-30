<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Fonction extends Model
{
    use HasFactory;

    protected $table = 'fonctions';

    protected $fillable = [
        'ulid',
        'libelle',
        'salaire_base_defaut',
        'description',
    ];

    // Génération automatique d'un ULID lors de la création
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = strtolower((string) Str::ulid());
            }
        });
    }

    // Relation : Une fonction est exercée par plusieurs employés administratifs
    public function employes()
    {
        return $this->hasMany(EmployeAdministratif::class, 'fonction_id');
    }
}
