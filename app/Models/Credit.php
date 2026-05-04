<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_uid',
        'client_id',
        'carnet_id',
        'agent_id',
        'admin_id',
        'montant_demande',
        'montant_accorde',
        'taux',
        'taux_manuelle',
        'type',
        'mode',
        'periodicite',
        'nombre_echeances',
        'montant_echeance',
        'interet_total',
        'montant_rembourse',
        'blocked_amount',
        'penalty_amount',
        'statut',
        'date_demande',
        'date_debut',
        'date_fin_prevue',
        'approved_at',
        'metadata',
    ];

    protected $casts = [
        'date_demande' => 'date',
        'date_debut' => 'date',
        'date_fin_prevue' => 'date',
        'approved_at' => 'datetime',
        'metadata' => 'array',
        'montant_demande' => 'decimal:2',
        'montant_accorde' => 'decimal:2',
        'taux' => 'decimal:4',
        'taux_manuelle' => 'decimal:4',
        'interet_total' => 'decimal:2',
        'montant_rembourse' => 'decimal:2',
        'blocked_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function carnet()
    {
        return $this->belongsTo(Carnet::class);
    }

    public function payments()
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
