<?php

namespace Database\Seeders;

use App\Models\CreditType;
use App\Models\CreditProduct;
use App\Models\CreditObject;
use Illuminate\Database\Seeder;

class CreditSystemSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CRÉATION DES TYPES DE CRÉDIT (La mécanique structurelle)
        $typeAmortissable = CreditType::create([
            'nom'  => 'Prêt Amortissable Classique',
            'code' => 'PRET_AMORT',
        ]);

        $typeDecouvert = CreditType::create([
            'nom'  => 'Ligne de Crédit / Découvert',
            'code' => 'DECOUVERT',
        ]);


        // 2. CRÉATION DES OBRETS / MOTIFS DE CRÉDIT
        $objCommerce   = CreditObject::create(['nom' => 'Achat de marchandises / Stock', 'secteur_activite' => 'Commerce']);
        $objArtisanat  = CreditObject::create(['nom' => 'Matériel / Équipement professionnel', 'secteur_activite' => 'Artisanat']);
        $objScolaire   = CreditObject::create(['nom' => 'Frais Scolaires / Éducation', 'secteur_activite' => 'Social']);
        $objAgricole   = CreditObject::create(['nom' => 'Intrants / Activités Agricoles', 'secteur_activite' => 'Agriculture']);
        $objSante      = CreditObject::create(['nom' => 'Urgence Médicale / Santé', 'secteur_activite' => 'Social']);
        $objAutre      = CreditObject::create(['nom' => 'Autre motif à préciser', 'secteur_activite' => 'Divers']);


        // 3. CRÉATION DES PRODUITS DE CRÉDIT ET ASSOCATIONS DYNAMIQUES (Table pivot)

        // Produit A : Crédit Tontine Commerce
        $produitTontine = CreditProduct::create([
            'credit_type_id'       => $typeAmortissable->id,
            'nom'                  => 'Crédit Tontine Commerce',
            'code'                 => 'CRE-TONT',
            'type_carnet_requis'   => 'tontine',
            'frais_dossier_defaut' => 3000.00,
            'taux_interet_defaut'  => 2.00, // 2% 
            'duree_max_mois'       => 6,
        ]);
        // Ce produit n'accepte que le commerce, le matériel ou "autre"
        $produitTontine->objects()->attach([$objCommerce->id, $objArtisanat->id, $objAutre->id]);


        // Produit B : Prêt Scolaire & Éducation
        $produitScolaire = CreditProduct::create([
            'credit_type_id'       => $typeAmortissable->id,
            'nom'                  => 'Prêt Scolaire',
            'code'                 => 'CRE-SCO',
            'type_carnet_requis'   => 'compte',
            'frais_dossier_defaut' => 2000.00,
            'taux_interet_defaut'  => 1.50, // 1.5%
            'duree_max_mois'       => 10,
        ]);
        // Ce produit n'accepte que les frais scolaires et "autre"
        $produitScolaire->objects()->attach([$objScolaire->id, $objAutre->id]);


        // Produit C : Découvert Flash
        $produitDecouvert = CreditProduct::create([
            'credit_type_id'       => $typeDecouvert->id,
            'nom'                  => 'Découvert Flash Rapide',
            'code'                 => 'DEC-FLASH',
            'frais_dossier_defaut' => 4000.00,
            'taux_interet_defaut'  => 5.00, // 5%
            'duree_max_mois'       => 3,
            'type_carnet_requis'   => 'compte',
        ]);
        // Le découvert est polyvalent : commerce (besoin urgent de cash), santé ou autre
        $produitDecouvert->objects()->attach([$objCommerce->id, $objSante->id, $objAutre->id]);
    }
}