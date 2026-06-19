<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            // 1. Ajout des relations manquantes
            $table->foreignId('credit_product_id')->after('admin_id')->constrained('credit_products');
            $table->foreignId('credit_object_id')->nullable()->after('credit_product_id')->constrained('credit_objects');
            $table->foreignId('cycle_id')->nullable()->after('carnet_id'); // Trace pour la tontine terrain
            $table->string('type_support')->default('compte')->after('credit_object_id'); // compte ou tontine

            // 2. Ajout des nouveaux paramètres du formulaire
            $table->integer('differe')->default(0)->after('nombre_echeances');
            $table->decimal('frais_dossier', 15, 2)->default(0.00)->after('differe');

            // 3. Correction orthographique du taux manuel
            $table->renameColumn('taux_manuelle', 'taux_manuel');

            // 4. Suppression du champ redondant 'type'
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            // Revenir en arrière en cas de problème (rollback)
            $table->string('type')->nullable()->after('taux_manuel');
            $table->renameColumn('taux_manuel', 'taux_manuelle');
            
            $table->dropColumn([
                'credit_product_id', 
                'credit_object_id', 
                'cycle_id', 
                'type_support', 
                'differe', 
                'frais_dossier'
            ]);
        });
    }
};