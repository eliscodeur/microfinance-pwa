<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncBatchCollecte extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_batch_id',
        'sync_batch_cycle_id',
        'collecte_uid',
        'cycle_uid',
        'cycle_ref',
        'client_id',
        'agent_id',
        'pointage',
        'montant',
        'date_saisie',
        'payload',
    ];

    protected $casts = [
        'date_saisie' => 'datetime',
        'montant' => 'decimal:2',
        'payload' => 'array',
    ];

    public function batch()
    {
        return $this->belongsTo(SyncBatch::class, 'sync_batch_id');
    }

    public function batchCycle()
    {
        return $this->belongsTo(SyncBatchCycle::class, 'sync_batch_cycle_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
