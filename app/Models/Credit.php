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
        'cycle_id',
        'agent_id',
        'admin_id',
        'credit_product_id', 
        'credit_object_id',  
        'type_support',     
        'montant_demande',
        'montant_accorde',
        'taux',
        'taux_manuel',      
        'mode',
        'periodicite',
        'nombre_echeances',
        'differe',
        'frais_dossier',    
        'montant_echeance',
        'montant_echeance_differe', 
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

    /**
     * Typage strict pour éviter les bugs de centimes en FCFA (integer pour la monnaie)
     */
    protected $casts = [
        'date_demande'             => 'date',
        'date_debut'               => 'date:Y-m-d',
        'date_fin_prevue'          => 'date',
        'approved_at'              => 'datetime',
        'metadata'                 => 'array',
        'montant_demande'          => 'integer',
        'montant_accorde'          => 'integer',
        'frais_dossier'            => 'integer',
        'montant_echeance'         => 'integer',
        'montant_echeance_differe' => 'integer',
        'interet_total'            => 'integer',
        'montant_rembourse'        => 'integer',
        'blocked_amount'           => 'integer',
        'penalty_amount'           => 'integer',
        'taux'                     => 'decimal:4',
        'taux_manuel'              => 'decimal:4',
    ];

    /* -------------------------------------------------------------------------
     * RELATIONS
     * ------------------------------------------------------------------------- */

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function carnet()
    {
        return $this->belongsTo(Carnet::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    // --- NOUVELLES RELATIONS ---
    
    public function creditProduct()
    {
        return $this->belongsTo(CreditProduct::class);
    }

    public function creditObject()
    {
        return $this->belongsTo(CreditObject::class);
    }

    
    public function creditGuarantor() 
    {
        return $this->hasOne(CreditGuarantor::class, 'credit_id');
    }
    // ---------------------------

    public function payments()
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function schedules()
    {
        return $this->hasMany(CreditSchedule::class);
    }

    /* -------------------------------------------------------------------------
     * ACCESSEURS & MUTATEURS
     * ------------------------------------------------------------------------- */

    public function getMontantRestantAttribute(): int
    {
        return (int) max(0, ($this->montant_accorde + $this->interet_total) - $this->montant_rembourse);
    }

    public function getIsInDiffereAttribute(): bool
    {
        if (!$this->differe || $this->differe <= 0) {
            return false;
        }

        $paidSchedulesCount = $this->schedules()->where('statut', 'paye')->count();
        
        return $paidSchedulesCount < $this->differe;
    }
}