<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class BookList extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_public' => true,
        'is_ranked' => false,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'is_public',
        'is_ranked',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_public' => 'boolean',
            'is_ranked' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static function (self $list): void {
            if ($list->slug === null || $list->slug === '') {
                $list->slug = self::generateUniqueSlug((int) $list->user_id, (string) $list->title);
            }
        });

        static::updating(static function (self $list): void {
            if ($list->isDirty('title') && ! $list->isDirty('slug')) {
                $list->slug = self::generateUniqueSlug(
                    (int) $list->user_id,
                    (string) $list->title,
                    (int) $list->getKey()
                );
            }
        });
    }

    public static function generateUniqueSlug(int $userId, string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);

        if ($baseSlug === '') {
            $baseSlug = 'lista';
        }

        $baseSlug = substr($baseSlug, 0, 125);
        $slug = $baseSlug;
        $suffix = 2;

        while (
            self::query()
                ->where('user_id', $userId)
                ->where('slug', $slug)
                ->when($ignoreId !== null, static fn ($q) => $q->where('id', '<>', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Book, $this>
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_list_items')
            ->withPivot(['position', 'created_at'])
            ->withTrashed();
    }
}
