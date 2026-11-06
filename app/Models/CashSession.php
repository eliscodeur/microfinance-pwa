<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class CashSession extends Model implements Auditable
{
    use HasFactory;use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'ulid',
        'user_id',
        'fond_caisse_initial',
        'date_ouverture',
        'date_fermeture',
        'statut',
    ];

    protected $casts = [
        'date_ouverture' => 'datetime',
        'date_fermeture' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function closing()
    {
        return $this->hasOne(CashClosing::class, 'cash_session_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(MouvementCaisse::class, 'cash_session_id');
    }
}
