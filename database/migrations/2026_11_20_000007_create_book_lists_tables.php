<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('book_lists', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title', 120);
            $table->string('slug', 140);
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_ranked')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'updated_at']);
            $table->index(['is_public', 'created_at']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('book_list_items', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->unsignedBigInteger('book_list_id');
            $table->unsignedBigInteger('book_id');
            $table->unsignedInteger('position');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['book_list_id', 'book_id']);
            $table->index(['book_list_id', 'position']);
            $table->index(['book_list_id', 'created_at']);
            $table->index('book_id');

            $table->foreign('book_list_id')->references('id')->on('book_lists')->cascadeOnDelete();
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_list_items');
        Schema::dropIfExists('book_lists');
    }
};
