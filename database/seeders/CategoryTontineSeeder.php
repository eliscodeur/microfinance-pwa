<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoryTontineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\CategoryTontine::insert([
            ['libelle' => 'Quinzaine', 'nombre_cycles' => 15],
            ['libelle' => 'Tontine 3 mois', 'nombre_cycles' => 3],
            ['libelle' => 'Tontine 6 mois', 'nombre_cycles' => 6],
            ['libelle' => 'Tontine 12 mois', 'nombre_cycles' => 12],
        ]);
    }
}
