<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IndexLibraryEntryRequest;
use App\Http\Requests\LibraryEntryRequest;
use App\Http\Resources\LibraryEntryResource;
use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class LibraryEntryController extends Controller
{
    public function index(IndexLibraryEntryRequest $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $query = $user->libraryEntries()
            ->with(['book.authors', 'book.genres']);

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if ($request->has('is_owned')) {
            $query->where('is_owned', $request->boolean('is_owned'));
        }

        if ($request->has('is_favorite')) {
            $query->where('is_favorite', $request->boolean('is_favorite'));
        }

        $perPage = (int) ($validated['per_page'] ?? 15);

        return LibraryEntryResource::collection(
            $query->latest('updated_at')->paginate($perPage)
        );
    }

    public function storeOrUpdate(LibraryEntryRequest $request): LibraryEntryResource
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $entry = LibraryEntry::updateOrCreate(
            [
                'user_id' => $user->id,
                'book_id' => $validated['book_id'],
            ],
            Arr::except($validated, ['book_id'])
        );

        $entry->load(['book.authors', 'book.genres']);

        return new LibraryEntryResource($entry);
    }

    public function destroy(Request $request, Book $book): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $entry = LibraryEntry::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->firstOrFail();

        $entry->delete();

        return response()->json([
            'message' => 'Libro eliminado de tu biblioteca.',
        ]);
    }
}
