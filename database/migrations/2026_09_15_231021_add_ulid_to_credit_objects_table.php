<?php
use App\Models\CreditObject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajouter la colonne nullable
        Schema::table('credit_objects', function (Blueprint $table) {
            $table->ulid('ulid')->nullable()->after('id');
        });

        // 2. Remplir les enregistrements existants
        CreditObject::whereNull('ulid')->get()->each(function ($item) {
            $item->ulid = strtolower((string) Str::ulid());
            $item->save();
        });

        // 3. Rendre la colonne unique et non nullable
        Schema::table('credit_objects', function (Blueprint $table) {
            $table->ulid('ulid')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('credit_objects', function (Blueprint $table) {
            $table->dropColumn('ulid');
        });
    }
};
