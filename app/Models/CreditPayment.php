<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_id',
        'echeance',
        'due_date',
        'montant_principal',
        'montant_interets',
        'montant_total',
        'montant_paye',
        'status',
        'date_paye',
        'penalite',
    ];

    protected $casts = [
        'due_date' => 'date',
        'date_paye' => 'datetime',
        'montant_principal' => 'decimal:2',
        'montant_interets' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'penalite' => 'decimal:2',
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }
}
