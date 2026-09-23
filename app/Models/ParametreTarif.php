<?php
namespace App\Models;

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametreTarif extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'libelle',
        'prix',
    ];
}
