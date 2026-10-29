<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'cash_session_id',
        'user_id',
        'fond_caisse_initial',
        'total_entrees',
        'total_sorties',
        'solde_theorique',
        'solde_physique',
        'ecart',
        'commentaire',
        'date_cloture',
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
