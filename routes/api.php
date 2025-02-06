<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Api Login,logout
Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {

    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('profile', [AuthController::class, 'profile']);
});

// Các route chỉ dành cho addmin
Route::middleware(['auth:api', 'checkRole:1'])->group(function () {

    // Api Products
    Route::post('products/create', [ProductController::class, 'store']);
    Route::put('products/update/{id}', [ProductController::class, 'update']);
    Route::get('products/show/{id}', [ProductController::class, 'show']);
    Route::delete('products/delete/{id}', [ProductController::class, 'destroy']);

    // Api Movies
    Route::post('movies/create', [MovieController::class, 'store']);
    Route::put('movies/update/{id}', [MovieController::class, 'update']);
    Route::get('movies/show/{id}', [MovieController::class, 'show']);
    Route::delete('movies/delete/{id}', [MovieController::class, 'destroy']);

    // Api Banners
    Route::post('banners/create', [BannerController::class, 'store']);
    Route::put('banners/update/{id}', [BannerController::class, 'update']);
    Route::get('banners/show/{id}', [BannerController::class, 'show']);
    Route::delete('banners/delete/{id}', [BannerController::class, 'destroy']);
});


// Api không cần đăng nhập
// Api Products
Route::get('products', [ProductController::class, 'index']);

// Api Movies
Route::get('movies', [MovieController::class, 'index']);

// Api Genres
Route::get('genres', [GenreController::class, 'index']);

// Api Banners
Route::get('banners', [BannerController::class, 'index']);
