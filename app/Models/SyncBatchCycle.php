<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncBatchCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_batch_id',
        'cycle_uid',
        'carnet_id',
        'client_id',
        'agent_id',
        'montant_journalier',
        'nombre_jours_objectif',
        'statut',
        'date_debut',
        'date_fin_prevue',
        'completed_at',
        'payload',
    ];

    protected $casts = [
        'date_debut' => 'date:Y-m-d',
        'date_fin_prevue' => 'date:Y-m-d',
        'completed_at' => 'datetime',
        'payload' => 'array',
    ];

    public function batch()
    {
        return $this->belongsTo(SyncBatch::class, 'sync_batch_id');
    }

    public function carnet()
    {
        return $this->belongsTo(Carnet::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function collectes()
    {
        return $this->hasMany(SyncBatchCollecte::class);
    }
}
