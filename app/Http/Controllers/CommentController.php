<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends Controller
{
    public function index(Request $request, Review $review): AnonymousResourceCollection
    {
        $comments = $review->comments()
            ->with('user')
            ->oldest()
            ->paginate($request->integer('per_page', 20));

        return CommentResource::collection($comments);
    }

    public function store(StoreCommentRequest $request, Review $review): CommentResource
    {
        /** @var User $user */
        $user = $request->user();

        $comment = Comment::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
            'content' => (string) $request->validated('content'),
        ]);

        $comment->load('user');

        return new CommentResource($comment);
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        abort_unless(
            $request->user()?->id === $comment->user_id,
            Response::HTTP_FORBIDDEN,
            'No tienes permiso para eliminar este comentario.'
        );

        $comment->delete();

        return response()->json([
            'message' => 'Comentario eliminado correctamente.',
        ]);
    }
}
