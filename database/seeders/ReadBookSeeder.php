<?php

namespace Database\Seeders;

use App\Models\ReadBook;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReadBookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('read_books')->insert([
            [
                'user_id' => 1,
                'book_id' => 1,
                'is_like' => true
            ],
            [
                'user_id' => 2,
                'book_id' => 1,
                'is_like' => false
            ],
            [
                'user_id' => 3,
                'book_id' => 1,
                'is_like' => true
            ],
            [
                'user_id' => 1,
                'book_id' => 2,
                'is_like' => true
            ],
            [
                'user_id' => 2,
                'book_id' => 2,
                'is_like' => false
            ],
            [
                'user_id' => 3,
                'book_id' => 2,
                'is_like' => true
            ],
            [
                'user_id' => 1,
                'book_id' => 3,
                'is_like' => true
            ],
            [
                'user_id' => 2,
                'book_id' => 3,
                'is_like' => false
            ],
            [
                'user_id' => 3,
                'book_id' => 3,
                'is_like' => true
            ],
            [
                'user_id' => 1,
                'book_id' => 4,
                'is_like' => true
            ],
            [
                'user_id' => 2,
                'book_id' => 4,
                'is_like' => false
            ],
            [
                'user_id' => 3,
                'book_id' => 4,
                'is_like' => true
            ],
        ]);
    }
}
