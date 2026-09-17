<?php
namespace Database\Seeders;

use App\Models\CreditObject;
use App\Models\CreditProduct;
use App\Models\CreditType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

// <-- Ne pas oublier d'importer Str

class CreditSystemSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CRÉATION DES TYPES DE CRÉDIT
        $typeAmortissable = CreditType::firstOrCreate(
            ['code' => 'PRET_AMORT'],
            [
                'ulid'      => strtolower((string) Str::ulid()),
                'nom'       => 'Prêt Amortissable Classique',
                'is_active' => true,
            ]
        );

        $typeDecouvert = CreditType::firstOrCreate(
            ['code' => 'DECOUVERT'],
            [
                'ulid'      => strtolower((string) Str::ulid()),
                'nom'       => 'Ligne de Crédit / Découvert',
                'is_active' => true,
            ]
        );

        // 2. CRÉATION DES OBJETS / MOTIFS DE CRÉDIT
        $objCommerce = CreditObject::firstOrCreate(
            ['nom' => 'Achat de marchandises / Stock'],
            [
                'ulid'             => strtolower((string) Str::ulid()),
                'secteur_activite' => 'Commerce',
            ]
        );
        $objArtisanat = CreditObject::firstOrCreate(
            ['nom' => 'Matériel / Équipement professionnel'],
            [
                'ulid'             => strtolower((string) Str::ulid()),
                'secteur_activite' => 'Artisanat',
            ]
        );
        $objScolaire = CreditObject::firstOrCreate(
            ['nom' => 'Frais Scolaires / Éducation'],
            [
                'ulid'             => strtolower((string) Str::ulid()),
                'secteur_activite' => 'Social',
            ]
        );
        $objAgricole = CreditObject::firstOrCreate(
            ['nom' => 'Intrants / Activités Agricoles'],
            [
                'ulid'             => strtolower((string) Str::ulid()),
                'secteur_activite' => 'Agriculture',
            ]
        );
        $objSante = CreditObject::firstOrCreate(
            ['nom' => 'Urgence Médicale / Santé'],
            [
                'ulid'             => strtolower((string) Str::ulid()),
                'secteur_activite' => 'Social',
            ]
        );
        $objAutre = CreditObject::firstOrCreate(
            ['nom' => 'Autre motif à préciser'],
            [
                'ulid'             => strtolower((string) Str::ulid()),
                'secteur_activite' => 'Divers',
            ]
        );

        // 3. CRÉATION DES PRODUITS DE CRÉDIT
        $produitTontine = CreditProduct::firstOrCreate(
            ['code' => 'CRE-TONT'],
            [
                'ulid'                 => strtolower((string) Str::ulid()),
                'credit_type_id'       => $typeAmortissable->id,
                'nom'                  => 'Crédit Tontine Commerce',
                'type_carnet_requis'   => 'tontine',
                'frais_dossier_defaut' => 3000.00,
                'taux_interet_defaut'  => 2.00,
                'duree_max_mois'       => 6,
            ]
        );
        $produitTontine->objects()->sync([$objCommerce->id, $objArtisanat->id, $objAutre->id]);

        $produitScolaire = CreditProduct::firstOrCreate(
            ['code' => 'CRE-SCO'],
            [
                'ulid'                 => strtolower((string) Str::ulid()),
                'credit_type_id'       => $typeAmortissable->id,
                'nom'                  => 'Prêt Scolaire',
                'type_carnet_requis'   => 'compte',
                'frais_dossier_defaut' => 2000.00,
                'taux_interet_defaut'  => 1.50,
                'duree_max_mois'       => 10,
            ]
        );
        $produitScolaire->objects()->sync([$objScolaire->id, $objAutre->id]);

        $produitDecouvert = CreditProduct::firstOrCreate(
            ['code' => 'DEC-FLASH'],
            [
                'ulid'                 => strtolower((string) Str::ulid()),
                'credit_type_id'       => $typeDecouvert->id,
                'nom'                  => 'Découvert Flash Rapide',
                'frais_dossier_defaut' => 4000.00,
                'taux_interet_defaut'  => 5.00,
                'duree_max_mois'       => 3,
                'type_carnet_requis'   => 'compte',
            ]
        );
        $produitDecouvert->objects()->sync([$objCommerce->id, $objSante->id, $objAutre->id]);
    }
}
