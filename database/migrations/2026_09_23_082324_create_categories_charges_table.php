<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories_charges', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();                                                          // ULID unique pour sécuriser les URLs
            $table->foreignId('types_charge_id')->constrained('types_charges')->onDelete('cascade'); // Lien vers le type macro
            $table->string('libelle');                                                               // Ex: Loyers, Facture CEET, Salaires de base, etc.
            $table->string('code_analytique')->nullable();                                           // Ex: Code SYSCOHADA (605, 611, etc.)
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true); // Pour activer/désactiver la catégorie
            $table->timestamps();
        });

        // Récupération des IDs des types insérés précédemment pour faire les liaisons propres
        $idExploitation = DB::table('types_charges')->where('code', 'EXPLOITATION')->value('id');
        $idPersonnel    = DB::table('types_charges')->where('code', 'PERSONNEL')->value('id');
        $idExceptionnel = DB::table('types_charges')->where('code', 'EXCEPTIONNEL')->value('id');

        // Insertion des catégories par défaut
        DB::table('categories_charges')->insert([
            // --- Catégories rattachées à EXPLOITATION ---
            [
                'ulid'            => (string) \Illuminate\Support\Str::ulid(),
                'types_charge_id' => $idExploitation,
                'libelle'         => 'Loyers des locaux et agences',
                'code_analytique' => '6131',
                'description'     => 'Frais de location du siège et des différentes agences de terrain',
                'actif'           => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'ulid'            => (string) \Illuminate\Support\Str::ulid(),
                'types_charge_id' => $idExploitation,
                'libelle'         => 'Eau et Électricité (CEET / TDE)',
                'code_analytique' => '6052',
                'description'     => 'Factures d’eau, d’électricité et fournitures de fluides',
                'actif'           => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'ulid'            => (string) \Illuminate\Support\Str::ulid(),
                'types_charge_id' => $idExploitation,
                'libelle'         => 'Achats et Confection de carnets',
                'code_analytique' => '6021',
                'description'     => 'Frais d’impression des carnets de tontines et d’épargne',
                'actif'           => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'ulid'            => (string) \Illuminate\Support\Str::ulid(),
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
                'ulid'            => (string) \Illuminate\Support\Str::ulid(),
                'types_charge_id' => $idPersonnel,
                'libelle'         => 'Salaires et Traitements bruts',
                'code_analytique' => '6611',
                'description'     => 'Rémunération mensuelle fixe du personnel',
                'actif'           => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'ulid'            => (string) \Illuminate\Support\Str::ulid(),
                'types_charge_id' => $idPersonnel,
                'libelle'         => 'Primes et Commissions de performance',
                'code_analytique' => '6612',
                'description'     => 'Commissions versées aux agents de terrain et primes sur objectifs',
                'actif'           => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'ulid'            => (string) \Illuminate\Support\Str::ulid(),
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
                'ulid'            => (string) \Illuminate\Support\Str::ulid(),
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

    public function down(): void
    {
        Schema::dropIfExists('categories_charges');
    }
};
