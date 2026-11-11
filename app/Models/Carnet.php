<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Contracts\Auditable;

class Carnet extends Model implements Auditable
{
    use HasFactory, HasUlids;
    use \OwenIt\Auditing\Auditable;

    /**
     * Les attributs personnalisés à ajouter aux tableaux/JSON.
     */
    protected $appends = [
        'solde_tontine_non_retire',
        'is_deletable',
    ];

    /**
     * Les attributs qui doivent être castés.
     */
    protected $casts = [
        'date_debut' => 'date',
    ];

    /**
     * Les attributs assignables en masse.
     */
    protected $fillable = [
        'ulid',
        'client_id',
        'type',
        'category_tontine_id',
        'parent_id',
        'numero',
        'statut',
        'date_debut',
        'agent_id',
        'created_by',
    ];

    /**
     * Spécifie la colonne qui génère l'ULID automatiquement.
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Utilise la colonne ULID pour la résolution des routes (ex: /carnets/{carnet}).
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /* -------------------------------------------------------------------------- */
    /* RELATIONS                                                                  */
    /* -------------------------------------------------------------------------- */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function depots(): HasMany
    {
        return $this->hasMany(Depot::class);
    }

    public function retraits(): HasMany
    {
        return $this->hasMany(Retrait::class);
    }

    public function creditSettlementSources(): HasMany
    {
        return $this->hasMany(CreditSettlementSource::class);
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(Cycle::class);
    }

    public function collectes(): HasManyThrough
    {
        return $this->hasManyThrough(Collecte::class, Cycle::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function categoryTontine(): BelongsTo
    {
        return $this->belongsTo(CategoryTontine::class, 'category_tontine_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Carnet::class, 'parent_id');
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(Carnet::class, 'parent_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* -------------------------------------------------------------------------- */
    /* RELATIONS HISTORIQUE AGENTS COLLECTEURS                                    */
    /* -------------------------------------------------------------------------- */

    /**
     * Historique complet des agents collecteurs ayant géré ce carnet.
     */
    public function agentHistories(): HasMany
    {
        return $this->hasMany(CarnetAgentHistory::class);
    }

    /**
     * Affectation de l'agent collecteur actuellement active (unassigned_at IS NULL).
     */
    public function currentAgentHistory(): HasOne
    {
        return $this->hasOne(CarnetAgentHistory::class)->whereNull('unassigned_at');
    }

    /* -------------------------------------------------------------------------- */
    /* ACCESSEURS (ATTRIBUTES)                                                    */
    /* -------------------------------------------------------------------------- */

    /**
     * Solde disponible pour les carnets de type "compte" (Épargne)
     */
    public function getSoldeDisponibleAttribute(): float
    {
        $totalDepots = (float) $this->depots->sum('montant');
        // On déduit tous les retraits liés à ce carnet (montant_net pour la sortie réelle client)
        $totalRetraits = (float) $this->retraits->sum('montant_net');
        $totalAffecteCredit = (float) $this->creditSettlementSources->sum('montant_affecte');

        return round($totalDepots - $totalRetraits - $totalAffecteCredit, 2);
    }

    /**
     * Argent des cycles de tontine terminés qui n'a pas encore été retiré (Net restant)
     */
    public function getSoldeTontineNonRetireAttribute(): float
    {
        // On ne calcule que pour les cycles terminés qui n'ont pas encore leur date de retrait final
        $cyclesPrets = $this->cycles
            ->where('statut', 'termine')
            ->whereNull('retire_at');

        return (float) $cyclesPrets->reduce(function ($carry, $cycle) {
            $totalCollectes  = (float) $cycle->collectes->sum('montant');
            $totalDejaRetire = (float) $cycle->retraits->sum('montant_net');
            $creditSources = $cycle->creditSettlementSources;
            $totalAffecteCredit = (float) $creditSources->sum('montant_affecte');
            $commissionDejaPrelevee = (float) $cycle->retraits->sum('commission')
                + (float) $creditSources->sum('commission');
            $cycleAlreadyUsed = $cycle->retraits->isNotEmpty() || $creditSources->isNotEmpty();
            $commissionFixe  = ! $cycleAlreadyUsed
                ? (float) ($cycle->montant_journalier ?? 0)
                : 0.0;

            $soldeRestant = $totalCollectes
                - $totalAffecteCredit
                - $commissionDejaPrelevee
                - $commissionFixe
                - $totalDejaRetire;

            // On retourne le cumul, mais jamais en dessous de 0
            return $carry + max(0, $soldeRestant);
        }, 0.0);
    }

    public function getIsDeletableAttribute(): bool
    {
        return ! $this->cycles()->exists()
        && ! $this->depots()->exists()
        && ! $this->retraits()->exists()
        && ! $this->credits()->exists();
    }

    /* -------------------------------------------------------------------------- */
    /* LOGIQUE MÉTIER                                                             */
    /* -------------------------------------------------------------------------- */

    /**
     * Épargne retirable des cycles terminés (Alias pour l'attribut)
     */
    public function terminalWithdrawableSavings(): float
    {
        return $this->solde_tontine_non_retire;
    }

    /**
     * Épargne disponible dans les cycles actifs (Non encore terminés)
     */
    public function activeCycleSavings(): float
    {
        // On utilise la relation chargée pour éviter les requêtes N+1
        $cycleEnCours = $this->cycles->where('statut', 'en_cours')->first();

        if (! $cycleEnCours) {
            return 0.0;
        }

        return (float) $cycleEnCours->collectes->sum('montant');
    }

    /**
     * Total de l'épargne (Actifs + Terminés non retirés)
     */
    public function availableSavings(): float
    {
        return round($this->activeCycleSavings() + $this->terminalWithdrawableSavings(), 2);
    }

    /**
     * Total des pointages pour ce carnet.
     */
    public function totalPointages(): int
    {
        return (int) $this->collectes->sum('pointage');
    }

    /**
     * Récupère tous les carnets liés (parents et enfants).
     */
    public function allLinkedCarnets(): Collection
    {
        $collection = collect([$this]);

        if ($this->parent) {
            $collection->push($this->parent);
            $collection = $collection->merge($this->parent->enfants);
        }

        if ($this->type === 'tontine') {
            $collection = $collection->merge($this->enfants);
        }

        return $collection->unique('id');
    }

    public function guaranteeBase(): float
    {
        return round($this->allLinkedCarnets()->sum(fn(Carnet $c) => $c->availableSavings()), 2);
    }

    public function withdrawableGuarantee(): float
    {
        return round($this->allLinkedCarnets()->sum(fn(Carnet $c) => $c->terminalWithdrawableSavings()), 2);
    }

    public function availableTontineSettlementFunds(): float
    {
        $linkedCarnets = $this->allLinkedCarnets();
        $linkedCarnets = self::with([
            'cycles.collectes',
            'cycles.retraits',
            'cycles.creditSettlementSources',
        ])->whereKey($linkedCarnets->pluck('id'))->get();

        return round($linkedCarnets
            ->where('type', 'tontine')
            ->where('client_id', $this->client_id)
            ->sum(function (Carnet $carnet) {
                return $carnet->cycles
                    ->where('statut', 'termine')
                    ->whereNull('retire_at')
                    ->sum(fn(Cycle $cycle) => $cycle->solde_compensation_restant);
            }), 2);
    }

    public function availableCreditSettlementFunds(): float
    {
        return $this->type === 'compte'
            ? max(0, $this->solde_disponible)
            : $this->availableTontineSettlementFunds();
    }

    /* -------------------------------------------------------------------------- */
    /* EVENEMENTS ELOQUENT                                                        */
    /* -------------------------------------------------------------------------- */

    protected static function booted(): void
    {
        static::created(function (Carnet $carnet) {
            \App\Models\ClientCarnetNumber::where('client_id', $carnet->client_id)
                ->where('numero', $carnet->numero)
                ->where('statut', 'disponible')
                ->update([
                    'statut'  => 'utilise',
                    'used_at' => now(),
                ]);
        });

        static::deleting(function (Carnet $carnet) {
            if (! $carnet->is_deletable) {
                throw new \Exception("Action impossible : Ce carnet contient des transactions actives.");
            }
        });
    }
}
