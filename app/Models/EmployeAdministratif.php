<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmployeAdministratif extends Model
{
    use HasFactory;

    protected $table = 'employe_administratifs';

    protected $fillable = [
        'ulid',
        'nom',
        'prenoms',
        'sexe',
        'date_naissance',
        'lieu_naissance',
        'telephone',
        'email',
        'adresse',
        'fonction_id',
        'salaire_base',
        'statut_contrat',
        'date_embauche',
        'piece_identite',
        'contact_urgence_nom',
        'contact_urgence_telephone',
        'actif',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_embauche'  => 'date',
        'actif'          => 'boolean',
    ];

    public function getRouteKeyName()
    {
        return 'ulid';
    }
    // Génération automatique d'un ULID lors de la création
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = strtolower((string) Str::ulid());
            }
        });
    }

    // Relation : Appartient à une fonction
    public function fonction()
    {
        return $this->belongsTo(Fonction::class, 'fonction_id');
    }

    // Accessor intelligent pour récupérer soit le salaire personnalisé, soit celui par défaut de la fonction
    public function getSalaireEffectifAttribute()
    {
        return $this->salaire_base ?? $this->fonction->salaire_base_defaut ?? 0;
    }

    public function getSalaireBase()
    {
        // 1. Si l'employé a un salaire propre défini et non nul
        if (! empty($this->salaire_base) && $this->salaire_base > 0) {
            return $this->salaire_base;
        }

        if (! empty($this->fonction_id)) {
            $fonction = \App\Models\Fonction::find($this->fonction_id);
            if ($fonction && ! empty($fonction->salaire_base_defaut)) {
                return $fonction->salaire_base_defaut;
            }
        }

        // 3. Valeur de secours si rien n'est trouvé
        return 0;
    }
}
