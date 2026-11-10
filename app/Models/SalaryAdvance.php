<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;

class SalaryAdvance extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'advance_uid',
        'agent_id',
        'employe_administratif_id',
        'montant_total',
        'montant_mensuel',
        'nombre_tranches',
        'tranches_payees',
        'montant_restant',
        'date_demande',
        'statut',
        'motif',
        'approved_by',
        'approved_at',
        'created_by',
        'approval_status',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'disbursed_by',
        'disbursed_at',
    ];

    protected $casts = [
        'montant_total'   => 'integer',
        'montant_mensuel' => 'integer',
        'montant_restant' => 'integer',
        'date_demande'    => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'disbursed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->advance_uid)) {
                $model->advance_uid = strtolower((string) Str::ulid());
            }
        });
    }

    // --- RELATIONS ---

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function disburser()
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }
    public function employe()
    {
        return $this->belongsTo(EmployeAdministratif::class, 'employe_administratif_id');
    }
}
