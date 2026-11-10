<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaireEmploye extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'salaires_employe';

    protected $guarded = ['id'];

    protected $casts = [
        'validated_at' => 'datetime',
        'paid_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    // Indiquer à Laravel que la clé d'identification de route par défaut (route model binding) est 'ulid' au lieu de 'id'
    public function getRouteKeyName()
    {
        return 'ulid';
    }

    // Relation vers l'employé
    public function employe()
    {
        return $this->belongsTo(EmployeAdministratif::class, 'employe_id');
    }

    // Relation vers la dépense attestant le paiement
    public function depense()
    {
        return $this->belongsTo(Depense::class, 'depense_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
