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
        Schema::create('reading_logs', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('book_id');
            $table->date('started_at')->nullable();
            $table->date('finished_at')->nullable();
            $table->boolean('is_reread')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'finished_at', 'id']);
            $table->index(['user_id', 'book_id', 'finished_at']);

            // Las FKs se declaran después de los índices compuestos para que
            // MariaDB reutilice el prefijo user_id sin crear un índice duplicado.
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_logs');
    }
};
