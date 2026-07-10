<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LogBookController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::get('test', function () {
    Benchmark::dd(function () {
        (string) view('welcome');
    });
});

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'doLogin'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin Users Routes
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::middleware('access_type:1')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/add', [UserController::class, 'add'])->name('add');
        Route::post('/create', [UserController::class, 'doCreate'])->name('create');
        Route::get('/detail/{id}', [UserController::class, 'detail'])->name('detail');
        Route::get('/update/{id}', [UserController::class, 'update'])->name('update');
        Route::post('/update/{id}', [UserController::class, 'doUpdate'])->name('doUpdate');
        Route::delete('/delete/{id}', [UserController::class, 'delete'])->name('delete');
        Route::post('/reset-password/{id}', [UserController::class, 'resetPassword'])->name('resetPassword');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/change-password', [UserController::class, 'changePassword'])->name('change_password');
        Route::post('/change-password', [UserController::class, 'doChangePassword'])->name('do_change_password');
    });

    Route::middleware('access_type:1,2')->prefix('log-book')->name('log_book.')->group(function () {
        Route::get('/', [LogBookController::class, 'index'])->name('index');
        Route::get('/add', [LogBookController::class, 'add'])->name('add');
        Route::get('/export-excel', [LogBookController::class, 'exportExcel'])->name('export_excel');
        Route::get('/export-pdf', [LogBookController::class, 'exportPdf'])->name('export_pdf');
        Route::post('/create', [LogBookController::class, 'doCreate'])->name('create');
        Route::get('/detail/{id}', [LogBookController::class, 'detail'])->name('detail');
        Route::get('/update/{id}', [LogBookController::class, 'update'])->name('update');
        Route::post('/update/{id}', [LogBookController::class, 'doUpdate'])->name('doUpdate');
        Route::delete('/delete/{id}', [LogBookController::class, 'delete'])->name('delete');
        Route::delete('/image/{imageId}', [LogBookController::class, 'deleteImage'])->name('delete_image');
    });

});
