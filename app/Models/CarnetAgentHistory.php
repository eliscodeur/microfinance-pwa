<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarnetAgentHistory extends Model
{
    use HasFactory, HasUlids; 

    protected $table = 'carnet_agent_histories';

    protected $fillable = [
        'ulid', 
        'carnet_id',
        'agent_id',
        'assigned_at',
        'unassigned_at',
    ];

    protected $casts = [
        'assigned_at'   => 'datetime',
        'unassigned_at' => 'datetime',
    ];

    /**
     * 4. Spécifie la colonne qui génère l'ULID automatiquement (au lieu d'écraser l'id).
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * 5. Utilise la colonne ULID pour la résolution des routes (si besoin d'API/URL).
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /* -------------------------------------------------------------------------- */
    /* RELATIONS                                                                  */
    /* -------------------------------------------------------------------------- */

    public function carnet(): BelongsTo
    {
        return $this->belongsTo(Carnet::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}