<?php

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

// Api Products
Route::get('products',[ProductController::class,'index']);
Route::post('products/create',[ProductController::class,'store']);
Route::put('products/update/{id}',[ProductController::class,'update']);
Route::get('products/show/{id}',[ProductController::class,'show']);
Route::delete('products/delete/{id}',[ProductController::class,'destroy']);

// Api Movies
Route::get('movies',[MovieController::class,'index']);
Route::post('movies/create',[MovieController::class,'store']);
Route::put('movies/update/{id}',[MovieController::class,'update']);
Route::get('movies/show/{id}',[MovieController::class,'show']);
Route::delete('movies/delete/{id}',[MovieController::class,'destroy']);

// Api Genres
Route::get('genres',[GenreController::class,'index']);
Route::post('genres/create',[GenreController::class,'store']);
Route::put('genres/update/{id}',[GenreController::class,'update']);
Route::get('genres/show/{id}',[GenreController::class,'show']);
Route::delete('genres/delete/{id}',[GenreController::class,'destroy']);

// Api Banners
Route::get('banners',[BannerController::class,'index']);
Route::post('banners/create',[BannerController::class,'store']);
Route::put('banners/update/{id}',[BannerController::class,'update']);
Route::get('banners/show/{id}',[BannerController::class,'show']);
Route::delete('banners/delete/{id}',[BannerController::class,'destroy']);