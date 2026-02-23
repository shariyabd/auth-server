<?php


use App\Http\Controllers\Auth\SsoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/


Route::middleware('guest')->group(function () {
    Route::get('/login', [SsoController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [SsoController::class, 'login']);
    Route::get('/register', [SsoController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [SsoController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [SsoController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [SsoController::class, 'logout'])->name('logout');
    Route::get('/logout', [SsoController::class, 'logout'])->name('logout.get');
});

Route::middleware('web')->group(function () {
    Route::get('/sso/session-check', [SsoController::class, 'sessionCheck'])->name('sso.session-check');
});

Route::get('/', function () {
    return redirect('/login');
});