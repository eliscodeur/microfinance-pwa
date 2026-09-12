<?php

namespace App\Models;

use App\Models\Carnet;
use App\Models\ClientAgentHistory;
use App\Models\ClientCarnetNumber;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ulid',
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
        'is_active',
        'created_by',
    ];

    /**
     * Spécifie la colonne qui génère l'ULID automatiquement.
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Utilise la colonne ULID pour la résolution des routes (ex: /clients/{client}).
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /* -------------------------------------------------------------------------- */
    /* RELATIONS ADMINISTRATION                                                   */
    /* -------------------------------------------------------------------------- */

    /**
     * L'utilisateur/admin qui a créé la fiche client.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /* -------------------------------------------------------------------------- */
    /* RELATIONS HISTORIQUE AGENT                                                 */
    /* -------------------------------------------------------------------------- */

    /**
     * Historique complet de l'affectation du client aux agents (Prospection/Zones).
     */
    public function agentHistories(): HasMany
    {
        return $this->hasMany(ClientAgentHistory::class);
    }

    /**
     * Affectation agent actuellement active (si unassigned_at est NULL).
     */
    public function currentAgentHistory(): HasOne
    {
        return $this->hasOne(ClientAgentHistory::class)->whereNull('unassigned_at');
    }

    /* -------------------------------------------------------------------------- */
    /* RELATIONS CARNETS & NUMÉROS                                                */
    /* -------------------------------------------------------------------------- */

    public function carnetNumbers(): HasMany
    {
        return $this->hasMany(ClientCarnetNumber::class);
    }

    public function availableCarnetNumbers(): HasMany
    {
        return $this->hasMany(ClientCarnetNumber::class)->where('statut', 'disponible');
    }

    public function carnets(): HasMany
    {
        return $this->hasMany(Carnet::class);
    }

    public function carnet(): HasOne
    {
        return $this->hasOne(Carnet::class)->where('statut', 'actif');
    }

    public function activeCarnets(): HasMany
    {
        return $this->hasMany(Carnet::class)->where('statut', 'actif');
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }
}