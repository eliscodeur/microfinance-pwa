<?php
namespace App\Models;

use App\Models\Retrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property \Carbon\Carbon|null $date_debut
 * @property \Carbon\Carbon|null $date_fin_prevue
 * @property \Carbon\Carbon|null $date_cloture_reelle
 * @property string $statut
 * @property float|int $montant_journalier
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Collecte[] $collectes
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Retrait[] $retraits
 */
class Cycle extends Model
{
    use HasFactory;
    protected $fillable = [
        'cycle_uid',
        'carnet_id',
        'agent_id',
        'client_id',
        'user_id',
        'montant_journalier',
        'nombre_jours_objectif',
        'statut',
        'commission_genere',
        'date_debut',
        'date_fin_prevue',
        'date_cloture_reelle',
        'completed_at',
        'retire_at',
    ];

    protected $casts = [
        'date_debut'          => 'date:Y-m-d',
        'date_fin_prevue'     => 'date:Y-m-d',
        'date_cloture_reelle' => 'date:Y-m-d',
        'completed_at'        => 'datetime',
        'retire_at'           => 'datetime',
    ];

    public function carnet()
    {
        return $this->belongsTo(Carnet::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function collectes()
    {
        return $this->hasMany(Collecte::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function retraits()
    {
        return $this->hasMany(Retrait::class, 'cycle_id');
    }

    public function creditSettlementSources()
    {
        return $this->hasMany(CreditSettlementSource::class);
    }

    public function scopeVisibleForAgentSync(Builder $query): Builder
    {
        return $query->where('statut', 'en_cours');
    }

    public function getSoldeBrutRestantAttribute()
    {
        $totalCollecte = $this->relationLoaded('collectes')
            ? $this->collectes->sum('montant')
            : $this->collectes()->sum('montant');
        $totalRetire = $this->relationLoaded('retraits')
            ? $this->retraits->sum('montant_net')
            : $this->retraits()->sum('montant_net');
        $totalAffecteAuCredit = $this->relationLoaded('creditSettlementSources')
            ? $this->creditSettlementSources->sum('montant_affecte')
            : $this->creditSettlementSources()->sum('montant_affecte');

        return $totalCollecte - $totalRetire - $totalAffecteAuCredit;
    }

    /**
     * Calcul du solde net (après déduction de la commission/mise journalière)
     */
    public function getSoldeNetRestantAttribute()
    {
        $commission = (float) ($this->relationLoaded('retraits')
            ? $this->retraits->sum('commission')
            : $this->retraits()->sum('commission'))
            + (float) ($this->relationLoaded('creditSettlementSources')
                ? $this->creditSettlementSources->sum('commission')
                : $this->creditSettlementSources()->sum('commission'));
        $alreadyUsed = ($this->relationLoaded('retraits')
            ? $this->retraits->isNotEmpty()
            : $this->retraits()->exists())
            || ($this->relationLoaded('creditSettlementSources')
                ? $this->creditSettlementSources->isNotEmpty()
                : $this->creditSettlementSources()->exists());
        if (! $alreadyUsed) {
            $commission = (float) ($this->montant_journalier ?? 0);
        }
        return $this->solde_brut_restant - $commission;
    }

    public function getSoldeCompensationRestantAttribute(): float
    {
        $collected = (float) ($this->relationLoaded('collectes')
            ? $this->collectes->sum('montant')
            : $this->collectes()->sum('montant'));
        $withdrawn = (float) ($this->relationLoaded('retraits')
            ? $this->retraits->sum('montant_net')
            : $this->retraits()->sum('montant_net'));
        $withdrawalFees = (float) ($this->relationLoaded('retraits')
            ? $this->retraits->sum('commission')
            : $this->retraits()->sum('commission'));
        $settled = (float) ($this->relationLoaded('creditSettlementSources')
            ? $this->creditSettlementSources->sum('montant_affecte')
            : $this->creditSettlementSources()->sum('montant_affecte'));
        $settlementFees = (float) ($this->relationLoaded('creditSettlementSources')
            ? $this->creditSettlementSources->sum('commission')
            : $this->creditSettlementSources()->sum('commission'));

        return max(0, round($collected - $withdrawn - $withdrawalFees - $settled - $settlementFees, 2));
    }

    public function calculerCommission(): float
    {
        return (float) ($this->montant_journalier ?? 0);
    }

    /**
     * Calcule le total collecté sur ce cycle.
     */
    public function totalCollecte(): float
    {
        return (float) $this->collectes()->sum('montant');
    }

    public function bonuses()
    {
        return $this->hasMany(Bonus::class, 'cycle_id');
    }
}
