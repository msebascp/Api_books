<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ReadingStatus;
use App\Models\LibraryEntry;
use Illuminate\Database\Seeder;

class LibraryEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entries = [
            // Curated read entries migrated from ReadBookSeeder
            ['user_id' => 1, 'book_id' => 1, 'status' => ReadingStatus::Read, 'is_owned' => true, 'is_favorite' => true, 'read_at' => now()],
            ['user_id' => 2, 'book_id' => 1, 'status' => ReadingStatus::Read, 'is_owned' => false, 'is_favorite' => false, 'read_at' => now()],
            ['user_id' => 3, 'book_id' => 1, 'status' => ReadingStatus::Read, 'is_owned' => false, 'is_favorite' => true, 'read_at' => now()],

            ['user_id' => 1, 'book_id' => 2, 'status' => ReadingStatus::Read, 'is_owned' => true, 'is_favorite' => true, 'read_at' => now()],
            ['user_id' => 2, 'book_id' => 2, 'status' => ReadingStatus::Read, 'is_owned' => false, 'is_favorite' => false, 'read_at' => now()],
            ['user_id' => 3, 'book_id' => 2, 'status' => ReadingStatus::Read, 'is_owned' => true, 'is_favorite' => true, 'read_at' => now()],

            ['user_id' => 1, 'book_id' => 3, 'status' => ReadingStatus::Read, 'is_owned' => false, 'is_favorite' => true, 'read_at' => now()],
            ['user_id' => 2, 'book_id' => 3, 'status' => ReadingStatus::Read, 'is_owned' => false, 'is_favorite' => false, 'read_at' => now()],
            ['user_id' => 3, 'book_id' => 3, 'status' => ReadingStatus::Read, 'is_owned' => false, 'is_favorite' => true, 'read_at' => now()],

            ['user_id' => 1, 'book_id' => 4, 'status' => ReadingStatus::Read, 'is_owned' => true, 'is_favorite' => true, 'read_at' => now()],
            ['user_id' => 2, 'book_id' => 4, 'status' => ReadingStatus::Read, 'is_owned' => false, 'is_favorite' => false, 'read_at' => now()],
            ['user_id' => 3, 'book_id' => 4, 'status' => ReadingStatus::Read, 'is_owned' => false, 'is_favorite' => true, 'read_at' => now()],

            // Additional reading states across the unified library_entries model
            ['user_id' => 1, 'book_id' => 5, 'status' => ReadingStatus::Reading, 'is_owned' => true, 'is_favorite' => false, 'read_at' => null],
            ['user_id' => 1, 'book_id' => 7, 'status' => ReadingStatus::ToRead, 'is_owned' => false, 'is_favorite' => false, 'read_at' => null],
            ['user_id' => 4, 'book_id' => 8, 'status' => ReadingStatus::Read, 'is_owned' => true, 'is_favorite' => true, 'read_at' => now()],
            ['user_id' => 4, 'book_id' => 9, 'status' => ReadingStatus::ToRead, 'is_owned' => true, 'is_favorite' => false, 'read_at' => null],
            ['user_id' => 2, 'book_id' => 10, 'status' => ReadingStatus::Dnf, 'is_owned' => false, 'is_favorite' => false, 'read_at' => null],
        ];

        foreach ($entries as $entry) {
            LibraryEntry::create($entry);
        }
    }
}
