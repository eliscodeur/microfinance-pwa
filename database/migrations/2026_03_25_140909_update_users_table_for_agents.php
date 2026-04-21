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
        Schema::table('users', function (Blueprint $table) {
            // On vérifie si username n'existe pas déjà avant de l'ajouter
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->nullable()->after('email');
            }

            // On vérifie si type n'existe pas déjà
            if (!Schema::hasColumn('users', 'type')) {
                $table->string('type')->default('admin')->after('username');
            }

            // On NE crée PAS role_id ici car l'erreur dit qu'il existe déjà !
            // Si tu veux quand même être sûr qu'il est bien placé :
            // $table->foreignId('role_id')->nullable()->change(); 

            // On vérifie si is_active n'existe pas déjà
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('password');
            }
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
