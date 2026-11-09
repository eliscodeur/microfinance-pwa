<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Depot extends Model implements Auditable
{
    use HasFactory;use \OwenIt\Auditing\Auditable;

    // Champs que l'admin peut remplir via le formulaire
    protected $fillable = [
        'client_id',
        'carnet_id',
        'cycle_id',
        'user_id',
        'cash_session_id',
        'montant',
        'date_depot',
        'commentaire',
        'mode_paiement',
        'reference_paiement',
        'payeur_type',
        'payeur_nom',
        'payeur_telephone',
        'payeur_lien',
        'reference_operation',
        'idempotency_key',
        'solde_carnet_avant',
        'solde_carnet_apres',
    ];

    // On indique à Laravel de traiter date_depot comme une date Carbon
    protected $casts = [
        'date_depot' => 'datetime',
        'montant' => 'decimal:2',
        'solde_carnet_avant' => 'decimal:2',
        'solde_carnet_apres' => 'decimal:2',
    ];

    /**
     * Le carnet auquel appartient ce dépôt
     */
    public function carnet()
    {
        return $this->belongsTo(Carnet::class);
    }

    /**
     * L'administrateur qui a enregistré l'opération
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le client propriétaire
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
