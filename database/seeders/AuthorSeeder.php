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
            'Arthur Conan Doyle',
            'Isaac Asimov',
            'J. R. R. Tolkien',
            'Stephen King',
            'Jane Austen',
            'Julio Verne',
            'Agatha Christie',
            'Gabriel García Márquez',
            'George Orwell',
            'Friedrich Nietzsche',
            'Dale Carnegie',
            'Julia Child',
            'Roald Dahl',
            'J. K. Rowling',
            'Brandon Sanderson',
            'Oscar Wilde',
            'Joel Dicker',
        ];

        foreach ($authors as $author) {
            Author::create(['name' => $author]);
        }
    }
}
