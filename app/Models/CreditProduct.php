<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CreditProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_type_id',
        'nom',
        'code',
        'taux_interet_defaut',
        'type_carnet_requis',
        'frais_dossier_defaut', // <-- Ajouté ici
        'duree_max_mois',
        'is_active',
    ];

    /**
     * Un produit appartient à un type de crédit spécifique.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CreditType::class, 'credit_type_id');
    }

    /**
     * Les motifs/objets de crédit autorisés pour ce produit (Relation Pivot).
     */
    public function objects(): BelongsToMany
    {
        return $this->belongsToMany(CreditObject::class);
    }
    public function creditObjects(): BelongsToMany
    {
        return $this->belongsToMany(
            CreditObject::class, 
            'credit_object_credit_product', 
            'credit_product_id', 
            'credit_object_id'
        )->withTimestamps(); // Optionnel : si vous gérez created_at/updated_at dans le pivot (actuellement à NULL chez vous)
    }
}