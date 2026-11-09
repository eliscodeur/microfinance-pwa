<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditSettlementSource extends Model
{
    protected $fillable = [
        'credit_settlement_id',
        'cycle_id',
        'carnet_id',
        'montant_affecte',
        'commission',
    ];

    protected $casts = [
        'montant_affecte' => 'decimal:2',
        'commission' => 'decimal:2',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(CreditSettlement::class, 'credit_settlement_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function carnet(): BelongsTo
    {
        return $this->belongsTo(Carnet::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CreditSettlementAllocation::class);
    }
}
