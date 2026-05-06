<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'cycle_id',
        'montant',
        'motif',
        'commission_genere',
        'admin_id',
        'validated_at',
        'validated_by',
        'date_attribution',
    ];
    
    protected $casts = [
        'montant' => 'decimal:2',
        'date_attribution' => 'date',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
    /**
     * Scope pour filtrer les commissions automatiques
    */
    public function scopeCommissions($query)
    {
        return $query->where('motif', 'like', '%Commission%');
    }

    /**
     * Scope pour filtrer les bonus manuels
     */
    public function scopeManuels($query)
    {
        return $query->where('motif', 'not like', '%Commission%');
    }
}