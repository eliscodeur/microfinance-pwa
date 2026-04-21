<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        // ÉTAPE 1 : Ajouter les colonnes de base sans contraintes
        Schema::table('agents', function (Blueprint $table) {
            if (!Schema::hasColumn('agents', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('agents', 'code_agent')) {
                $table->string('code_agent')->nullable()->after('user_id');
            }
        });

        // ÉTAPE 2 : Remplir les données vides pour éviter l'erreur "Duplicate"
        $agents = DB::table('agents')->get();
        foreach ($agents as $index => $agent) {
            DB::table('agents')->where('id', $agent->id)->update([
                'code_agent' => 'NNC-' . str_pad($index + 1, 5, '0', STR_PAD_LEFT)
            ]);
        }

        // ÉTAPE 3 : Appliquer les contraintes et supprimer le superflu
        Schema::table('agents', function (Blueprint $table) {
            // Supprimer email et password s'ils existent encore
            if (Schema::hasColumn('agents', 'email')) {
                $table->dropColumn(['email', 'password']);
            }
            
            // On rend le code unique maintenant qu'il est rempli
            $table->string('code_agent')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
