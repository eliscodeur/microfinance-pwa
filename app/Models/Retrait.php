<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Retrait extends Model implements Auditable
{
    use HasFactory;use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'cycle_id',
        'client_id',
        'carnet_id',
        'admin_id',
        'cash_session_id',
        'montant_total',
        'commission',
        'montant_net',
        'date_retrait',
        'note',
        'reference_operation',
        'idempotency_key',
        'mode_paiement',
        'reference_paiement',
        'motif',
        'solde_carnet_avant',
        'solde_carnet_apres',
    ];

    protected $auditExclude = [
        'note',
        'updated_at',
    ];

    protected $casts = [
        'date_retrait'  => 'datetime',
        'montant_total' => 'decimal:2',
        'commission'    => 'decimal:2',
        'montant_net'   => 'decimal:2',
    ];

    public function cycle()
    {
        return $this->belongsTo(Cycle::class)->withDefault();
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function carnet()
    {
        return $this->belongsTo(Carnet::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function cashSession()
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }
}
