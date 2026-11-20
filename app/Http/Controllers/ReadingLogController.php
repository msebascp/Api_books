<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreReadingLogRequest;
use App\Http\Requests\UpdateReadingLogRequest;
use App\Http\Resources\ReadingLogResource;
use App\Models\Book;
use App\Models\ReadingLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ReadingLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return $this->paginatedLogsForUser($request, $user, $user);
    }

    public function userIndex(Request $request, string $username): AnonymousResourceCollection
    {
        $targetUser = User::query()
            ->where('username', $username)
            ->when(
                ctype_digit($username),
                fn (Builder $query): Builder => $query->orWhere('id', (int) $username)
            )
            ->firstOrFail();

        return $this->paginatedLogsForUser($request, $targetUser, $this->viewer($request));
    }

    public function store(StoreReadingLogRequest $request, Book $book): ReadingLogResource
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $isReread = array_key_exists('is_reread', $validated)
            ? (bool) $validated['is_reread']
            : ReadingLog::query()
                ->where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->exists();

        $readingLog = ReadingLog::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'started_at' => $validated['started_at'] ?? null,
            'finished_at' => $validated['finished_at'] ?? null,
            'is_reread' => $isReread,
        ]);

        $readingLog->load(['book.authors', 'book.genres', ...Book::reactionEagerLoads($user, 'book.')]);

        return new ReadingLogResource($readingLog);
    }

    public function update(UpdateReadingLogRequest $request, ReadingLog $readingLog): ReadingLogResource
    {
        /** @var User $user */
        $user = $request->user();

        $readingLog->update($request->validated());
        $readingLog->load(['book.authors', 'book.genres', ...Book::reactionEagerLoads($user, 'book.')]);

        return new ReadingLogResource($readingLog);
    }

    public function destroy(Request $request, ReadingLog $readingLog): JsonResponse
    {
        abort_unless(
            $request->user()?->id === $readingLog->user_id,
            Response::HTTP_FORBIDDEN,
            'No tienes permiso para eliminar este registro de lectura.'
        );

        $readingLog->delete();

        return response()->json([
            'message' => 'Registro de lectura eliminado correctamente.',
        ]);
    }

    private function paginatedLogsForUser(
        Request $request,
        User $owner,
        ?User $viewer,
    ): AnonymousResourceCollection {
        $query = $owner->readingLogs()
            ->with(['book.authors', 'book.genres', ...Book::reactionEagerLoads($viewer, 'book.')]);

        if ($request->filled('year')) {
            $query->whereYear('finished_at', $request->integer('year'));
        }

        if ($request->filled('book_id')) {
            $query->where('book_id', $request->integer('book_id'));
        }

        $logs = $query
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return ReadingLogResource::collection($logs);
    }
}
