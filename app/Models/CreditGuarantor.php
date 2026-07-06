<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditGuarantor extends Model
{
    // Indique explicitement le nom de la table
    protected $table = 'credit_guarantors';

    protected $fillable = [
        'credit_id', 
        'nom_prenom', 
        'telephone', 
        'profession', 
        'adresse', 
        'piece_identite', 
        'justificatif_revenu'
    ];
}
