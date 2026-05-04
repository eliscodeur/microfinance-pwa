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

    public function minimumPointagesRequired(): int
    {
        if (!$this->nombre_cycles) {
            return 15;
        }

        $label = strtolower($this->libelle ?? '');

        if (str_contains($label, 'quinzaine')) {
            return max(15, (int) $this->nombre_cycles);
        }

        if ((int) $this->nombre_cycles >= 15) {
            return (int) $this->nombre_cycles;
        }

        return 15 * (int) $this->nombre_cycles;
    }
}