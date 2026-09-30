<?php
namespace Database\Seeders;

use App\Models\Fonction;
use Illuminate\Database\Seeder;

class FonctionSeeder extends Seeder
{
    public function run()
    {
        $fonctions = [
            [
                'libelle'             => 'Secrétaire / Assistante de Direction',
                'salaire_base_defaut' => 35000,
                'description'         => 'Gestion de l’accueil, du secrétariat et des tâches administratives courantes.',
            ],
            [
                'libelle'             => 'Comptable / Assistant Comptable',
                'salaire_base_defaut' => 50000,
                'description'         => 'Suivi des écritures comptables, états de caisse et gestion financière.',
            ],
            [
                'libelle'             => 'Gérante / Responsable Administrative',
                'salaire_base_defaut' => 60000,
                'description'         => 'Supervision générale de la gestion administrative et du chiffre d’affaires.',
            ],
        ];

        foreach ($fonctions as $fonction) {
            Fonction::firstOrCreate(
                ['libelle' => $fonction['libelle']],
                $fonction
            );
        }
    }
}
