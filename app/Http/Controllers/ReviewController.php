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
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ReviewController extends Controller
{
    public function index(Request $request, ?Book $book = null): AnonymousResourceCollection
    {
        $viewer = $this->viewer($request);

        $userRelation = $viewer !== null
            ? ['user' => static fn ($q) => $q->withExists(User::viewerFollowExistsConstraint($viewer))]
            : ['user'];

        $relations = $book !== null
            ? $userRelation
            : [...$userRelation, 'book', ...Book::reactionEagerLoads($viewer, 'book.')];

        $query = Review::query()
            ->with($relations)
            ->withCount(['likes', 'comments'])
            ->when(
                $book !== null,
                fn (Builder $q): Builder => $q->where('book_id', $book->id)
            );

        if ($viewer !== null) {
            $query->withExists(Review::viewerLikeExistsConstraint($viewer));
        }

        if ($request->has('has_content')) {
            if ($request->boolean('has_content')) {
                $query->whereNotNull('content')->where('content', '<>', '');
            } else {
                $query->where(function (Builder $q): void {
                    $q->whereNull('content')->orWhere('content', '');
                });
            }
        }

        $reviews = $query
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ReviewResource::collection($reviews);
    }

    public function show(Request $request, Review $review): ReviewResource
    {
        $viewer = $this->viewer($request);

        if ($viewer !== null) {
            $review->load([
                'user' => static fn ($q) => $q->withExists(User::viewerFollowExistsConstraint($viewer)),
                'book',
                'comments.user' => static fn ($q) => $q->withExists(User::viewerFollowExistsConstraint($viewer)),
                ...Book::reactionEagerLoads($viewer, 'book.'),
            ])
                ->loadCount(['likes', 'comments'])
                ->loadExists(Review::viewerLikeExistsConstraint($viewer));
        } else {
            $review->load([
                'user',
                'book',
                'comments.user',
                ...Book::reactionEagerLoads(null, 'book.'),
            ])->loadCount(['likes', 'comments']);
        }

        return new ReviewResource($review);
    }

    public function store(StoreReviewRequest $request, Book $book): ReviewResource
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        try {
            $review = Review::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $validated['rating'],
                'content' => $validated['content'] ?? null,
                'has_spoilers' => $validated['has_spoilers'] ?? false,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'book_id' => ['Ya has publicado una reseña para este libro.'],
            ]);
        }

        $review->setRelation('user', $user);
        $review->setAttribute('liked_by_viewer', false);
        $review->loadCount(['likes', 'comments']);

        return new ReviewResource($review);
    }

    public function update(UpdateReviewRequest $request, Review $review): ReviewResource
    {
        /** @var User $user */
        $user = $request->user();

        $review->update($request->validated());
        $review->setRelation('user', $user);
        $review->loadCount(['likes', 'comments'])
            ->loadExists(Review::viewerLikeExistsConstraint($user));

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

        $review->load([
            'user' => static fn ($q) => $q->withExists(User::viewerFollowExistsConstraint($user)),
        ])->loadCount(['likes', 'comments']);
        $review->setAttribute('liked_by_viewer', $liked);

        return (new ReviewResource($review))->additional([
            'liked' => $liked,
            'message' => $liked ? 'Like añadido a la reseña.' : 'Like eliminado de la reseña.',
        ]);
    }
}
