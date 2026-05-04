<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    // Un carnet appartient à un client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function getIsViergeAttribute()
    {
        // 1. Pour la Tontine : aucun cycle commencé
        if ($this->cycles()->exists()) return false;

        // 2. Pour l'Épargne/Compte : aucun dépôt ni retrait
        if ($this->depots()->exists()) return false;
        if ($this->retraits()->exists()) return false;

        // 3. Sécurité supplémentaire : aucune collecte (même orpheline)
        if ($this->collectes()->exists()) return false;

        return true;
    }
    protected static function booted()
    {
        static::creating(function ($carnet) {
            if (empty($carnet->numero)) {
                // OPTION A : On compte combien il y a de carnets pour définir le rang
                // Si tu as 0 carnet, le prochain est 1. Si tu en as 5, le prochain est 6.
                $count = self::count(); 
                $nextNumber = $count + 1;
                
                // On construit la partie numérique (1000 + le rang)
                // Ainsi, le premier sera toujours 1001, le second 1002, etc.
                $idPart = 1000 + $nextNumber;

                if ($carnet->type === 'tontine') {
                    $carnet->numero = (string)$idPart;
                } 
                else if ($carnet->type === 'compte') {
                    $client = \App\Models\Client::find($carnet->client_id);
                    
                    if ($client) {
                        $initialeNom = strtoupper(mb_substr($client->nom, 0, 1));
                        $initialePrenom = strtoupper(mb_substr($client->prenom ?? 'X', 0, 1));
                        $carnet->numero = "{$initialeNom}{$idPart}{$initialePrenom}";
                    } else {
                        $carnet->numero = "C" . $idPart;
                    }
                }
            }
        });
        static::deleting(function ($carnet) {
            if (!$carnet->is_deletable) {
                // On lance une exception ou on retourne false pour stopper Laravel
                throw new \Exception("Action impossible : Ce carnet contient des transactions (cycles, dépôts ou collectes).");
            }
        });
    }
    public function cycles()
    {
        return $this->hasMany(Cycle::class);
    }

    public function collectes() {
        return $this->hasManyThrough(Collecte::class, Cycle::class);
    }

    public function totalPointages(): int
    {
        return (int) $this->collectes()->sum('pointage');
    }

    public function retraits() {
        return $this->hasMany(Retrait::class); // Ou la table où tu stockes les sorties d'argent
    }

    public function categoryTontine()
    {
        return $this->belongsTo(CategoryTontine::class, 'category_tontine_id');
    }

    public function parent()
    {
        return $this->belongsTo(Carnet::class, 'parent_id');
    }

    public function enfants() // Pour voir les comptes liés à une tontine
    {
        return $this->hasMany(Carnet::class, 'parent_id');
    }

    public function activeCycleSavings(): float
    {
        $cycle = $this->cycles()->where('statut', 'en_cours')->first();

        if (!$cycle) {
            return 0.0;
        }

        return (float) $cycle->collectes()->sum('montant');
    }

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

    public function availableSavings(): float
    {
        return round($this->activeCycleSavings() + $this->terminalWithdrawableSavings(), 2);
    }

    public function allLinkedCarnets()
    {
        $collection = collect([$this]);

        if ($this->parent) {
            $collection->push($this->parent);
        }

        if ($this->type === 'tontine') {
            $collection = $collection->merge($this->enfants);
        }

        return $collection->unique('id');
    }

    public function guaranteeBase(): float
    {
        return round($this->allLinkedCarnets()->sum(function (Carnet $carnet) {
            return $carnet->availableSavings();
        }), 2);
    }

    public function withdrawableGuarantee(): float
    {
        return round($this->allLinkedCarnets()->sum(function (Carnet $carnet) {
            return $carnet->terminalWithdrawableSavings();
        }), 2);
    }
}