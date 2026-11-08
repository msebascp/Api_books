<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reviews = [
            [
                'user_id' => 1,
                'book_id' => 1,
                'rating' => 4.5,
                'content' => 'Me gustó mucho, Sherlock Holmes es uno de mis personajes favoritos.',
                'has_spoilers' => false,
            ],
            [
                'user_id' => 2,
                'book_id' => 1,
                'rating' => 5.0,
                'content' => 'Me encantó este libro, lo recomiendo mucho.',
                'has_spoilers' => false,
            ],
            [
                'user_id' => 3,
                'book_id' => 1,
                'rating' => 2.0,
                'content' => 'No me gustó mucho, no lo recomendaría.',
                'has_spoilers' => false,
            ],
        ];

        foreach ($reviews as $reviewData) {
            Review::create($reviewData);
        }
    }
}
