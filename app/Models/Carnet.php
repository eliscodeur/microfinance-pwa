<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Carnet extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'type', 
        'category_tontine_id',
        'parent_id',
        'numero',
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
                $nextId = $latest ? $latest->id + 1 : 1;

                if ($carnet->type === 'tontine') {
                    $carnet->numero = 1000 + $nextId;
                } 
                else if ($carnet->type === 'compte') {
                    // SÉCURITÉ : On récupère manuellement le client si la relation est vide
                    $client = $carnet->client ?? \App\Models\Client::find($carnet->client_id); 
                    
                    if ($client) {
                        $initialeNom = strtoupper(substr($client->nom, 0, 1));
                        // Gestion du prénom vide ou null
                        $prenom = $client->prenom ?? 'X';
                        $initialePrenom = strtoupper(substr($prenom, 0, 1));
                        
                        $idPart = 1000 + $nextId;
                        $carnet->numero = "{$initialeNom}{$idPart}{$initialePrenom}";
                    } else {
                        // Fallback au cas où le client n'existe vraiment pas
                        $carnet->numero = "C" . (1000 + $nextId);
                    }
                }
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
    // app/Models/Carnet.php

    public function categoryTontine()
    {
        return $this->belongsTo(CategoryTontine::class, 'category_tontine_id');
    }

    public function parent()
    {
        return $this->belongsTo(Carnet::class, 'parent_id');
    }

    public function enfants() // Pour voir les comptes liés à une tontine
    {
        return $this->hasMany(Carnet::class, 'parent_id');
    }
}