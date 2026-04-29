<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'nom', 
        'prenom',
        'date_naissance', 
        'lieu_naissance', 
        'genre', 
        'statut_matrimonial', 
        'nationalite', 
        'profession', 
        'telephone', 
        'adresse', 
        'photo', 
        'reference_nom', 
        'reference_telephone', 
        'agent_id'
    ];

    public function agent(){
        return $this->belongsTo(\App\Models\Agent::class);
    }

    public function agentHistory()
    {
        return $this->hasMany(\App\Models\ClientAgentHistory::class)
            ->orderBy('assigned_at', 'desc');
    }
    public function carnets()
    {
        return $this->hasMany(Carnet::class);
    }

    public function carnet()
    {
        return $this->hasOne(Carnet::class)->where('statut', 'actif');
    }
}
