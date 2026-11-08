<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AuthorController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $authors = Author::query()
            ->withCount('books')
            ->when(
                $request->filled('search'),
                fn (Builder $query): Builder => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return AuthorResource::collection($authors);
    }

    public function show(Author $author): AuthorResource
    {
        $author->load(['books.genres'])->loadCount('books');

        return new AuthorResource($author);
    }

    public function store(StoreAuthorRequest $request): AuthorResource
    {
        $author = Author::create($request->validated());

        return new AuthorResource($author);
    }

    public function update(UpdateAuthorRequest $request, Author $author): AuthorResource
    {
        $author->update($request->validated());

        return new AuthorResource($author);
    }

    public function destroy(Author $author): JsonResponse
    {
        DB::transaction(function () use ($author): void {
            $author->books()->detach();
            $author->delete();
        });

        return response()->json([
            'message' => 'Autor eliminado correctamente.',
        ]);
    }
}
