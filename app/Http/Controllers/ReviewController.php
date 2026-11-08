<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ReviewController extends Controller
{
    public function index(Request $request, ?Book $book = null): AnonymousResourceCollection
    {
        $relations = $book !== null ? ['user'] : ['user', 'book'];

        $reviews = Review::query()
            ->with($relations)
            ->withCount(['likes', 'comments'])
            ->when(
                $book !== null,
                fn (Builder $query): Builder => $query->where('book_id', $book->id)
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ReviewResource::collection($reviews);
    }

    public function show(Review $review): ReviewResource
    {
        $review->load(['user', 'book', 'comments.user'])->loadCount(['likes', 'comments']);

        return new ReviewResource($review);
    }

    public function store(StoreReviewRequest $request, Book $book): ReviewResource
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => $validated['rating'],
            'content' => $validated['content'] ?? null,
            'has_spoilers' => $validated['has_spoilers'] ?? false,
        ]);

        $review->load('user')->loadCount(['likes', 'comments']);

        return new ReviewResource($review);
    }

    public function update(UpdateReviewRequest $request, Review $review): ReviewResource
    {
        $review->update($request->validated());
        $review->load('user')->loadCount(['likes', 'comments']);

        return new ReviewResource($review);
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        abort_unless(
            $request->user()?->id === $review->user_id,
            Response::HTTP_FORBIDDEN,
            'No tienes permiso para eliminar esta reseña.'
        );

        $review->delete();

        return response()->json([
            'message' => 'Reseña eliminada correctamente.',
        ]);
    }

    public function toggleLike(Request $request, Review $review): ReviewResource
    {
        /** @var User $user */
        $user = $request->user();

        $changes = $review->likes()->toggle($user->id);
        $liked = count($changes['attached']) > 0;

        $review->load('user')->loadCount(['likes', 'comments']);

        return (new ReviewResource($review))->additional([
            'liked' => $liked,
            'message' => $liked ? 'Like añadido a la reseña.' : 'Like eliminado de la reseña.',
        ]);
    }
}
