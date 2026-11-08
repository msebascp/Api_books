<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GenreRequest;
use App\Http\Resources\GenreResource;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class GenreController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $genres = Genre::query()
            ->withCount('books')
            ->when(
                $request->filled('search'),
                fn (Builder $query): Builder => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return GenreResource::collection($genres);
    }

    public function show(Genre $genre): GenreResource
    {
        $genre->load(['books.authors'])->loadCount('books');

        return new GenreResource($genre);
    }

    public function store(GenreRequest $request): GenreResource
    {
        $genre = Genre::create($request->validated());

        return new GenreResource($genre);
    }

    public function update(GenreRequest $request, Genre $genre): GenreResource
    {
        $genre->update($request->validated());

        return new GenreResource($genre);
    }

    public function destroy(Genre $genre): JsonResponse
    {
        DB::transaction(function () use ($genre): void {
            $genre->books()->detach();
            $genre->delete();
        });

        return response()->json([
            'message' => 'Género eliminado correctamente.',
        ]);
    }
}
