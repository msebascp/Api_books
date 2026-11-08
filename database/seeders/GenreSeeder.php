<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $genres = [
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

        foreach ($genres as $genreName) {
            Genre::create([
                'name' => $genreName,
                'slug' => Str::slug($genreName),
            ]);
        }
    }
}
