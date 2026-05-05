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
    /*                                 RELATIONS                                  */
    /* -------------------------------------------------------------------------- */

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function depots()
    {
        return $this->hasMany(Depot::class);
    }

    public function retraits() 
    {
        return $this->hasMany(Retrait::class);
    }

    public function cycles()
    {
        return $this->hasMany(Cycle::class);
    }

    public function collectes() 
    {
        return $this->hasManyThrough(Collecte::class, Cycle::class);
    }

    public function credits()
    {
        return $this->hasMany(Credit::class);
    }

    public function categoryTontine()
    {
        return $this->belongsTo(CategoryTontine::class, 'category_tontine_id');
    }

    public function parent()
    {
        return $this->belongsTo(Carnet::class, 'parent_id');
    }

    public function enfants() 
    {
        return $this->hasMany(Carnet::class, 'parent_id');
    }

    /* -------------------------------------------------------------------------- */
    /*                                 ATTRIBUTES                                 */
    /* -------------------------------------------------------------------------- */

    /**
     * Solde disponible pour les carnets de type "compte" (Épargne)
     */
    public function getSoldeDisponibleAttribute(): float
    {
        // On utilise les relations pour profiter de l'Eager Loading du Controller
        $totalDepots = (float) $this->depots->sum('montant');
        $totalRetraits = (float) $this->retraits->sum('montant_total');
        
        return round($totalDepots - $totalRetraits, 2);
    }

    /**
     * Argent des cycles de tontine terminés qui n'a pas encore été retiré
     */
    /**
     * Calcule le solde disponible uniquement pour les cycles achevés 
     * et non encore décaissés (Somme des collectes - 1 mise).
     */
    public function getSoldeTontineNonRetireAttribute(): float
    {
        // On ne cible que les cycles TERMINÉS et NON RETIRÉS
        $cyclesPretsPourRetrait = $this->cycles
            ->where('statut', 'termine')
            ->whereNull('retire_at');

        return (float) $cyclesPretsPourRetrait->reduce(function ($carry, $cycle) {
            // Somme brute des collectes pour ce cycle
            $totalCollectes = $cycle->collectes->sum('montant');
            
            // Déduction systématique de la commission (1 mise journalière)
            $commission = (float) $cycle->montant_journalier;
            
            return $carry + ($totalCollectes - $commission);
        }, 0);
    }
    /**
     * Vérifie si le carnet peut être supprimé (Admin)
     */
    public function getIsDeletableAttribute(): bool
    {
        return !$this->cycles()->exists() 
            && !$this->depots()->exists() 
            && !$this->retraits()->exists()
            && !$this->credits()->exists();
    }

    /**
     * Calcul du nombre total de pointages pour un carnet tontine
     */
    public function totalPointages(): int
    {
        return (int) $this->collectes()->sum('pointage');
    }

    /**
     * Épargne disponible dans les cycles actifs
     */
    public function activeCycleSavings(): float
    {
        $cycle = $this->cycles()->where('statut', 'en_cours')->first();

        if (!$cycle) {
            return 0.0;
        }

        return (float) $cycle->collectes()->sum('montant');
    }

    /**
     * Épargne retirable des cycles terminés non encore retirés
     */
    public function terminalWithdrawableSavings(): float
    {
        $cycles = $this->cycles()
            ->where('statut', 'termine')
            ->whereNull('retire_at')
            ->get();

        return round($cycles->reduce(function ($carry, $cycle) {
            $totalCollectes = (float) $cycle->collectes()->sum('montant');
            $commission = (float) ($cycle->montant_journalier ?? 0);
            return $carry + max(0, $totalCollectes - $commission);
        }, 0.0), 2);
    }

    /**
     * Total de l'épargne disponible (actifs + terminés)
     */
    public function availableSavings(): float
    {
        return round($this->activeCycleSavings() + $this->terminalWithdrawableSavings(), 2);
    }

    /**
     * Tous les carnets liés (parent, enfants, self)
     */
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

    /**
     * Base de garantie : somme des épargnes disponibles de tous les carnets liés
     */
    public function guaranteeBase(): float
    {
        return round($this->allLinkedCarnets()->sum(function (Carnet $carnet) {
            return $carnet->availableSavings();
        }), 2);
    }

    /**
     * Garantie retirable : somme des épargnes retiables de tous les carnets liés
     */
    public function withdrawableGuarantee(): float
    {
        return round($this->allLinkedCarnets()->sum(function (Carnet $carnet) {
            return $carnet->terminalWithdrawableSavings();
        }), 2);
    }

    /* -------------------------------------------------------------------------- */
    /*                                   LOGIQUE                                  */
    /* -------------------------------------------------------------------------- */

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

    // App\Models\Collecte.php
    public function user()
    {
        return $this->belongsTo(User::class, 'agent_id'); 
        // Remplace 'user_id' par le nom de la colonne qui stocke l'ID de l'agent
    }

    // Garde tes méthodes de calcul de garanties (guaranteeBase, etc.) si tu les utilises pour les crédits
}