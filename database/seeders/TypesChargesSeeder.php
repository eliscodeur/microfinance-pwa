<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TypesChargesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('types_charges')->insert([
            [
                'ulid'       => (string) Str::ulid(),
                'code'       => 'EXPLOITATION',
                'libelle'    => 'Charges d\'exploitation',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ulid'       => (string) Str::ulid(),
                'code'       => 'PERSONNEL',
                'libelle'    => 'Charges de personnel',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ulid'       => (string) Str::ulid(),
                'code'       => 'EXCEPTIONNEL',
                'libelle'    => 'Charges exceptionnelles',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
