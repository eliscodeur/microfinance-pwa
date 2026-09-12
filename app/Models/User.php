<?php

namespace App\Models;

use App\Models\Agent;
use App\Models\Retrait;
use App\Models\Role;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUlids, Notifiable;

    protected $fillable = [
        'ulid',
        'name',
        'email',
        'password',
        'username',
        'type',
        'role_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
    ];

    /**
     * Génère l'ULID dans la colonne 'ulid' lors de la création.
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Utilise l'ULID pour la résolution des URLs et API routes.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /* -------------------------------------------------------------------------- */
    /* RELATIONS                                                                  */
    /* -------------------------------------------------------------------------- */

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id')->withDefault([
            'nom' => '---',
        ]);
    }

    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class, 'user_id');
    }

    public function retraits(): HasMany
    {
        return $this->hasMany(Retrait::class, 'admin_id');
    }
}