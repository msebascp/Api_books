<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookListController;
use App\Http\Controllers\BookRatingController;
use App\Http\Controllers\BookReactionController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\LibraryEntryController;
use App\Http\Controllers\ReadingLogController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
*/

// Autenticación
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

// Catálogo
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{book}', [BookController::class, 'show'])->whereNumber('book')->name('books.show');

Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
Route::get('/authors/{author}', [AuthorController::class, 'show'])->whereNumber('author')->name('authors.show');

Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
Route::get('/genres/{genre}', [GenreController::class, 'show'])->whereNumber('genre')->name('genres.show');

// Social de lectura
Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.feed');
Route::get('/reviews/{review}', [ReviewController::class, 'show'])->whereNumber('review')->name('reviews.show');
Route::get('/books/{book}/reviews', [ReviewController::class, 'index'])->whereNumber('book')->name('books.reviews.index');
Route::get('/reviews/{review}/comments', [CommentController::class, 'index'])->whereNumber('review')->name('reviews.comments.index');

Route::get('/users/{username}', [UserController::class, 'show'])->name('users.show');
Route::get('/users/{username}/reading-logs', [ReadingLogController::class, 'userIndex'])->name('users.reading-logs.index');
Route::get('/users/{username}/lists', [BookListController::class, 'userLists'])->name('users.lists.index');
Route::get('/users/{user}/followers', [FollowController::class, 'followers'])->name('users.followers');
Route::get('/users/{user}/following', [FollowController::class, 'following'])->name('users.following');

Route::get('/lists/{bookList}', [BookListController::class, 'show'])->whereNumber('bookList')->name('lists.show');
Route::get('/lists/{bookList}/books', [BookListController::class, 'books'])->whereNumber('bookList')->name('lists.books.index');

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function (): void {
    // Sesión y perfil autenticado
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');

    // Mi Biblioteca
    Route::get('/library-entries', [LibraryEntryController::class, 'index'])->name('library-entries.index');
    Route::post('/library-entries', [LibraryEntryController::class, 'storeOrUpdate'])->name('library-entries.store');
    Route::delete('/library-entries/{book}', [LibraryEntryController::class, 'destroy'])->whereNumber('book')->name('library-entries.destroy');

    // Historial de lecturas y relecturas (Diary / Plays)
    Route::get('/reading-logs', [ReadingLogController::class, 'index'])->name('reading-logs.index');
    Route::post('/books/{book}/reading-logs', [ReadingLogController::class, 'store'])->whereNumber('book')->name('books.reading-logs.store');
    Route::put('/reading-logs/{readingLog}', [ReadingLogController::class, 'update'])->whereNumber('readingLog')->name('reading-logs.update');
    Route::delete('/reading-logs/{readingLog}', [ReadingLogController::class, 'destroy'])->whereNumber('readingLog')->name('reading-logs.destroy');

    // Listas personalizadas de libros
    Route::get('/lists', [BookListController::class, 'index'])->name('lists.index');
    Route::post('/lists', [BookListController::class, 'store'])->name('lists.store');
    Route::put('/lists/{bookList}', [BookListController::class, 'update'])->whereNumber('bookList')->name('lists.update');
    Route::delete('/lists/{bookList}', [BookListController::class, 'destroy'])->whereNumber('bookList')->name('lists.destroy');
    Route::post('/lists/{bookList}/books', [BookListController::class, 'addBook'])->whereNumber('bookList')->name('lists.books.store');
    Route::put('/lists/{bookList}/books/reorder', [BookListController::class, 'reorder'])->whereNumber('bookList')->name('lists.books.reorder');
    Route::delete('/lists/{bookList}/books/{book}', [BookListController::class, 'removeBook'])->whereNumber('bookList')->whereNumber('book')->withTrashed()->name('lists.books.destroy');

    // Interacciones sobre reseñas
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->whereNumber('book')->name('books.reviews.store');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->whereNumber('review')->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->whereNumber('review')->name('reviews.destroy');
    Route::post('/reviews/{review}/like', [ReviewController::class, 'toggleLike'])->whereNumber('review')->name('reviews.like');

    // Comentarios
    Route::post('/reviews/{review}/comments', [CommentController::class, 'store'])->whereNumber('review')->name('reviews.comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->whereNumber('comment')->name('comments.destroy');

    // Social
    Route::post('/users/{user}/follow', [FollowController::class, 'toggleFollow'])->name('users.follow');

    // Puntuación del libro (0.5 a 5.0 en pasos de 0.5; 0 o null elimina la puntuación)
    Route::put('/books/{book}/rating', [BookRatingController::class, 'upsert'])->whereNumber('book')->name('books.rating.upsert');

    // Reacciones con emoji (estilo Trakt TV): PUT idempotente, reemplaza el set completo
    Route::put('/books/{book}/reactions', [BookReactionController::class, 'sync'])->whereNumber('book')->name('books.reactions.sync');

    // Gestión de Catálogo (Rol Admin)
    Route::middleware('admin')->group(function (): void {
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
        Route::put('/books/{book}', [BookController::class, 'update'])->whereNumber('book')->name('books.update');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])->whereNumber('book')->name('books.destroy');
        Route::post('/books/{book}/restore', [BookController::class, 'restore'])->whereNumber('book')->withTrashed()->name('books.restore');

        Route::post('/authors', [AuthorController::class, 'store'])->name('authors.store');
        Route::put('/authors/{author}', [AuthorController::class, 'update'])->whereNumber('author')->name('authors.update');
        Route::delete('/authors/{author}', [AuthorController::class, 'destroy'])->whereNumber('author')->name('authors.destroy');

        Route::post('/genres', [GenreController::class, 'store'])->name('genres.store');
        Route::put('/genres/{genre}', [GenreController::class, 'update'])->whereNumber('genre')->name('genres.update');
        Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])->whereNumber('genre')->name('genres.destroy');
    });
});
