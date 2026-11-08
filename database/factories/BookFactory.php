<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->sentence(3), '.'),
            'description' => fake()->paragraphs(2, true),
            'cover_path' => null,
            'isbn' => fake()->unique()->isbn13(),
            'publication_date' => fake()->date(),
            'pages' => fake()->numberBetween(80, 1200),
            'avg_rating' => 0.00,
            'ratings_count' => 0,
        ];
    }
}
