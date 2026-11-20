<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reacción con emoji de un usuario sobre un libro.
 *
 * La tabla usa una clave primaria compuesta (user_id, book_id, reaction),
 * que Eloquent no soporta de forma nativa: las escrituras se hacen con el
 * query builder (ver BookReactionController::sync) y no mediante save()/delete()
 * sobre instancias individuales.
 *
 * @property int $user_id
 * @property int $book_id
 * @property ReactionType $reaction
 */
class BookReaction extends Model
{
    /**
     * @var string|null
     */
    protected $primaryKey = null;

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'book_id',
        'reaction',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'book_id' => 'integer',
            'reaction' => ReactionType::class,
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
        return $this->belongsTo(Book::class);
    }
}
