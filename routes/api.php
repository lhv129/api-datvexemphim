<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CinemaController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromocodeController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScreenController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\ShowtimeController;
use App\Http\Controllers\TicketController;
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
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
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

    //Api Roles
    Route::post('roles/create', [RoleController::class, 'store']);
    Route::put('roles/update/{id}', [RoleController::class, 'update']);
    Route::get('roles/show/{id}', [RoleController::class, 'show']);
    Route::delete('roles/delete/{id}', [RoleController::class, 'destroy']);

    // Api Banners
    Route::post('banners/create', [BannerController::class, 'store']);
    Route::put('banners/update/{id}', [BannerController::class, 'update']);
    Route::get('banners/show/{id}', [BannerController::class, 'show']);
    Route::delete('banners/delete/{id}', [BannerController::class, 'destroy']);

    // Api Provinces
    Route::post('provinces/create', [ProvinceController::class, 'store']);
    Route::put('provinces/update/{id}', [ProvinceController::class, 'update']);
    Route::get('provinces/show/{id}', [ProvinceController::class, 'show']);
    Route::delete('provinces/delete/{id}', [ProvinceController::class, 'destroy']);

    // Api Cinemas
    Route::post('cinemas/create', [CinemaController::class, 'store']);
    Route::put('cinemas/update/{id}', [CinemaController::class, 'update']);
    Route::get('cinemas/show/{id}', [CinemaController::class, 'show']);
    Route::delete('cinemas/delete/{id}', [CinemaController::class, 'destroy']);

    // Api Screens
    Route::post('screens/create', [ScreenController::class, 'store']);
    Route::put('screens/update/{id}', [ScreenController::class, 'update']);
    Route::get('screens/show/{id}', [ScreenController::class, 'show']);
    Route::delete('screens/delete/{id}', [ScreenController::class, 'destroy']);

    // Api Seats
    Route::post('seats/create', [SeatController::class, 'store']);
    Route::put('seats/update/{id}', [SeatController::class, 'update']);
    Route::get('seats/show/{id}', [SeatController::class, 'show']);
    Route::delete('seats/delete/{id}', [SeatController::class, 'destroy']);

    // Api Promo_codes
    Route::post('promocodes/create', [PromocodeController::class, 'store']);
    Route::put('promocodes/update/{id}', [PromocodeController::class, 'update']);
    Route::get('promocodes/show/{id}', [PromocodeController::class, 'show']);
    Route::delete('promocodes/delete/{id}', [PromocodeController::class, 'destroy']);

    // Api Showtimes
    Route::post('showtimes/create', [ShowtimeController::class, 'store']);
    Route::put('showtimes/update/{id}', [ShowtimeController::class, 'update']);
    Route::get('showtimes/show/{id}', [ShowtimeController::class, 'show']);
    Route::delete('showtimes/delete/{id}', [ShowtimeController::class, 'destroy']);

    // Api blogs
    Route::post('blogs/create', [BlogController::class, 'store']);
    Route::put('blogs/update/{id}', [BlogController::class, 'update']);
    Route::get('blogs/show/{id}', [BlogController::class, 'show']);
    Route::delete('blogs/delete/{id}', [BlogController::class, 'destroy']);

    //Api ticket
    Route::get('/admin/tickets', [TicketController::class, 'adminIndex']);
    Route::get('/admin/tickets/{id}', [TicketController::class, 'adminShow']);
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

// Api Provinces
Route::get('provinces', [ProvinceController::class, 'index']);

// Api Cinemas
Route::get('cinemas', [CinemaController::class, 'index']);

// Api Screens
Route::get('screens', [ScreenController::class, 'index']);

// Api Seats
Route::get('seats', [SeatController::class, 'index']);

// Api Promo_codes
Route::get('promocodes', [PromocodeController::class, 'index']);

// Api Showtimes
Route::get('showtimes', [ShowtimeController::class, 'index']);

// Api blogs
Route::get('blogs', [BlogController::class, 'index']);

Route::get('roles',[RoleController::class,'index']);

// Các route liên quan đến vé (cần đăng nhập)
Route::middleware(['auth:api'])->group(function () {
    Route::get('tickets', [TicketController::class, 'index']);     // Lấy danh sách vé của người dùng
    Route::post('tickets', [TicketController::class, 'store']);    // Đặt vé mới
    Route::get('tickets/{id}', [TicketController::class, 'show']); // Lấy chi tiết vé theo ID
});
