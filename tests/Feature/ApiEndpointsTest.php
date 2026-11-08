<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ReadingStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\Comment;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_login_with_email_or_username_me_and_current_token_logout(): void
    {
        $registerResponse = $this->postJson('/api/register', [
            'name' => 'Ana Lectora',
            'username' => 'ana.lectora_99',
            'email' => 'ana@example.com',
            'password' => 'Secret1234!',
            'bio' => 'Amante de los clásicos.',
        ]);

        $registerResponse->assertCreated()
            ->assertJsonPath('data.username', 'ana.lectora_99')
            ->assertJsonPath('data.bio', 'Amante de los clásicos.')
            ->assertJsonStructure(['data' => ['id', 'name', 'username', 'email'], 'token']);

        $firstToken = (string) $registerResponse->json('token');

        // Login using username in single field
        $loginByUsername = $this->postJson('/api/login', [
            'login' => 'ana.lectora_99',
            'password' => 'Secret1234!',
        ]);
        $loginByUsername->assertOk()->assertJsonStructure(['data', 'token']);
        $secondToken = (string) $loginByUsername->json('token');

        // Login using email in single field
        $this->postJson('/api/login', [
            'login' => 'ana@example.com',
            'password' => 'Secret1234!',
        ])->assertOk();

        // Invalid password returns 401
        $this->postJson('/api/login', [
            'login' => 'ana.lectora_99',
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        // GET /api/me returns counts
        $this->withHeader('Authorization', 'Bearer '.$secondToken)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.username', 'ana.lectora_99')
            ->assertJsonStructure([
                'data' => [
                    'library_entries_count',
                    'reviews_count',
                    'followers_count',
                    'following_count',
                ],
            ]);

        // POST /api/logout revokes only the current token in use
        $this->withHeader('Authorization', 'Bearer '.$secondToken)
            ->postJson('/api/logout')
            ->assertOk();

        $user = User::where('username', 'ana.lectora_99')->firstOrFail();
        $this->assertCount(2, $user->tokens); // firstToken + emailLoginToken remain; secondToken deleted
        $this->assertNotEmpty($firstToken);
    }

    public function test_catalog_crud_filters_sorting_and_admin_authorization(): void
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('user', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $reader = User::factory()->create();
        $reader->assignRole('user');

        // Non-admin gets 403 on catalog write endpoints
        $this->actingAs($reader, 'sanctum')
            ->postJson('/api/authors', ['name' => 'Isaac Asimov'])
            ->assertForbidden();

        // Admin creates author and genre
        $authorRes = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/authors', ['name' => 'Isaac Asimov', 'bio' => 'Maestro sci-fi'])
            ->assertCreated();
        $authorId = (int) $authorRes->json('data.id');

        $genreRes = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/genres', ['name' => 'Ciencia Ficción'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'ciencia-ficcion');
        $genreId = (int) $genreRes->json('data.id');

        // Admin creates book syncing pivots
        $bookRes = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/books', [
                'title' => 'Fundación',
                'description' => 'Imperio galáctico.',
                'isbn' => '9788418037542',
                'pages' => 320,
                'authors' => [$authorId],
                'genres' => [$genreId],
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Fundación')
            ->assertJsonCount(1, 'data.authors')
            ->assertJsonCount(1, 'data.genres');

        $bookId = (int) $bookRes->json('data.id');

        // Public filters & search
        $this->getJson('/api/books?genre=ciencia-ficcion&author='.$authorId.'&sort=avg_rating')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/authors?search=Asimov')
            ->assertOk()
            ->assertJsonPath('data.0.books_count', 1);

        // Admin updates book and deletes author safely detaching pivot
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/books/{$bookId}", ['title' => 'Trilogía Fundación'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Trilogía Fundación');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/authors/{$authorId}")
            ->assertOk();

        $this->assertDatabaseMissing('author_book', ['author_id' => $authorId, 'book_id' => $bookId]);
    }

    public function test_library_entries_idempotent_upsert_filters_and_destroy(): void
    {
        $user = User::factory()->create();
        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();

        // Initial insert -> 201 Created
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/library-entries', [
                'book_id' => $book1->id,
                'status' => ReadingStatus::ToRead->value,
                'is_owned' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'to_read')
            ->assertJsonPath('data.is_owned', true);

        // Idempotent update -> 200 OK
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/library-entries', [
                'book_id' => $book1->id,
                'status' => ReadingStatus::Read->value,
                'is_favorite' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'read')
            ->assertJsonPath('data.is_favorite', true)
            ->assertJsonPath('data.is_owned', true);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/library-entries', [
                'book_id' => $book2->id,
                'status' => ReadingStatus::Reading->value,
                'is_owned' => false,
            ])
            ->assertCreated();

        // Filter by status & flags
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/library-entries?status=read&is_favorite=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.book_id', $book1->id);

        // Delete from personal library
        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/library-entries/{$book1->id}")
            ->assertOk();

        $this->assertDatabaseMissing('library_entries', [
            'user_id' => $user->id,
            'book_id' => $book1->id,
        ]);
    }

    public function test_reviews_unique_validation_ownership_likes_and_comments(): void
    {
        $authorUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        // Create review
        $reviewRes = $this->actingAs($authorUser, 'sanctum')
            ->postJson("/api/books/{$book->id}/reviews", [
                'rating' => 4.5,
                'content' => 'Obra maestra absoluta.',
                'has_spoilers' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('data.rating', 4.5);

        $reviewId = (int) $reviewRes->json('data.id');

        // Duplicate review by same user on same book fails FormRequest validation with 422
        $this->actingAs($authorUser, 'sanctum')
            ->postJson("/api/books/{$book->id}/reviews", [
                'rating' => 3.0,
                'content' => 'Intento duplicado.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['book_id']);

        // Non-owner cannot update or delete review
        $this->actingAs($otherUser, 'sanctum')
            ->putJson("/api/reviews/{$reviewId}", ['rating' => 1.0])
            ->assertForbidden();

        $this->actingAs($otherUser, 'sanctum')
            ->deleteJson("/api/reviews/{$reviewId}")
            ->assertForbidden();

        // Owner updates review
        $this->actingAs($authorUser, 'sanctum')
            ->putJson("/api/reviews/{$reviewId}", ['rating' => 5.0, 'has_spoilers' => true])
            ->assertOk()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.has_spoilers', true);

        // Toggle like
        $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/reviews/{$reviewId}/like")
            ->assertOk()
            ->assertJsonPath('liked', true)
            ->assertJsonPath('data.likes_count', 1);

        $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/reviews/{$reviewId}/like")
            ->assertOk()
            ->assertJsonPath('liked', false)
            ->assertJsonPath('data.likes_count', 0);

        // Comments nested under review + strict ownership on delete
        $commentRes = $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/reviews/{$reviewId}/comments", [
                'content' => 'Totalmente de acuerdo.',
            ])
            ->assertCreated();

        $commentId = (int) $commentRes->json('data.id');

        $this->getJson("/api/reviews/{$reviewId}/comments")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($authorUser, 'sanctum')
            ->deleteJson("/api/comments/{$commentId}")
            ->assertForbidden();

        $this->actingAs($otherUser, 'sanctum')
            ->deleteJson("/api/comments/{$commentId}")
            ->assertOk();

        $this->assertNull(Comment::find($commentId));
        $this->assertNotNull(Review::find($reviewId));
    }

    public function test_public_user_profile_and_follow_toggle_endpoints(): void
    {
        $alice = User::factory()->create(['username' => 'alice_reads']);
        $bob = User::factory()->create(['username' => 'bob_reads']);

        // Self-follow rejected with 422
        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/users/{$alice->id}/follow")
            ->assertUnprocessable();

        // Follow Bob
        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/users/{$bob->username}/follow")
            ->assertOk()
            ->assertJsonPath('following', true)
            ->assertJsonPath('data.followers_count', 1);

        // Public profile by username and by ID
        $this->getJson('/api/users/bob_reads')
            ->assertOk()
            ->assertJsonPath('data.followers_count', 1);

        $this->getJson("/api/users/{$bob->id}")
            ->assertOk()
            ->assertJsonPath('data.username', 'bob_reads');

        // Followers & following lists
        $this->getJson("/api/users/{$bob->id}/followers")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.username', 'alice_reads');

        $this->getJson("/api/users/{$alice->id}/following")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.username', 'bob_reads');

        // Unfollow Bob
        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/users/{$bob->id}/follow")
            ->assertOk()
            ->assertJsonPath('following', false)
            ->assertJsonPath('data.followers_count', 0);
    }
}
