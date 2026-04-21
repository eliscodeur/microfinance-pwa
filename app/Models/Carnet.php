<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Carnet extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'numero',
        'reference_physique', 
        'statut',
        'date_debut',
    ];

    // Un carnet appartient à un client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    // app/Models/Carnet.php
    protected static function booted()
    {
        static::creating(function ($carnet) {
            if (empty($carnet->numero)) {
                $latest = self::latest('id')->first();
                $number = $latest ? ((int) str_replace('NNC-', '', $latest->numero)) + 1 : 1;
                $carnet->numero = "NNC-" . str_pad($number, 3, '0', STR_PAD_LEFT);
            }
        });
    }
    public function cycles()
    {
        return $this->hasMany(Cycle::class);
    }

    public function collectes() {
        return $this->hasManyThrough(Collecte::class, Cycle::class);
    }

    public function retraits() {
        return $this->hasMany(Retrait::class); // Ou la table où tu stockes les sorties d'argent
    }
}