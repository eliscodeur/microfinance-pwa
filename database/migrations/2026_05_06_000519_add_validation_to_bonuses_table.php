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
        Schema::table('bonuses', function (Blueprint $table) {
            $table->foreignId('cycle_id')->nullable()->after('agent_id')->constrained('cycles')->onDelete('set null');
            $table->boolean('commission_genere')->default(false)->after('motif');
            $table->foreignId('validated_by')->nullable()->after('admin_id')->references('id')->on('admins');
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            
            // Pour rendre admin_id nullable si ce n'est pas le cas :
            $table->foreignId('admin_id')->nullable()->change(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bonuses', function (Blueprint $table) {
            //
        });
    }
};
