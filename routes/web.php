<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::view('/help', 'help')->name('help');
Route::get('/encyclopedia', [ContentController::class, 'encyclopedia'])->name('encyclopedia');
Route::get('/encyclopedia/{article:slug}', [ContentController::class, 'article'])->name('articles.show');
Route::get('/countries', [ContentController::class, 'countries'])->name('countries');
Route::get('/countries/{country:slug}', [ContentController::class, 'country'])->name('countries.show');
Route::view('/protection', 'content.protection')->name('protection');
Route::view('/tools', 'content.tools')->name('tools');
Route::get('/consultation', [ConsultationController::class, 'create'])->name('consultation');
Route::get('/stop-blackmail', [ConsultationController::class, 'emergency'])->name('blackmail.help');
Route::post('/consultation', [ConsultationController::class, 'store'])->middleware('throttle:5,10')->name('consultation.store');
Route::get('/consultation/success', [ConsultationController::class, 'success'])->name('consultation.success');
Route::get('/consultation/track', [ConsultationController::class, 'showTracking'])->name('consultation.track');
Route::get('/consultation/open/{consultationRequest}', [ConsultationController::class, 'openLink'])->middleware(['signed', 'throttle:20,1'])->name('consultation.open');
Route::post('/consultation/forget', [ConsultationController::class, 'forget'])->name('consultation.forget');
Route::post('/consultation/track', [ConsultationController::class, 'track'])->middleware('throttle:10,10')->name('consultation.track.lookup');
Route::post('/consultation/reply', [ConsultationController::class, 'reply'])->middleware('throttle:10,10')->name('consultation.reply');
Route::post('/consultation/close', [ConsultationController::class, 'close'])->middleware('throttle:10,10')->name('consultation.close');
Route::post('/consultation/rate', [ConsultationController::class, 'rate'])->middleware('throttle:10,10')->name('consultation.rate');
Route::view('/contact', 'pages.coming-soon', ['title' => 'تواصل معنا'])->name('contact');

Route::post('/locale', function (Request $request) {
    $validated = $request->validate(['locale' => 'required|in:ar,en']);
    $request->session()->put('locale', $validated['locale']);

    return back();
})->middleware('throttle:10,1')->name('locale');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});
Route::middleware('auth')->group(function () {
    Route::get('/account/requests/{consultationRequest}', [ConsultationController::class, 'memberRequest'])->name('account.requests.show');
    Route::get('/account', [AuthController::class, 'account'])->name('account');
    Route::put('/account', [AuthController::class, 'updateProfile'])->name('account.update');
    Route::put('/account/password', [AuthController::class, 'updatePassword'])->name('account.password');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
