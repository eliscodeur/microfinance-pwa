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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->unique()->nullable()->after('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->foreignId('role_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_active')->default(true);
        });
        Schema::table('agents', function (Blueprint $table) {
    // Lien vers le compte utilisateur
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Le code de l'agent
            $table->string('code_agent')->unique(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
            Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']); // Supprime la contrainte
            $table->dropColumn('role_id');    // Supprime la colonne
        });
    }
};
