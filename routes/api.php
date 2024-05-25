<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CollectBookController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\ReadBookController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WatchBookController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/register', 'register')->withoutMiddleware('auth:sanctum');
        Route::post('/login', 'login')->withoutMiddleware('auth:sanctum');
        Route::get('/logout', 'logout');
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
    Route::controller(CollectBookController::class)->group(function () {
        Route::get('/collectionbooks/user/{user_id?}', 'index');
        Route::get('/collectionbooks/{book_id}', 'store');
        Route::delete('/collectionbooks/{book_id}', 'destroy');
    });
    Route::controller(FollowController::class)->group(function () {
        Route::get('/follows/followers/{user_id}', 'index_followers');
        Route::get('/follows/following/{user_id}', 'index_following');
        Route::get('/follows/{user_id}', 'store');
        Route::delete('/follows/{user_id}', 'destroy');
    });
    Route::controller(ReadBookController::class)->group(function () {
        Route::get('/readbooks/user/{user_id?}', 'index');
        Route::get('/readbooks/read/{book_id}', 'store');
        Route::delete('/readbooks/read/{book_id}', 'destroy');
        Route::get('/readbooks/book/{book_id}', 'show');
        Route::get('/readbooks/like/{book_id}', 'like');
        Route::delete('/readbooks/like/{book_id}', 'unlike');
        Route::get('/like_books', 'likeBooks');
    });
    Route::controller(ReviewController::class)->group(function () {
        Route::get('/reviews/user/{user_id?}', 'index_user');
        Route::get('/reviews/book/{book_id}', 'index_book');
        Route::get('/reviews/{id}', 'show');
        Route::post('/reviews', 'store');
        Route::put('/reviews/{id}', 'update');
        Route::delete('/reviews/{id}', 'destroy');
    });
    Route::controller(UserController::class)->group(function () {
        Route::get('/users/{user_id?}', 'show');
    });
    Route::controller(WatchBookController::class)->group(function () {
        Route::get('/watchbooks/user/{user_id?}', 'index');
        Route::get('/watchbooks/{book_id}', 'store');
        Route::delete('/watchbooks/{book_id}', 'destroy');
    });
});

