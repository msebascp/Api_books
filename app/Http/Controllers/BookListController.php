<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddBookToListRequest;
use App\Http\Requests\ReorderBookListRequest;
use App\Http\Requests\StoreBookListRequest;
use App\Http\Requests\UpdateBookListRequest;
use App\Http\Resources\BookListResource;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\BookList;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class BookListController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $lists = $user->bookLists()
            ->withCount('books')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        foreach ($lists->getCollection() as $list) {
            $list->setRelation('user', $user);
        }

        return BookListResource::collection($lists);
    }

    public function userLists(Request $request, string $username): AnonymousResourceCollection
    {
        $viewer = $this->viewer($request);

        $targetUserQuery = User::query();

        if ($viewer !== null) {
            $targetUserQuery->withExists(User::viewerFollowExistsConstraint($viewer));
        }

        $targetUser = $targetUserQuery
            ->where(function (Builder $q) use ($username): void {
                $q->where('username', $username)
                    ->when(
                        ctype_digit($username),
                        fn (Builder $sub): Builder => $sub->orWhere('id', (int) $username)
                    );
            })
            ->firstOrFail();

        $lists = $targetUser->bookLists()
            ->where('is_public', true)
            ->withCount('books')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        foreach ($lists->getCollection() as $list) {
            $list->setRelation('user', $targetUser);
        }

        return BookListResource::collection($lists);
    }

    public function show(Request $request, BookList $bookList): BookListResource
    {
        $viewer = $this->viewer($request);

        abort_if(
            ! $bookList->is_public && $viewer?->id !== $bookList->user_id,
            Response::HTTP_FORBIDDEN,
            'No tienes permiso para ver esta lista privada.'
        );

        if ($viewer !== null) {
            $bookList->load([
                'user' => static fn ($q) => $q->withExists(User::viewerFollowExistsConstraint($viewer)),
            ]);
        } else {
            $bookList->load('user');
        }

        $bookList->loadCount('books');

        return new BookListResource($bookList);
    }

    public function books(Request $request, BookList $bookList): AnonymousResourceCollection
    {
        $viewer = $this->viewer($request);

        abort_if(
            ! $bookList->is_public && $viewer?->id !== $bookList->user_id,
            Response::HTTP_FORBIDDEN,
            'No tienes permiso para ver los libros de esta lista privada.'
        );

        $validated = $request->validate([
            'sort' => ['nullable', 'string', Rule::in(['custom', 'added_desc', 'added_asc', 'rating_desc', 'rating_asc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $sort = $validated['sort'] ?? 'custom';

        $query = $bookList->books()
            ->with(['authors', 'genres', ...Book::reactionEagerLoads($viewer)])
            ->withCount(['libraryEntries', 'reviews']);

        match ($sort) {
            'added_desc' => $query
                ->orderByDesc('book_list_items.created_at')
                ->orderByDesc('book_list_items.position'),
            'added_asc' => $query
                ->orderBy('book_list_items.created_at')
                ->orderBy('book_list_items.position'),
            'rating_desc' => $query
                ->orderByDesc('books.avg_rating')
                ->orderByDesc('books.ratings_count')
                ->orderBy('books.id'),
            'rating_asc' => $query
                ->orderByRaw('CASE WHEN books.ratings_count = 0 THEN 1 ELSE 0 END ASC')
                ->orderBy('books.avg_rating')
                ->orderByDesc('books.ratings_count')
                ->orderBy('books.id'),
            default => $query
                ->orderBy('book_list_items.position')
                ->orderBy('book_list_items.book_id'),
        };

        $perPage = (int) ($validated['per_page'] ?? 15);

        return BookResource::collection($query->paginate($perPage));
    }

    public function store(StoreBookListRequest $request): BookListResource
    {
        /** @var User $user */
        $user = $request->user();

        $bookList = $user->bookLists()->create($request->validated());
        $bookList->setRelation('user', $user);
        $bookList->loadCount('books');

        return new BookListResource($bookList);
    }

    public function update(UpdateBookListRequest $request, BookList $bookList): BookListResource
    {
        /** @var User $user */
        $user = $request->user();

        $bookList->update($request->validated());
        $bookList->setRelation('user', $user);
        $bookList->loadCount('books');

        return new BookListResource($bookList);
    }

    public function destroy(Request $request, BookList $bookList): JsonResponse
    {
        abort_unless(
            $request->user()?->id === $bookList->user_id,
            Response::HTTP_FORBIDDEN,
            'No tienes permiso para eliminar esta lista.'
        );

        $bookList->delete();

        return response()->json([
            'message' => 'Lista eliminada correctamente.',
        ]);
    }

    public function addBook(AddBookToListRequest $request, BookList $bookList): BookListResource
    {
        /** @var User $user */
        $user = $request->user();
        $bookId = (int) $request->validated('book_id');

        try {
            DB::transaction(function () use ($bookList, $bookId): void {
                $maxPosition = (int) DB::table('book_list_items')
                    ->where('book_list_id', $bookList->id)
                    ->lockForUpdate()
                    ->max('position');

                DB::table('book_list_items')->insert([
                    'book_list_id' => $bookList->id,
                    'book_id' => $bookId,
                    'position' => $maxPosition + 1,
                    'created_at' => now(),
                ]);

                $bookList->touch();
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'book_id' => ['El libro ya se encuentra en esta lista.'],
            ]);
        }

        $bookList->setRelation('user', $user);
        $bookList->loadCount('books');

        return (new BookListResource($bookList))->additional([
            'message' => 'Libro añadido a la lista correctamente.',
        ]);
    }

    public function removeBook(Request $request, BookList $bookList, Book $book): JsonResponse
    {
        abort_unless(
            $request->user()?->id === $bookList->user_id,
            Response::HTTP_FORBIDDEN,
            'No tienes permiso para modificar esta lista.'
        );

        DB::transaction(function () use ($bookList, $book): void {
            $deleted = DB::table('book_list_items')
                ->where('book_list_id', $bookList->id)
                ->where('book_id', $book->id)
                ->delete();

            abort_if($deleted === 0, Response::HTTP_NOT_FOUND, 'El libro no pertenece a esta lista.');

            $remainingBookIds = DB::table('book_list_items')
                ->where('book_list_id', $bookList->id)
                ->orderBy('position')
                ->orderBy('book_id')
                ->pluck('book_id')
                ->all();

            foreach ($remainingBookIds as $index => $remainingBookId) {
                DB::table('book_list_items')
                    ->where('book_list_id', $bookList->id)
                    ->where('book_id', $remainingBookId)
                    ->update(['position' => $index + 1]);
            }

            $bookList->touch();
        });

        return response()->json([
            'message' => 'Libro eliminado de la lista correctamente.',
        ]);
    }

    public function reorder(ReorderBookListRequest $request, BookList $bookList): BookListResource
    {
        /** @var User $user */
        $user = $request->user();
        /** @var list<int> $bookIds */
        $bookIds = array_map(
            static fn (mixed $id): int => (int) $id,
            (array) $request->validated('book_ids')
        );

        DB::transaction(function () use ($bookList, $bookIds): void {
            foreach ($bookIds as $index => $bookId) {
                DB::table('book_list_items')
                    ->where('book_list_id', $bookList->id)
                    ->where('book_id', $bookId)
                    ->update(['position' => $index + 1]);
            }

            $bookList->touch();
        });

        $bookList->setRelation('user', $user);
        $bookList->loadCount('books');

        return (new BookListResource($bookList))->additional([
            'message' => 'Orden de la lista actualizado correctamente.',
        ]);
    }
}
