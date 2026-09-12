<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajout de user_id sur la table cycles (optionnel, si un cycle peut être ouvert au bureau)
        Schema::table('cycles', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('agent_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Ajout de user_id sur la table collectes (pour savoir quel utilisateur a encaissé au bureau)
        Schema::table('collectes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('agent_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('collectes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
