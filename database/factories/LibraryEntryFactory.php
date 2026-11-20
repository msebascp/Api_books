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
        $isRead = $status === ReadingStatus::Read;

        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'status' => $status,
            'is_owned' => fake()->boolean(30),
            'is_favorite' => fake()->boolean(20),
            'read_count' => $isRead ? 1 : 0,
            'last_read_at' => $isRead ? now()->toDateString() : null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReadingStatus::Read,
            'read_count' => 1,
            'last_read_at' => now()->toDateString(),
        ]);
    }

    public function toRead(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReadingStatus::ToRead,
            'read_count' => 0,
            'last_read_at' => null,
        ]);
    }

    public function reading(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReadingStatus::Reading,
            'read_count' => 0,
            'last_read_at' => null,
        ]);
    }

    public function dnf(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReadingStatus::Dnf,
            'read_count' => 0,
            'last_read_at' => null,
        ]);
    }
}
