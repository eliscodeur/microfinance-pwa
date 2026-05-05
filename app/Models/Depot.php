<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Depot extends Model
{
    use HasFactory;

    // Champs que l'admin peut remplir via le formulaire
    protected $fillable = [
        'client_id',
        'carnet_id',
        'cycle_id',
        'user_id',
        'montant',
        'date_depot',
        'commentaire'
    ];

    // On indique à Laravel de traiter date_depot comme une date Carbon
    protected $casts = [
        'date_depot' => 'datetime',
    ];

    /**
     * Le carnet auquel appartient ce dépôt
     */
    public function carnet()
    {
        return $this->belongsTo(Carnet::class);
    }

    /**
     * L'administrateur qui a enregistré l'opération
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le client propriétaire
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}