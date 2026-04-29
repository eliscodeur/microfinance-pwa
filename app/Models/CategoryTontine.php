<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryTontine extends Model
{
    use HasFactory;

    // On indique le nom exact de la table dans ta base de données
    protected $table = 'categories_tontine'; 

    // On autorise le remplissage des champs
    protected $fillable = [
        'libelle',
        'prix',
        'nombre_cycles',
        'description'
    ];

    public function carnets()
    {
        return $this->hasMany(Carnet::class, 'category_tontine_id');
    }
}