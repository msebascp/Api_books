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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 50)->change();
        });

        Schema::table('books', function (Blueprint $table): void {
            $table->string('isbn', 20)->nullable()->change();
            $table->decimal('avg_rating', 3, 2)->unsigned()->default(0.00)->change();

            $table->index(['created_at', 'id']);
            $table->index(['avg_rating', 'ratings_count']);
            $table->index(['publication_date', 'id']);
        });

        Schema::table('authors', function (Blueprint $table): void {
            $table->index('name');
        });

        Schema::table('library_entries', function (Blueprint $table): void {
            $table->date('read_at')->nullable()->change();

            $table->dropIndex(['status']);
            $table->index(['user_id', 'status', 'updated_at']);
            $table->index(['user_id', 'updated_at']);
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->decimal('rating', 2, 1)->unsigned()->change();

            $table->index('created_at');
            $table->index(['book_id', 'created_at']);
        });

        if (Schema::hasIndex('reviews', 'reviews_book_id_foreign')) {
            Schema::table('reviews', function (Blueprint $table): void {
                $table->dropIndex('reviews_book_id_foreign');
            });
        }

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement(
                'ALTER TABLE `reviews` ADD CONSTRAINT `reviews_rating_range_check` CHECK (`rating` >= 0.5 AND `rating` <= 5.0)'
            );
        }

        Schema::table('comments', function (Blueprint $table): void {
            $table->index(['review_id', 'created_at']);
        });

        if (Schema::hasIndex('comments', 'comments_review_id_foreign')) {
            Schema::table('comments', function (Blueprint $table): void {
                $table->dropIndex('comments_review_id_foreign');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->index('review_id', 'comments_review_id_foreign');
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->dropIndex(['review_id', 'created_at']);
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `reviews` DROP CONSTRAINT `reviews_rating_range_check`');
        }

        Schema::table('reviews', function (Blueprint $table): void {
            $table->index('book_id', 'reviews_book_id_foreign');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropIndex(['book_id', 'created_at']);
            $table->dropIndex(['created_at']);
            $table->decimal('rating', 2, 1)->change();
        });

        Schema::table('library_entries', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'status', 'updated_at']);
            $table->dropIndex(['user_id', 'updated_at']);
            $table->index('status');
            $table->timestamp('read_at')->nullable()->change();
        });

        Schema::table('authors', function (Blueprint $table): void {
            $table->dropIndex(['name']);
        });

        Schema::table('books', function (Blueprint $table): void {
            $table->dropIndex(['created_at', 'id']);
            $table->dropIndex(['avg_rating', 'ratings_count']);
            $table->dropIndex(['publication_date', 'id']);
            $table->string('isbn', 255)->nullable()->change();
            $table->decimal('avg_rating', 3, 2)->default(0.00)->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 255)->change();
        });
    }
};
