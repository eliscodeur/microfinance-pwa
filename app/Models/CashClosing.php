<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class CashClosing extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'ulid',
        'cash_session_id',
        'user_id',
        'fond_caisse_initial',
        'total_entrees',
        'total_sorties',
        'total_entrees_hors_especes',
        'total_sorties_hors_especes',
        'solde_theorique',
        'solde_physique',
        'ecart',
        'commentaire',
        'date_cloture',
    ];

    protected $casts = [
        'date_cloture' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }
}
