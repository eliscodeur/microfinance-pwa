<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {

            $table->ulid('ulid')->unique()->after('id');
            $table->timestamp('inclus_dans_salaire_at')->nullable()->after('validated_by');
        });
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropColumn(['ulid', 'inclus_dans_salaire_at']);
        });
    }
};
