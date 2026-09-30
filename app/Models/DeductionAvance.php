<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeductionAvance extends Model
{
    use HasFactory;

    protected $table = 'deductions_avances';

    protected $guarded = ['id'];

    // Génération automatique de l'ULID à la création
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function salaryAdvance()
    {
        return $this->belongsTo(SalaryAdvance::class, 'salary_advance_id');
    }

    /**
     * Relation avec l'employé administratif (si applicable)
     */
    public function employeAdministratif()
    {
        return $this->belongsTo(EmployeAdministratif::class, 'employe_administratif_id');
    }
}
