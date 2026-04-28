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
        Schema::table('carnets', function (Blueprint $table) {
            // 1. Le Type (Tontine ou Compte)
            // On vérifie si la colonne n'existe pas déjà avant de l'ajouter
            if (!Schema::hasColumn('carnets', 'type')) {
                $table->enum('type', ['tontine', 'compte'])->default('tontine')->after('client_id');
            }

            // 2. Clé vers la catégorie de tontine
            $table->unsignedBigInteger('category_tontine_id')->nullable()->after('type');
            $table->foreign('category_tontine_id')->references('id')->on('categories_tontine');

            // 3. Clé pour lier un compte à une tontine (Parent)
            $table->unsignedBigInteger('parent_id')->nullable()->after('category_tontine_id');
            $table->foreign('parent_id')->references('id')->on('carnets')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('carnets', function (Blueprint $table) {
            $table->dropForeign(['category_tontine_id']);
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['type', 'category_tontine_id', 'parent_id']);
        });
    }
};
