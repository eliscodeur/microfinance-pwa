<?php
use App\Models\CreditProduct;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajouter la colonne nullable
        Schema::table('credit_products', function (Blueprint $table) {
            $table->ulid('ulid')->nullable()->after('id');
        });

        // 2. Remplir les enregistrements existants
        CreditProduct::whereNull('ulid')->get()->each(function ($item) {
            $item->ulid = strtolower((string) Str::ulid());
            $item->save();
        });

        Schema::table('credit_products', function (Blueprint $table) {
            $table->ulid('ulid')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('credit_products', function (Blueprint $table) {
            $table->dropColumn('ulid');
        });
    }
};
