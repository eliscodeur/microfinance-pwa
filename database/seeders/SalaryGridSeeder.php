<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalaryGridSeeder extends Seeder
{
    public function run(): void
    {
        // Vider la table avant de insérer pour éviter les doublons lors des re-seed
        DB::table('salary_grids')->truncate();

        $grilles = [
            [
                'seuil_min'          => 40000,
                'seuil_max'          => 40000,
                'salaire_base'       => 30000,
                'commission_travail' => 0,
                'taux_carnet'        => 25.00,
            ],
            [
                'seuil_min'          => 50000,
                'seuil_max'          => 50000,
                'salaire_base'       => 35000,
                'commission_travail' => 2500,
                'taux_carnet'        => 25.00,
            ],
            [
                'seuil_min'          => 60000,
                'seuil_max'          => 70000,
                'salaire_base'       => 45000,
                'commission_travail' => 2500,
                'taux_carnet'        => 25.00,
            ],
            [
                'seuil_min'          => 80000,
                'seuil_max'          => 90000,
                'salaire_base'       => 55000,
                'commission_travail' => 2500,
                'taux_carnet'        => 25.00,
            ],
            [
                'seuil_min'          => 100000,
                'seuil_max'          => 100000,
                'salaire_base'       => 65000,
                'commission_travail' => 5000,
                'taux_carnet'        => 25.00,
            ],
            [
                'seuil_min'          => 120000,
                'seuil_max'          => 130000,
                'salaire_base'       => 80000,
                'commission_travail' => 5000,
                'taux_carnet'        => 25.00,
            ],
            [
                'seuil_min'          => 140000,
                'seuil_max'          => 140000,
                'salaire_base'       => 90000,
                'commission_travail' => 5000,
                'taux_carnet'        => 25.00,
            ],
            [
                'seuil_min'          => 150000,
                'seuil_max'          => 150000,
                'salaire_base'       => 95000,
                'commission_travail' => 5000,
                'taux_carnet'        => 25.00,
            ],
            [
                'seuil_min'          => 160000,
                'seuil_max'          => 160000,
                'salaire_base'       => 105000,
                'commission_travail' => 5000,
                'taux_carnet'        => 25.00,
            ],
            [
                'seuil_min'          => 180000,
                'seuil_max'          => 190000,
                'salaire_base'       => 115000,
                'commission_travail' => 10000,
                'taux_carnet'        => 25.00,
            ],
            [
                'seuil_min'          => 200000,
                'seuil_max'          => 200000,
                'salaire_base'       => 125000,
                'commission_travail' => 10000,
                'taux_carnet'        => 25.00,
            ],
        ];

        foreach ($grilles as $grille) {
            DB::table('salary_grids')->insert([
                'ulid'               => strtolower((string) Str::ulid()),
                'seuil_min'          => $grille['seuil_min'],
                'seuil_max'          => $grille['seuil_max'],
                'salaire_base'       => $grille['salaire_base'],
                'commission_travail' => $grille['commission_travail'],
                'taux_carnet'        => $grille['taux_carnet'],
                'date_debut'         => now()->startOfYear(), // Période active par défaut
                'date_fin'           => null,
                'est_actif'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }
}
