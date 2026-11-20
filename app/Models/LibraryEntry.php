<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReadingStatus;
use App\Observers\LibraryEntryObserver;
use Database\Factories\LibraryEntryFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(LibraryEntryObserver::class)]
class LibraryEntry extends Model
{
    /** @use HasFactory<LibraryEntryFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_owned' => false,
        'is_favorite' => false,
        'read_count' => 0,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'book_id',
        'status',
        'is_owned',
        'is_favorite',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReadingStatus::class,
            'is_owned' => 'boolean',
            'is_favorite' => 'boolean',
            'rating' => 'float',
            'read_count' => 'integer',
            'last_read_at' => 'date',
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
}
