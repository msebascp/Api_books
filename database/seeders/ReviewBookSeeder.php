<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewBookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('reviews')->insert([
            [
                'user_id' => 1,
                'book_id' => 1,
                'read_book_id' => 1,
                'content' => 'Me gustó mucho, Sherlock Holmes es uno de mis personajes favoritos.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 2,
                'book_id' => 1,
                'read_book_id' => 1,
                'content' => 'Me encantó este libro, lo recomiendo mucho.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 3,
                'book_id' => 1,
                'read_book_id' => 1,
                'content' => 'No me gustó mucho, no lo recomendaría.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
