<?php

use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

Route::get('/login', function () {
    return 'login page';
})->name('login');

Route::resource('posts', PostController::class);

// Route::get('/posts', [PostController::class, 'index']);
// Route::get('/posts/{post}', [PostController::class, 'show']);

// Route::middleware('auth')->group(function () {
//     Route::get('/post/create/', [PostController::class, 'create']);
//     Route::post('/posts', [PostController::class, 'store']);

//     Route::get('/posts/{post}/edit', [PostController::class, 'edit']);
//     Route::put('/posts/{post}', [PostController::class, 'update']);
//     Route::patch('/posts/{post}', [PostController::class, 'update']);
//     Route::delete('/posts/{post}', [PostController::class, 'destroy']);
// });
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
