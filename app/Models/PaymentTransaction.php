<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_uid',
        'credit_payment_id',
        'credit_id',
        'agent_id',
        'montant',
        'mode_paiement',
        'reference_externe',
        'payer_name',
        'payer_phone',
        'payer_relation',
        'notes',
    ];

    protected $casts = [
        'montant' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->transaction_uid)) {
                $model->transaction_uid = strtolower((string) Str::ulid());
            }
        });
    }

    // --- RELATIONS ---

    public function creditPayment()
    {
        return $this->belongsTo(CreditPayment::class);
    }

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
