<?php

declare(strict_types=1);

namespace App\Models;

use Closure;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'book_id',
        'content',
        'has_spoilers',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_spoilers' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class)->withTrashed();
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'review_likes', 'review_id', 'user_id')
            ->withPivot('created_at');
    }

    /**
     * Añade mediante subconsulta escalar la puntuación (`reviewer_rating`) que
     * el autor de la reseña otorgó al libro en `library_entries` sin N+1.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithReviewerRating(Builder $query): Builder
    {
        return $query->addSelect([
            'reviewer_rating' => LibraryEntry::query()
                ->select('rating')
                ->whereColumn('library_entries.user_id', 'reviews.user_id')
                ->whereColumn('library_entries.book_id', 'reviews.book_id')
                ->limit(1),
        ]);
    }

    /**
     * Carga la puntuación del autor de la reseña en una instancia ya hidratada.
     */
    public function loadReviewerRating(): self
    {
        $rating = LibraryEntry::query()
            ->where('user_id', $this->user_id)
            ->where('book_id', $this->book_id)
            ->value('rating');

        $this->setAttribute('reviewer_rating', $rating !== null ? (float) $rating : null);

        return $this;
    }

    /**
     * Restricción withExists/loadExists para saber si el viewer dio like a la reseña.
     *
     * @return array<string, Closure>
     */
    public static function viewerLikeExistsConstraint(User $viewer): array
    {
        return [
            'likes as liked_by_viewer' => static function (Builder $query) use ($viewer): void {
                $query->where('review_likes.user_id', $viewer->getKey());
            },
        ];
    }
}
