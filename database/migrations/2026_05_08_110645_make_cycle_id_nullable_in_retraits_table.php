<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retraits', function (Blueprint $table) {
            // On rend la colonne nullable pour permettre les retraits sur compte épargne
            $table->unsignedBigInteger('cycle_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('retraits', function (Blueprint $table) {
            $table->unsignedBigInteger('cycle_id')->nullable(false)->change();
        });
    }
};