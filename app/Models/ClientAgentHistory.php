<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAgentHistory extends Model
{
    use HasFactory;

    protected $table = 'client_agent_history';

    protected $fillable = [
        'client_id',
        'agent_id',
        'assigned_at',
        'unassigned_at'
    ];

    protected $dates = ['assigned_at', 'unassigned_at'];

    public function client()
    {
        return $this->belongsTo(\App\Models\Client::class);
    }

    public function agent()
    {
        return $this->belongsTo(\App\Models\Agent::class);
    }
}
