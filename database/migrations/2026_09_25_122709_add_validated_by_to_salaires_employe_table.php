<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salaires_employe', function (Blueprint $table) {

            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table('salaires_employe', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn('validated_by');
        });
    }
};
