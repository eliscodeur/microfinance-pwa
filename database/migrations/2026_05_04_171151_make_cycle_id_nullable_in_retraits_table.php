<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retraits', function (Blueprint $table) {
            // On change la colonne pour qu'elle accepte NULL
            $table->foreignId('cycle_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('retraits', function (Blueprint $table) {
            // En cas de retour en arrière, on remet en NOT NULL 
            // (Attention: cela échouera s'il y a des NULL en base)
            $table->foreignId('cycle_id')->nullable(false)->change();
        });
    }
};