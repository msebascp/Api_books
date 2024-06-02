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
                'content' => '«En la madeja incolora de la vida encontramos la hebra escarlata del asesinato, y nuestro deber consiste en desenredarla y sacar a la luz todos sus detalles.»Sherlock Holmes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
