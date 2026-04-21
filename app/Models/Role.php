<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Role extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'permissions'];

    // Transforme le JSON de la BDD en tableau PHP utilisable
    protected $casts = [
        'permissions' => 'array',
    ];

    // Relation : Un rôle peut avoir plusieurs utilisateurs
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
