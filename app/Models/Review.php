<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\ReviewObserver;
use Closure;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(ReviewObserver::class)]
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
        'rating',
        'content',
        'has_spoilers',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'float',
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
