<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;
    protected $fillable = [
       'user_id', 'code_agent', 'nom', 'telephone', 'image', 'actif', 'can_sync'
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function clients(): HasMany
    {
        // Vérifie bien que ta table 'clients' possède une colonne 'agent_id'
        return $this->hasMany(Client::class);
    }
    public function syncBatches(): HasMany
    {
        return $this->hasMany(SyncBatch::class);
    }
}
