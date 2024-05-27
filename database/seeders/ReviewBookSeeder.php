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
                'content' => 'Es un gran libro',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 2,
                'book_id' => 1,
                'read_book_id' => 2,
                'content' => 'No me gustó',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
