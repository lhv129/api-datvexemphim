<?php

use App\Http\Controllers\ActorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CinemaController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromocodeController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScreenController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\ShowtimeController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerifyEmailController;
use App\Http\Requests\ForgotPasswordRequest;
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
    Route::get('verify-email/{token}', [AuthController::class, 'verifyEmail'])->name('verify-email');

    //Login with google
    Route::get('google/url', [AuthController::class, 'loginUrl']);
    Route::get('google/callback', [AuthController::class, 'loginCallback']);

    // Forget Password
    Route::post('forgot-password/get-token', [ForgotPasswordController::class, 'sendToken']);
    Route::put('forgot-password/verify', [ForgotPasswordController::class, 'restorePassword']);
});

// Các route chỉ dành cho user
Route::middleware(['auth:api', 'checkRole:1,2,3'])->group(function () {
    //Api cần đăng nhập nhưng cả user lẫn admin đều dùng được
    //Api reviews
    Route::post('reviews/create', [ReviewController::class, 'store']);
    Route::put('reviews/update/{id}', [ReviewController::class, 'update']);
    Route::delete('reviews/delete/{id}', [ReviewController::class, 'destroy']);

    //Api update profile
    Route::put('profile/update', [AuthController::class, 'updateProfile']);

    // Route dành cho admin,stafff
    Route::middleware(['auth:api', 'checkRole:1,2'])->group(function () {


        //Api ticket
        Route::get('/admin/tickets', [TicketController::class, 'adminIndex']);
        Route::get('/admin/tickets/{id}', [TicketController::class, 'adminShow'])->name('adminShow');
        Route::post('/admin/tickets/check', [TicketController::class, 'checkTicket']);
        Route::post('admin/tickets/confirm', [TicketController::class, 'confirmTicketUsage']);
    });

    // Route dành cho admin
    Route::middleware(['auth:api', 'checkRole:1'])->group(function () {
        Route::get('reviews', [ReviewController::class, 'index']);

        // Api Products
        Route::post('products/create', [ProductController::class, 'store']);
        Route::put('products/update/{id}', [ProductController::class, 'update']);
        Route::delete('products/delete/{id}', [ProductController::class, 'destroy']);

        // Api Movies
        Route::post('movies/create', [MovieController::class, 'store']);
        Route::put('movies/update/{id}', [MovieController::class, 'update']);
        Route::delete('movies/delete/{id}', [MovieController::class, 'destroy']);

        //Api Roles
        Route::post('roles/create', [RoleController::class, 'store']);
        Route::put('roles/update/{id}', [RoleController::class, 'update']);
        Route::delete('roles/delete/{id}', [RoleController::class, 'destroy']);

        // Api Banners
        Route::post('banners/create', [BannerController::class, 'store']);
        Route::put('banners/update/{id}', [BannerController::class, 'update']);
        Route::delete('banners/delete/{id}', [BannerController::class, 'destroy']);

        // Api Provinces
        Route::post('provinces/create', [ProvinceController::class, 'store']);
        Route::put('provinces/update/{id}', [ProvinceController::class, 'update']);
        Route::delete('provinces/delete/{id}', [ProvinceController::class, 'destroy']);

        // Api Cinemas
        Route::post('cinemas/create', [CinemaController::class, 'store']);
        Route::put('cinemas/update/{id}', [CinemaController::class, 'update']);
        Route::delete('cinemas/delete/{id}', [CinemaController::class, 'destroy']);

        // Api Screens
        Route::post('screens/create', [ScreenController::class, 'store']);
        Route::put('screens/update/{id}', [ScreenController::class, 'update']);
        Route::delete('screens/delete/{id}', [ScreenController::class, 'destroy']);

        // Api Seats
        Route::post('seats/create', [SeatController::class, 'store']);
        Route::put('seats/update/{id}', [SeatController::class, 'update']);
        Route::delete('seats/delete/{id}', [SeatController::class, 'destroy']);

        // Api Promo_codes
        Route::post('promocodes/create', [PromocodeController::class, 'store']);
        Route::put('promocodes/update/{id}', [PromocodeController::class, 'update']);
        Route::delete('promocodes/delete/{id}', [PromocodeController::class, 'destroy']);

        // Api Showtimes
        Route::post('showtimes/create', [ShowtimeController::class, 'store']);
        Route::put('showtimes/update/{id}', [ShowtimeController::class, 'update']);
        Route::delete('showtimes/delete/{id}', [ShowtimeController::class, 'destroy']);

        // Api blogs
        Route::post('blogs/create', [BlogController::class, 'store']);
        Route::put('blogs/update/{id}', [BlogController::class, 'update']);
        Route::delete('blogs/delete/{id}', [BlogController::class, 'destroy']);

        //Api users
        Route::get('users', [UserController::class, 'index']);
        Route::post('users/create', [UserController::class, 'store']);
        Route::put('users/update/{id}', [UserController::class, 'update']);
        Route::get('users/show/{id}', [UserController::class, 'show']);
        Route::delete('users/delete/{id}', [UserController::class, 'destroy']);

        //Api genres
        Route::get('genres', [GenreController::class, 'index']);
        Route::post('genres/create', [GenreController::class, 'store']);
        Route::put('genres/update/{id}', [GenreController::class, 'update']);
        Route::get('genres/show/{id}', [GenreController::class, 'show']);
        Route::delete('genres/delete/{id}', [GenreController::class, 'destroy']);

        //Api actors
        Route::get('actors', [ActorController::class, 'index']);
        Route::post('actors/create', [ActorController::class, 'store']);
        Route::put('actors/update/{id}', [ActorController::class, 'update']);
        Route::get('actors/show/{id}', [ActorController::class, 'show']);
        Route::delete('actors/delete/{id}', [ActorController::class, 'destroy']);
    });
});

// Api không cần đăng nhập

// Api Products
Route::get('products', [ProductController::class, 'index']);
Route::get('products/show/{id}', [ProductController::class, 'show']);

// Api Movies
Route::get('movies', [MovieController::class, 'index']);
Route::get('moviesShowing', [MovieController::class, 'moviesShowing']);
Route::get('moviesUpcoming', [MovieController::class, 'moviesUpcoming']);
Route::get('movies/show/{id}', [MovieController::class, 'show']);
Route::post('movies/search', [MovieController::class, 'searchMovie']);

// Api Genres
Route::get('genres', [GenreController::class, 'index']);


// Api Banners
Route::get('banners', [BannerController::class, 'index']);
Route::get('banners/show/{id}', [BannerController::class, 'show']);

// Api Provinces
Route::get('provinces', [ProvinceController::class, 'index']);
Route::get('provinces/show/{id}', [ProvinceController::class, 'show']);

// Api Cinemas
Route::get('cinemas', [CinemaController::class, 'index']);
Route::post('cinemas', [CinemaController::class, 'getAllByProvinceId']);
Route::get('cinemas/show/{id}', [CinemaController::class, 'show']);

// Api Screens
Route::get('screens', [ScreenController::class, 'index']);
Route::post('get-screen-by-cinema', [ScreenController::class, 'getAllByCinemaId']);
Route::get('screens/show/{id}', [ScreenController::class, 'show']);

// Api Seats
Route::get('seats', [SeatController::class, 'index']);
Route::post('get-seat-by-screen', [SeatController::class, 'getAllByScreenId']);
Route::post('get-seat-by-showtime', [SeatController::class, 'getSeatsByShowtime']);
Route::get('seats/show/{id}', [SeatController::class, 'show']);

// Api Promo_codes
Route::get('promocodes', [PromocodeController::class, 'index']);
Route::get('promocodes/show/{id}', [PromocodeController::class, 'show']);

// Api Showtimes
Route::get('showtimes', [ShowtimeController::class, 'index']);
Route::post('showtimes/getAllByDate', [ShowtimeController::class, 'getAllByDate']);
Route::post('showtimes/getAllByMovieTitle', [ShowtimeController::class, 'getAllByMovieTitle']);
Route::get('showtimes/show/{id}', [ShowtimeController::class, 'show']);

// Api blogs
Route::get('blogs', [BlogController::class, 'index']);
Route::get('blogs/show/{id}', [BlogController::class, 'show']);

//Api roles
Route::get('roles', [RoleController::class, 'index']);
Route::get('roles/show/{id}', [RoleController::class, 'show']);

//thanh toán
Route::post('tickets/payment/vnpay', [PaymentMethodController::class, 'createPayment']);
Route::get('tickets/payment/vnpay/callback', [PaymentMethodController::class, 'vnpayCallback']);

//Api reviews
Route::post('reviews', [ReviewController::class, 'getReviewByMovieId']);

// Các route liên quan đến vé (cần đăng nhập)
Route::middleware(['auth:api'])->group(function () {
    Route::get('tickets', [TicketController::class, 'index']);     // Lấy danh sách vé của người dùng
    Route::post('tickets/create', [TicketController::class, 'store']);    // Đặt vé mới
    Route::get('tickets/detail/{id}', [TicketController::class, 'show'])->name('ticket.detail'); // Lấy chi tiết vé theo ID
});
