<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authentication_log', function (Blueprint $table) {
            $table->id();
            $table->morphs('authenticatable');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('login_at')->nullable();
            $table->timestamp('logout_at')->nullable();
            $table->boolean('login_successful')->default(false);
            $table->text('cleared_by_user')->nullable();
            $table->json('location')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authentication_log');
    }
};
