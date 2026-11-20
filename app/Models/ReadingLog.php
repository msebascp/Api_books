<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\ReadingLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(ReadingLogObserver::class)]
class ReadingLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'book_id',
        'started_at',
        'finished_at',
        'is_reread',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'book_id' => 'integer',
            'started_at' => 'date',
            'finished_at' => 'date',
            'is_reread' => 'boolean',
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
