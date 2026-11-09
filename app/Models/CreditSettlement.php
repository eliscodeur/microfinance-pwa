<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditSettlement extends Model
{
    protected $fillable = [
        'ulid',
        'reference',
        'idempotency_key',
        'credit_id',
        'client_id',
        'admin_id',
        'montant',
        'date_operation',
        'note',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_operation' => 'datetime',
    ];

    public function credit(): BelongsTo
    {
        return $this->belongsTo(Credit::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(CreditSettlementSource::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CreditSettlementAllocation::class);
    }
}
