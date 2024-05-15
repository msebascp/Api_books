<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Ciencia Ficción',
            'Fantasía',
            'Terror',
            'Romance',
            'Aventura',
            'Misterio',
            'Biografía',
            'Historia',
            'Política',
            'Economía',
            'Autoayuda',
            'Cocina',
            'Infantil',
            'Juvenil',
            'Poesía',
            'Ensayo',
            'Religión',
            'Filosofía',
            'Ciencia',
            'Tecnología',
            'Arte',
            'Música',
            'Deportes',
        ];

        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }
    }
}
