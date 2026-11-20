<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ReactionType;
use App\Models\Book;
use App\Models\BookReaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookReactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_sync_reactions(): void
    {
        $book = Book::factory()->create();

        $this->putJson("/api/books/{$book->id}/reactions", ['reactions' => ['fire']])
            ->assertUnauthorized();
    }

    public function test_sync_replaces_reactions_atomically_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson("/api/books/{$book->id}/reactions", ['reactions' => ['fire', 'heart']])
            ->assertOk()
            ->assertJsonPath('message', 'Reacciones actualizadas correctamente.')
            ->assertJsonPath('data.reactions.total', 2)
            ->assertJsonPath('data.reactions.user_reactions', ['fire', 'heart']);

        // Mismo payload otra vez: mismo estado (idempotencia).
        $this->putJson("/api/books/{$book->id}/reactions", ['reactions' => ['fire', 'heart']])
            ->assertOk()
            ->assertJsonPath('data.reactions.total', 2);

        // Reemplazo completo del set.
        $this->putJson("/api/books/{$book->id}/reactions", ['reactions' => ['mind_blown', 'cry', 'laugh']])
            ->assertOk()
            ->assertJsonPath('data.reactions.total', 3)
            ->assertJsonPath('data.reactions.user_reactions', ['cry', 'laugh', 'mind_blown']);

        $this->assertSame(3, BookReaction::query()->where('book_id', $book->id)->count());
        $this->assertDatabaseMissing('book_reactions', ['book_id' => $book->id, 'reaction' => 'fire']);

        // Array vacío: elimina todas las reacciones del usuario.
        $this->putJson("/api/books/{$book->id}/reactions", ['reactions' => []])
            ->assertOk()
            ->assertJsonPath('data.reactions.total', 0)
            ->assertJsonPath('data.reactions.top_reactions', [])
            ->assertJsonPath('data.reactions.user_reactions', []);
    }

    public function test_sync_validation_rules(): void
    {
        $book = Book::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $url = "/api/books/{$book->id}/reactions";

        $this->putJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('reactions');
        $this->putJson($url, ['reactions' => 'fire'])->assertUnprocessable()->assertJsonValidationErrors('reactions');
        $this->putJson($url, ['reactions' => ['fire', 'heart', 'cry', 'laugh']])
            ->assertUnprocessable()->assertJsonValidationErrors('reactions');
        $this->putJson($url, ['reactions' => ['fire', 'fire']])
            ->assertUnprocessable()->assertJsonValidationErrors('reactions.0');
        $this->putJson($url, ['reactions' => ['poop']])
            ->assertUnprocessable()->assertJsonValidationErrors('reactions.0');

        $this->putJson('/api/books/999999/reactions', ['reactions' => ['fire']])->assertNotFound();

        $this->assertSame(0, BookReaction::query()->count());
    }

    public function test_book_resource_exposes_global_top_three_and_viewer_reactions(): void
    {
        $book = Book::factory()->create();
        [$ana, $luis, $eva] = User::factory()->count(3)->create()->all();

        $this->react($ana, $book, [ReactionType::Fire, ReactionType::Heart, ReactionType::MindBlown]);
        $this->react($luis, $book, [ReactionType::Fire, ReactionType::Heart]);
        $this->react($eva, $book, [ReactionType::Fire, ReactionType::Cry]);

        // Invitado en ruta pública: top global, sin reacciones propias.
        $this->getJson("/api/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.reactions.total', 7)
            ->assertJsonPath('data.reactions.top_reactions', [
                ['type' => 'fire', 'emoji' => '🔥', 'count' => 3],
                ['type' => 'heart', 'emoji' => '❤️', 'count' => 2],
                ['type' => 'cry', 'emoji' => '😭', 'count' => 1],
            ])
            ->assertJsonPath('data.reactions.user_reactions', []);

        // Con token Sanctum en la MISMA ruta pública: aparecen las suyas.
        Sanctum::actingAs($eva);

        $this->getJson("/api/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.reactions.user_reactions', ['cry', 'fire']);
    }

    public function test_books_index_does_not_suffer_from_n_plus_one(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $queriesFor = function (int $books) use ($user): int {
            Book::factory()->count($books)->create()->each(
                fn (Book $book) => $this->react($user, $book, [ReactionType::Fire, ReactionType::Laugh])
            );

            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson('/api/books?per_page=50')->assertOk();
            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $this->assertSame($queriesFor(2), $queriesFor(8));
    }

    /**
     * @param  list<ReactionType>  $reactions
     */
    private function react(User $user, Book $book, array $reactions): void
    {
        foreach ($reactions as $reaction) {
            BookReaction::query()->insert([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'reaction' => $reaction->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
