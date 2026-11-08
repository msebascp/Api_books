<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index(IndexBookRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $query = Book::query()
            ->with(['authors', 'genres'])
            ->withCount(['libraryEntries', 'reviews']);

        if (! empty($validated['search'])) {
            $search = (string) $validated['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        if (! empty($validated['genre'])) {
            $genre = (string) $validated['genre'];
            $query->whereHas('genres', function (Builder $q) use ($genre): void {
                if (ctype_digit($genre)) {
                    $q->where('genres.id', (int) $genre);
                } else {
                    $q->where('genres.slug', $genre);
                }
            });
        }

        if (! empty($validated['author'])) {
            $author = (string) $validated['author'];
            $query->whereHas('authors', function (Builder $q) use ($author): void {
                if (ctype_digit($author)) {
                    $q->where('authors.id', (int) $author);
                } else {
                    $q->where('authors.name', 'like', "%{$author}%");
                }
            });
        }

        $sort = $validated['sort'] ?? 'latest';

        match ($sort) {
            'avg_rating' => $query->orderByDesc('avg_rating')->orderByDesc('ratings_count'),
            'title' => $query->orderBy('title'),
            'publication_date' => $query->orderByDesc('publication_date')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        $perPage = (int) ($validated['per_page'] ?? 15);

        return BookResource::collection($query->paginate($perPage));
    }

    public function show(Book $book): BookResource
    {
        $book->load(['authors', 'genres'])->loadCount(['libraryEntries', 'reviews']);

        return new BookResource($book);
    }

    public function store(StoreBookRequest $request): BookResource
    {
        $validated = $request->validated();

        $book = DB::transaction(function () use ($validated): Book {
            $book = Book::create(Arr::except($validated, ['authors', 'genres']));

            $book->authors()->sync($validated['authors'] ?? []);
            $book->genres()->sync($validated['genres'] ?? []);

            return $book;
        });

        $book->load(['authors', 'genres'])->loadCount(['libraryEntries', 'reviews']);

        return new BookResource($book);
    }

    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $validated = $request->validated();

        DB::transaction(function () use ($book, $validated): void {
            $book->update(Arr::except($validated, ['authors', 'genres']));

            if (array_key_exists('authors', $validated)) {
                $book->authors()->sync($validated['authors']);
            }

            if (array_key_exists('genres', $validated)) {
                $book->genres()->sync($validated['genres']);
            }
        });

        $book->load(['authors', 'genres'])->loadCount(['libraryEntries', 'reviews']);

        return new BookResource($book);
    }

    public function destroy(Book $book): JsonResponse
    {
        DB::transaction(function () use ($book): void {
            $book->authors()->detach();
            $book->genres()->detach();
            $book->delete();
        });

        return response()->json([
            'message' => 'Libro eliminado correctamente.',
        ]);
    }
}
