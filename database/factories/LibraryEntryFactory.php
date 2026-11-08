<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LibraryEntry>
 */
class LibraryEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var ReadingStatus $status */
        $status = fake()->randomElement(ReadingStatus::cases());

        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'status' => $status,
            'is_owned' => fake()->boolean(30),
            'is_favorite' => fake()->boolean(20),
            'read_at' => $status === ReadingStatus::Read ? now() : null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReadingStatus::Read,
            'read_at' => now(),
        ]);
    }

    public function toRead(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReadingStatus::ToRead,
            'read_at' => null,
        ]);
    }

    public function reading(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReadingStatus::Reading,
            'read_at' => null,
        ]);
    }

    public function dnf(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReadingStatus::Dnf,
            'read_at' => null,
        ]);
    }
}
