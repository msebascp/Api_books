<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CollectionListController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ReadListController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WatchListController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/register', 'register')->withoutMiddleware('auth:sanctum');
        Route::post('/login', 'login')->withoutMiddleware('auth:sanctum');
        Route::post('/logout', 'logout');
        Route::get('/check_token', 'checkToken');
    });
    Route::controller(AuthorController::class)->group(function () {
        Route::get('/authors', 'index');
        Route::get('/authors/{id}', 'show');
        Route::post('/authors', 'store');
        Route::put('/authors/{id}', 'update');
        Route::delete('/authors/{id}', 'destroy');
    });
    Route::controller(BookController::class)->group(function () {
        Route::get('/books', 'index');
        Route::get('/books/{id}', 'show');
        Route::post('/books', 'store');
        Route::put('/books/{id}', 'update');
        Route::delete('/books/{id}', 'destroy');
    });
    Route::controller(CategoryController::class)->group(function () {
        Route::get('/categories', 'index');
        Route::get('/categories/{id}', 'show');
        Route::post('/categories', 'store');
        Route::put('/categories/{id}', 'update');
        Route::delete('/categories/{id}', 'destroy');
    });
    Route::controller(CommentController::class)->group(function () {
        Route::get('/comments', 'index');
        Route::get('/comments/{id}', 'show');
        Route::post('/comments', 'store');
        Route::put('/comments/{id}', 'update');
        Route::delete('/comments/{id}', 'destroy');
    });
    Route::controller(ReviewController::class)->group(function () {
        Route::get('/reviews', 'index');
        Route::get('/reviews/{id}', 'show');
        Route::post('/reviews', 'store');
        Route::put('/reviews/{id}', 'update');
        Route::delete('/reviews/{id}', 'destroy');
    });
    Route::controller(CollectionListController::class)->group(function () {
        Route::get('/user/collectionlist/{book_id}', 'store');
        Route::get('/user/collectionlist', 'index');
    });
    Route::controller(ReadListController::class)->group(function () {
        Route::get('/user/readlist/{book_id}', 'store');
        Route::get('/user/readlist', 'index');
        Route::get('/user/readlist/{book_id}', 'destroy');
        Route::get('books/{book_id}/like', 'like');
        Route::delete('books/{book_id}/like', 'unlike');
        Route::get('like_books', 'likeBooks');
    });
    Route::controller(WatchListController::class)->group(function () {
        Route::post('/books/watchlist/{book_id}', 'store');
        Route::get('/books/watchlist', 'index');
    });
});

