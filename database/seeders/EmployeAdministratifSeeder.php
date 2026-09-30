<?php
namespace Database\Seeders;

use App\Models\EmployeAdministratif;
use App\Models\Fonction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EmployeAdministratifSeeder extends Seeder
{
    public function run()
    {
        // S'assurer qu'il y a des fonctions en base avant de créer les employés
        $fonctions = Fonction::all();

        if ($fonctions->isEmpty()) {
            $this->call(FonctionSeeder::class);
            $fonctions = Fonction::all();
        }

        $noms        = ['ADJOHO', 'KODJO', 'AGBEMEBIA', 'MENSAH', 'LAWSON', 'TCHALLA', 'GNassingbe', 'KPAKPO', 'AKAKPO', 'DOSSOU'];
        $prenomsList = ['Essi Amouzou', 'Kossi', 'Afi', 'Kodjo', 'Ayélé', 'Kokou', 'Komi', 'Mawuko', 'Akouavi', 'Koffi'];
        $quartiers   = ['Vakpossito', 'Adidogomé', 'Agoè', 'Tokoin', 'Bè', 'Hédzranawoé', 'Lomé-Port', 'Kegué', 'Muzak', 'Cacaveli'];

        for ($i = 1; $i <= 10; $i++) {
            $nom      = $noms[array_rand($noms)];
            $prenom   = $prenomsList[array_rand($prenomsList)];
            $fonction = $fonctions->random();

            // Alterne entre salaire de base par défaut de la fonction ou un salaire personnalisé/négocié
            $salairePersonnalise = ($i % 3 == 0) ? null : ($fonction->salaire_base_defaut + (rand(0, 3) * 5000));

            EmployeAdministratif::firstOrCreate(
                ['telephone' => '+2289' . rand(10, 99) . rand(10, 99) . rand(10, 99) . rand(10, 99)],
                [
                    'nom'                       => $nom,
                    'prenoms'                   => $prenom . ' ' . $i,
                    'sexe'                      => ($i % 2 == 0) ? 'F' : 'M',
                    'date_naissance'            => Carbon::now()->subYears(rand(22, 45))->subMonths(rand(1, 12)),
                    'lieu_naissance'            => 'Lomé',
                    'email'                     => strtolower($nom) . '.' . strtolower(Str::slug($prenom)) . $i . '@example.com',
                    'adresse'                   => $quartiers[array_rand($quartiers)] . ' – Lomé',
                    'fonction_id'               => $fonction->id,
                    'salaire_base'              => $salairePersonnalise,
                    'statut_contrat'            => ($i <= 3) ? 'confirme' : 'essai', // Quelques-uns en CDI/confirmé, d'autres en essai
                    'date_embauche'             => Carbon::now()->subMonths(rand(1, 18)),
                    'piece_identite'            => 'CNI-' . rand(10000000, 99999999),
                    'contact_urgence_nom'       => 'Proche de ' . $nom,
                    'contact_urgence_telephone' => '+2289' . rand(70, 79) . rand(10, 99) . rand(10, 99) . rand(10, 99),
                    'actif'                     => true,
                ]
            );
        }
    }
}
