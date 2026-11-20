<?php

declare(strict_types=1);

namespace App\Models;

use Closure;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

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
        'avg_rating',
        'ratings_count',
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
     * Relaciones necesarias para serializar el bloque `reactions` del
     * BookResource sin N+1. Admite un prefijo para precargas anidadas
     * (p. ej. 'book.' desde LibraryEntry o Review).
     *
     * @return array<int|string, string|Closure>
     */
    public static function reactionEagerLoads(?User $viewer, string $prefix = ''): array
    {
        $relations = [$prefix.'reactionCounts'];

        if ($viewer !== null) {
            $relations[$prefix.'viewerReactions'] = self::viewerReactionsConstraint($viewer);
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
}
