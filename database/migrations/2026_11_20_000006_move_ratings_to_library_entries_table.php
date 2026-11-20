<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('library_entries', function (Blueprint $table): void {
            $table->decimal('rating', 2, 1)->unsigned()->nullable()->after('is_favorite');
            $table->index(['book_id', 'rating']);
        });

        if (Schema::hasIndex('library_entries', 'library_entries_book_id_foreign')) {
            Schema::table('library_entries', function (Blueprint $table): void {
                $table->dropIndex('library_entries_book_id_foreign');
            });
        }

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement(
                'ALTER TABLE `library_entries` ADD CONSTRAINT `library_entries_rating_range_check` CHECK (`rating` IS NULL OR (`rating` >= 0.5 AND `rating` <= 5.0))'
            );
        }

        // Migrar ratings existentes desde reviews hacia library_entries
        $reviewsWithRating = DB::table('reviews')
            ->whereNotNull('rating')
            ->get(['user_id', 'book_id', 'rating', 'created_at', 'updated_at']);

        foreach ($reviewsWithRating as $review) {
            $finishedAt = $review->created_at !== null
                ? substr((string) $review->created_at, 0, 10)
                : now()->toDateString();

            $entry = DB::table('library_entries')
                ->where('user_id', $review->user_id)
                ->where('book_id', $review->book_id)
                ->first(['id', 'read_count']);

            if ($entry === null) {
                DB::table('library_entries')->insert([
                    'user_id' => $review->user_id,
                    'book_id' => $review->book_id,
                    'status' => 'read',
                    'is_owned' => false,
                    'is_favorite' => false,
                    'rating' => $review->rating,
                    'read_count' => 1,
                    'last_read_at' => $finishedAt,
                    'created_at' => $review->created_at ?? now(),
                    'updated_at' => $review->updated_at ?? now(),
                ]);
            } else {
                $updates = ['rating' => $review->rating];

                if ((int) $entry->read_count === 0) {
                    $updates['status'] = 'read';
                    $updates['read_count'] = 1;
                    $updates['last_read_at'] = $finishedAt;
                }

                DB::table('library_entries')
                    ->where('id', $entry->id)
                    ->update($updates);
            }

            $hasLog = DB::table('reading_logs')
                ->where('user_id', $review->user_id)
                ->where('book_id', $review->book_id)
                ->exists();

            if (! $hasLog) {
                DB::table('reading_logs')->insert([
                    'user_id' => $review->user_id,
                    'book_id' => $review->book_id,
                    'started_at' => null,
                    'finished_at' => $finishedAt,
                    'is_reread' => false,
                    'created_at' => $review->created_at ?? now(),
                    'updated_at' => $review->updated_at ?? now(),
                ]);
            }
        }

        // Eliminar reseñas sin texto antes de hacer content NOT NULL
        DB::table('reviews')
            ->whereNull('content')
            ->orWhereRaw("TRIM(content) = ''")
            ->delete();

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `reviews` DROP CONSTRAINT `reviews_rating_range_check`');
        }

        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropColumn('rating');
            $table->text('content')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->decimal('rating', 2, 1)->unsigned()->default(5.0)->after('book_id');
            $table->text('content')->nullable()->change();
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement(
                'ALTER TABLE `reviews` ADD CONSTRAINT `reviews_rating_range_check` CHECK (`rating` >= 0.5 AND `rating` <= 5.0)'
            );
        }

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `library_entries` DROP CONSTRAINT `library_entries_rating_range_check`');
        }

        Schema::table('library_entries', function (Blueprint $table): void {
            $table->index('book_id', 'library_entries_book_id_foreign');
        });

        Schema::table('library_entries', function (Blueprint $table): void {
            $table->dropIndex(['book_id', 'rating']);
            $table->dropColumn('rating');
        });
    }
};
