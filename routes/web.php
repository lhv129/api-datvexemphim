<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/run-cancel-expired-tickets', function (Request $request) {
    if ($request->query('token') !== env('AUTO_TASK_SECRET_TOKEN')) {
        abort(403, 'Không có quyền!');
    }

    Artisan::call('tickets:cancel-expired');

    Log::info('Đã chạy command tickets:cancel-expired từ route lúc ' . now());

    return 'Command đã chạy thành công!';
});
