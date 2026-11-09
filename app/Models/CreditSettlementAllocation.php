<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditSettlementAllocation extends Model
{
    protected $fillable = [
        'credit_settlement_id',
        'credit_settlement_source_id',
        'credit_payment_id',
        'echeance',
        'date_echeance',
        'montant',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_echeance' => 'date',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(CreditSettlement::class, 'credit_settlement_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(CreditSettlementSource::class, 'credit_settlement_source_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CreditPayment::class, 'credit_payment_id');
    }
}
