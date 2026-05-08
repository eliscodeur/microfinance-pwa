<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $fillable = ['agent_id', 'montant_total', 'reference', 'validated_by'];

    // Un paiement regroupe plusieurs bonus
    public function bonuses()
    {
        return $this->hasMany(Bonus::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
