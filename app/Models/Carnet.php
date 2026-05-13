<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Carnet extends Model
{
    use HasFactory;

    protected $casts = [
        'date_debut' => 'date',
    ];

    protected $fillable = [
        'client_id',
        'type', 
        'category_tontine_id',
        'parent_id',
        'numero',
        'statut',
        'date_debut',
    ];

    /* -------------------------------------------------------------------------- */
    /* RELATIONS                                  */
    /* -------------------------------------------------------------------------- */

    public function client() { return $this->belongsTo(Client::class); }
    public function depots() { return $this->hasMany(Depot::class); }
    public function retraits() { return $this->hasMany(Retrait::class); }
    public function cycles() { return $this->hasMany(Cycle::class); }
    public function collectes() { return $this->hasManyThrough(Collecte::class, Cycle::class); }
    public function credits() { return $this->hasMany(Credit::class); }
    public function categoryTontine() { return $this->belongsTo(CategoryTontine::class, 'category_tontine_id'); }
    public function parent() { return $this->belongsTo(Carnet::class, 'parent_id'); }
    public function enfants() { return $this->hasMany(Carnet::class, 'parent_id'); }

    /* -------------------------------------------------------------------------- */
    /* ATTRIBUTES                                 */
    /* -------------------------------------------------------------------------- */

    /**
     * Solde disponible pour les carnets de type "compte" (Épargne)
     */
    public function getSoldeDisponibleAttribute(): float
    {
        $totalDepots = (float) $this->depots->sum('montant');
        // On déduit tous les retraits liés à ce carnet (montant_net pour la sortie réelle client)
        $totalRetraits = (float) $this->retraits->sum('montant_net'); 
        
        return round($totalDepots - $totalRetraits, 2);
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
        $totalCollectes = (float) $cycle->collectes->sum('montant');
        $totalDejaRetire = (float) $cycle->retraits->sum('montant_net');
        $commissionFixe = (float) ($cycle->montant_journalier ?? 0);

        // LOGIQUE : Le solde disponible est TOUJOURS :
        // (Ce qui a été cotisé) - (La commission du cycle) - (Ce qui a déjà été pris)
        $soldeRestant = $totalCollectes - $commissionFixe - $totalDejaRetire;

        // On retourne le cumul, mais jamais en dessous de 0
        return $carry + max(0, $soldeRestant);
    }, 0);
}

    /**
     * Épargne retirable des cycles terminés (Alias pour la méthode ci-dessous)
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

        if (!$cycleEnCours) {
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

    /* -------------------------------------------------------------------------- */
    /* LOGIQUE                                   */
    /* -------------------------------------------------------------------------- */

    public function getIsDeletableAttribute(): bool
    {
        return !$this->cycles()->exists() 
            && !$this->depots()->exists() 
            && !$this->retraits()->exists()
            && !$this->credits()->exists();
    }

    public function allLinkedCarnets()
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
        return round($this->allLinkedCarnets()->sum(fn($c) => $c->availableSavings()), 2);
    }

    public function withdrawableGuarantee(): float
    {
        return round($this->allLinkedCarnets()->sum(fn($c) => $c->terminalWithdrawableSavings()), 2);
    }

    protected static function booted()
    {
        static::creating(function ($carnet) {
            if (empty($carnet->numero)) {
                $count = self::count(); 
                $idPart = 1000 + $count + 1;
                if ($carnet->type === 'tontine') {
                    $carnet->numero = (string)$idPart;
                } else {
                    $client = Client::find($carnet->client_id);
                    $initialeNom = strtoupper(mb_substr($client->nom ?? 'C', 0, 1));
                    $initialePrenom = strtoupper(mb_substr($client->prenom ?? 'X', 0, 1));
                    $carnet->numero = "{$initialeNom}{$idPart}{$initialePrenom}";
                }
            }
        });

        static::deleting(function ($carnet) {
            if (!$carnet->is_deletable) {
                throw new \Exception("Action impossible : Ce carnet contient des transactions actives.");
            }
        });
    }
}