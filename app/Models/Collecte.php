<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Collecte extends Model
{
    protected $fillable = [
        'collecte_uid',
        'cycle_id',
        'cycle_uid',
        'client_id',
        'agent_id',
        'pointage',
        'numero_case',
        'montant',
        'date_saisie',
        'sync_uuid'
    ];

    protected $casts = [
        'date_saisie' => 'datetime',
        'montant' => 'decimal:2'
    ];

    public function cycle(): BelongsTo { return $this->belongsTo(Cycle::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function agent(): BelongsTo { return $this->belongsTo(Agent::class, 'agent_id');}
}
