<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id', 
        'sync_uuid', 
        'nb_collectes', 
        'nb_cycles', 
        'total_montant', 
        'status', 
        'ip_address', 
        'error_message'
    ];

    // L'accessor pour le badge dont on a parlé
    public function getStatusBadgeAttribute()
    {
        return $this->status === 'success' 
            ? '<span class="badge bg-success">Réussi</span>' 
            : '<span class="badge bg-danger">Échec</span>';
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
