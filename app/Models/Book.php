<?php

declare(strict_types=1);

namespace App\Models;

use Closure;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'cover_path',
        'isbn',
        'publication_date',
        'pages',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publication_date' => 'date',
            'pages' => 'integer',
            'avg_rating' => 'float',
            'ratings_count' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<Author, $this>
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'author_book');
    }

    /**
     * @return BelongsToMany<Genre, $this>
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'book_genre');
    }

    /**
     * @return HasMany<LibraryEntry, $this>
     */
    public function libraryEntries(): HasMany
    {
        return $this->hasMany(LibraryEntry::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'library_entries')
            ->withPivot(['status', 'is_owned', 'is_favorite', 'rating', 'read_count', 'last_read_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<ReadingLog, $this>
     */
    public function readingLogs(): HasMany
    {
        return $this->hasMany(ReadingLog::class);
    }

    /**
     * @return BelongsToMany<BookList, $this>
     */
    public function bookLists(): BelongsToMany
    {
        return $this->belongsToMany(BookList::class, 'book_list_items')
            ->withPivot(['position', 'created_at']);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<BookReaction, $this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(BookReaction::class);
    }

    /**
     * Recuento agregado por tipo de reacción (una fila por tipo, como máximo
     * tantas como casos tenga ReactionType), ordenado de más a menos popular.
     *
     * Al precargarse con with()/load() resuelve en UNA sola consulta GROUP BY
     * para toda la página de libros, apoyándose en el índice (book_id, reaction).
     * Cada modelo expone el atributo agregado `total`.
     *
     * @return HasMany<BookReaction, $this>
     */
    public function reactionCounts(): HasMany
    {
        return $this->hasMany(BookReaction::class)
            ->select(['book_id', 'reaction'])
            ->selectRaw('COUNT(*) AS total')
            ->groupBy(['book_id', 'reaction'])
            ->orderByDesc('total')
            ->orderBy('reaction');
    }

    /**
     * Reacciones del usuario que realiza la petición. Debe precargarse siempre
     * acotada a un usuario (ver reactionEagerLoads()).
     *
     * @return HasMany<BookReaction, $this>
     */
    public function viewerReactions(): HasMany
    {
        return $this->hasMany(BookReaction::class);
    }

    /**
     * Entrada de biblioteca del usuario que realiza la petición (para viewer_rating).
     * Debe precargarse siempre acotada al usuario (ver reactionEagerLoads()).
     *
     * @return HasOne<LibraryEntry, $this>
     */
    public function viewerLibraryEntry(): HasOne
    {
        return $this->hasOne(LibraryEntry::class);
    }

    /**
     * Relaciones necesarias para serializar el bloque `reactions` y `viewer_rating`
     * del BookResource sin N+1. Admite un prefijo para precargas anidadas
     * (p. ej. 'book.' desde LibraryEntry o Review).
     *
     * @return array<int|string, string|Closure>
     */
    public static function reactionEagerLoads(?User $viewer, string $prefix = ''): array
    {
        $relations = [$prefix.'reactionCounts'];

        if ($viewer !== null) {
            $relations[$prefix.'viewerReactions'] = self::viewerReactionsConstraint($viewer);
            $relations[$prefix.'viewerLibraryEntry'] = self::viewerLibraryEntryConstraint($viewer);
        }

        return $relations;
    }

    /**
     * Restricción que acota viewerReactions al usuario indicado.
     *
     * @return Closure(HasMany<BookReaction, Book>): void
     */
    public static function viewerReactionsConstraint(User $viewer): Closure
    {
        return static function (HasMany $query) use ($viewer): void {
            $query->where('user_id', $viewer->getKey())
                ->orderBy('created_at')
                ->orderBy('reaction');
        };
    }

    /**
     * Restricción que acota viewerLibraryEntry al usuario indicado.
     *
     * @return Closure(HasOne<LibraryEntry, Book>): void
     */
    public static function viewerLibraryEntryConstraint(User $viewer): Closure
    {
        return static function (HasOne $query) use ($viewer): void {
            $query->select(['id', 'user_id', 'book_id', 'rating'])
                ->where('user_id', $viewer->getKey());
        };
    }
}
