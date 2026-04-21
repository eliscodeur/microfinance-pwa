<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'sync_uuid',
        'status',
        'nb_collectes',
        'nb_cycles',
        'total_montant',
        'review_note',
        'reviewed_by',
        'reviewed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'total_montant' => 'decimal:2',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function cycles()
    {
        return $this->hasMany(SyncBatchCycle::class);
    }

    public function collectes()
    {
        return $this->hasMany(SyncBatchCollecte::class);
    }
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
