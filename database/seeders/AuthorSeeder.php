<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AuthorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $authors = [
            'Isaac Asimov',
            'J. R. R. Tolkien',
            'Stephen King',
            'Jane Austen',
            'Jules Verne',
            'Agatha Christie',
            'Gabriel García Márquez',
            'George Orwell',
            'Friedrich Nietzsche',
            'Karl Marx',
            'Dale Carnegie',
            'Julia Child',
            'Roald Dahl',
            'J. K. Rowling',
            'Pablo Neruda',
            'Albert Camus',
            'C. S. Lewis',
            'Carl Sagan',
            'Steve Jobs',
            'Leonardo Da Vinci',
            'Ludwig van Beethoven',
            'Michael Jordan',
            'Paulo Coelho',
            'J. Michael Straczynski',
            'Masashi Kishimoto',
        ];

        foreach ($authors as $author) {
            Author::create(['name' => $author]);
        }
    }
}
