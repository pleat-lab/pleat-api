<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Gyozas' => 1,
            'Bao' => 2,
            'Caldos' => 3,
            'Para acompañar' => 4,
            'Bebidas' => 5,
        ];

        foreach ($categories as $name => $position) {
            // updateOrCreate permite correr el seeder más de una vez sin duplicar.
            Category::updateOrCreate(['name' => $name], ['position' => $position]);
        }
    }
}
