<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $books = [
            [
                'name' => 'El señor de los anillos',
                'description' => 'Un libro de fantasía épica escrito por J. R. R. Tolkien.',
                'categories' => [2],
                'authors' => [1],
            ],
            [
                'name' => 'El hobbit',
                'description' => 'Un libro de fantasía escrito por J. R. R. Tolkien.',
                'categories' => [2],
                'authors' => [1],
            ],
            [
                'name' => 'El código Da Vinci',
                'description' => 'Un libro de misterio escrito por Dan Brown.',
                'categories' => [6],
                'authors' => [3],
            ],
            [
                'name' => 'Cien años de soledad',
                'description' => 'Un libro de realismo mágico escrito por Gabriel García Márquez.',
                'categories' => [6],
                'authors' => [7],
            ],
            [
                'name' => '1984',
                'description' => 'Un libro de ciencia ficción distópica escrito por George Orwell.',
                'categories' => [1],
                'authors' => [8],
            ],
            [
                'name' => 'Así habló Zaratustra',
                'description' => 'Un libro de filosofía escrito por Friedrich Nietzsche.',
                'categories' => [18],
                'authors' => [9],
            ],
            [
                'name' => 'El capital',
                'description' => 'Un libro de economía escrito por Karl Marx.',
                'categories' => [10],
                'authors' => [10],
            ],
            [
                'name' => 'Cómo ganar amigos e influir sobre las personas',
                'description' => 'Un libro de autoayuda escrito por Dale Carnegie.',
                'categories' => [11],
                'authors' => [11],
            ],
            [
                'name' => 'Mastering the Art of French Cooking',
                'description' => 'Un libro de cocina escrito por Julia Child.',
                'categories' => [12],
                'authors' => [12],
            ],
            [
                'name' => 'Charlie y la fábrica de chocolate',
                'description' => 'Un libro infantil escrito por Roald Dahl.',
                'categories' => [13],
                'authors' => [13],
            ],
            [
                'name' => 'Harry Potter y la piedra filosofal',
                'description' => 'Un libro juvenil escrito por J. K. Rowling.',
                'categories' => [14],
                'authors' => [14],
            ],
        ];

        foreach ($books as $book) {
            $authors = $book['authors'];
            unset($book['authors']);
            $categories = $book['categories'];
            unset($book['categories']);
            $book = Book::create($book);
            $book->authors()->attach($authors);
            $book->categories()->attach($categories);
        }
    }
}
