<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_type_id')->constrained('credit_types')->onDelete('cascade');
            $table->decimal('frais_dossier_defaut', 15, 2)->default(0);
            $table->string('nom'); 
            $table->string('type_carnet_requis');
            $table->string('code')->unique(); // Ex: CRE-TONT
            $table->decimal('taux_interet_defaut', 5, 2); // Ex: 1.50
            $table->integer('duree_max_mois')->default(12);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credit_products');
    }
};
