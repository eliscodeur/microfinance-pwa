<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mouvements_caisses', function (Blueprint $table) {
            $table->foreignId('cash_session_id')
                ->nullable()
                ->after('user_id')
                ->constrained('cash_sessions')
                ->nullOnDelete();
            $table->index('cash_session_id');
        });

        $sessionCache = [];

        DB::table('mouvements_caisses')
            ->whereNull('cash_session_id')
            ->orderBy('id')
            ->chunkById(500, function ($movements) use (&$sessionCache) {
                foreach ($movements as $movement) {
                    if (! isset($sessionCache[$movement->user_id])) {
                        $sessionCache[$movement->user_id] = DB::table('cash_sessions')
                            ->where('user_id', $movement->user_id)
                            ->get(['id', 'date_ouverture', 'date_fermeture']);
                    }

                    $matchingSessions = $sessionCache[$movement->user_id]->filter(function ($session) use ($movement) {
                        return $session->date_ouverture <= $movement->date_mouvement
                            && ($session->date_fermeture === null || $session->date_fermeture >= $movement->date_mouvement);
                    });

                    if ($matchingSessions->count() === 1) {
                        DB::table('mouvements_caisses')
                            ->where('id', $movement->id)
                            ->update(['cash_session_id' => $matchingSessions->first()->id]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('mouvements_caisses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_session_id');
        });
    }
};
