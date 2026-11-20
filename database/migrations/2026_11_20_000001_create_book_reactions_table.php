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
        Schema::create('book_reactions', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('book_id');
            $table->string('reaction', 32);
            $table->timestamps();

            // Un usuario no puede repetir la misma reacción en un libro.
            $table->primary(['user_id', 'book_id', 'reaction']);

            // Índice cubriente para el GROUP BY (book_id, reaction) del top global.
            // También satisface el índice que exige la FK de book_id.
            $table->index(['book_id', 'reaction']);

            // Las FKs se declaran tras los índices para que MariaDB reutilice
            // el PK (user_id) y el índice secundario (book_id) en lugar de
            // crear índices implícitos redundantes.
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_reactions');
    }
};
