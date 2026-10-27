<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoriesChargesSeeder extends Seeder
{
    public function run(): void
    {
        // Récupération sécurisée des IDs maintenant que les tables sont créées et remplies
        $idExploitation = DB::table('types_charges')->where('code', 'EXPLOITATION')->value('id');
        $idPersonnel    = DB::table('types_charges')->where('code', 'PERSONNEL')->value('id');
        $idExceptionnel = DB::table('types_charges')->where('code', 'EXCEPTIONNEL')->value('id');

        DB::table('categories_charges')->insert([
            // --- Catégories rattachées à EXPLOITATION ---
            [
                'ulid'            => (string) Str::ulid(),
                'types_charge_id' => $idExploitation,
                'libelle'         => 'Loyers des locaux et agences',
                'code_analytique' => '6131',
                'description'     => 'Frais de location du siège et des différentes agences de terrain',
                'actif'           => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'ulid'            => (string) Str::ulid(),
                'types_charge_id' => $idExploitation,
                'libelle'         => 'Eau et Électricité (CEET / TDE)',
                'code_analytique' => '6052',
                'description'     => 'Factures d’eau, d’électricité et fournitures de fluides',
                'actif'           => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'ulid'            => (string) Str::ulid(),
                'types_charge_id' => $idExploitation,
                'libelle'         => 'Achats et Confection de carnets',
                'code_analytique' => '6021',
                'description'     => 'Frais d’impression des carnets de tontines et d’épargne',
                'actif'           => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'ulid'            => (string) Str::ulid(),
                'types_charge_id' => $idExploitation,
                'libelle'         => 'Fournitures de bureau et petits matériels',
                'code_analytique' => '6041',
                'description'     => 'Papeterie, stylos, registres et consommables informatiques',
                'actif'           => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],

            // --- Catégories rattachées à PERSONNEL ---
            [
                'ulid'            => (string) Str::ulid(),
                'types_charge_id' => $idPersonnel,
                'libelle'         => 'Salaires et Traitements bruts',
                'code_analytique' => '6611',
                'description'     => 'Rémunération mensuelle fixe du personnel',
                'actif'           => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'ulid'            => (string) Str::ulid(),
                'types_charge_id' => $idPersonnel,
                'libelle'         => 'Primes et Commissions de performance',
                'code_analytique' => '6612',
                'description'     => 'Commissions versées aux agents de terrain et primes sur objectifs',
                'actif'           => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'ulid'            => (string) Str::ulid(),
                'types_charge_id' => $idPersonnel,
                'libelle'         => 'Charges sociales (CNSS)',
                'code_analytique' => '6641',
                'description'     => 'Cotisations patronales de sécurité sociale',
                'actif'           => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],

            // --- Catégories rattachées à EXCEPTIONNEL ---
            [
                'ulid'            => (string) Str::ulid(),
                'types_charge_id' => $idExceptionnel,
                'libelle'         => 'Dons et Actions caritatives',
                'code_analytique' => '6581',
                'description'     => 'Soutiens financiers exceptionnels, parrainages ou dons sociaux',
                'actif'           => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);
    }
}
