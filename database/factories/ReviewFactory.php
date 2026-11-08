<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'rating' => fake()->randomElement([0.5, 1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0]),
            'content' => fake()->paragraph(),
            'has_spoilers' => false,
        ];
    }

    public function withSpoilers(): static
    {
        return $this->state(fn (array $attributes): array => [
            'has_spoilers' => true,
        ]);
    }
}
