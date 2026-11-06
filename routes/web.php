<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SupportToolsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [\App\Http\Controllers\SeoController::class, 'robots'])->name('robots');
Route::post('/consultation/evidence/{evidenceRequest}', [\App\Http\Controllers\SecureEvidenceController::class,'submit'])->middleware('throttle:10,10')->name('consultation.evidence.submit');
Route::post('/consultation/evidence/submissions/{submission}/withdraw', [\App\Http\Controllers\SecureEvidenceController::class,'withdraw'])->middleware('throttle:10,10')->name('consultation.evidence.withdraw');
Route::view('/neutral', 'support.neutral')->name('neutral');
Route::view('/offline', 'offline')->name('offline');
Route::post('/quick-exit', [SupportToolsController::class,'quickExit'])->name('quick-exit');
Route::get('/privacy-center', [SupportToolsController::class,'privacy'])->name('privacy.center');
Route::post('/privacy-center', [SupportToolsController::class,'requestDeletion'])->middleware('throttle:5,10')->name('privacy.delete');
Route::post('/consultation/tasks/{task}', [SupportToolsController::class,'task'])->middleware('throttle:30,1')->name('consultation.task');
Route::post('/consultation/verify-contact', [SupportToolsController::class,'sendContactVerification'])->middleware('throttle:3,10')->name('consultation.verification.send');
Route::get('/consultation/verify-contact/{consultationRequest}/{hash}', [SupportToolsController::class,'verifyContact'])->middleware('signed')->name('consultation.verify-contact');
Route::get('/consultation/recover', [SupportToolsController::class,'recoveryForm'])->name('consultation.recovery');
Route::post('/consultation/recover', [SupportToolsController::class,'recover'])->middleware('throttle:3,10')->name('consultation.recovery.send');
Route::get('/consultation/recover/open/{token}', [SupportToolsController::class,'openRecovery'])->middleware('throttle:10,10')->name('consultation.recovery.open');
Route::get('/account/verify-email/{user}/{hash}', [SupportToolsController::class,'verifyMember'])->middleware('signed')->name('account.verify-email');
Route::get('/help', function () {
    return view('help', ['countries' => \App\Models\Country::with(['reportingAuthorities' => fn ($query) => $query
        ->where('is_published', true)->where('verification_status', 'verified')])->orderBy('name_ar')->get()]);
})->name('help');
Route::get('/blog', [ContentController::class, 'encyclopedia'])->name('blog');
Route::get('/events', [\App\Http\Controllers\EventController::class,'index'])->name('events');
Route::get('/events/{event:slug}', [\App\Http\Controllers\EventController::class,'show'])->name('events.show');
Route::get('/encyclopedia', fn () => redirect()->route('blog', request()->query(), 301))->name('encyclopedia');
Route::get('/categories/{category:slug}', [ContentController::class, 'category'])->name('categories.show');
Route::get('/blog/{article:slug}', [ContentController::class, 'article'])->name('articles.show');
Route::get('/encyclopedia/{article:slug}', fn (\App\Models\Article $article) => redirect()->route('articles.show', $article, 301));
Route::get('/countries', [ContentController::class, 'countries'])->name('countries');
Route::get('/directory-request', [\App\Http\Controllers\DirectoryRequestController::class,'create'])->name('directory.requests.create');
Route::post('/directory-request', [\App\Http\Controllers\DirectoryRequestController::class,'store'])->middleware('throttle:3,10')->name('directory.requests.store');
Route::get('/countries/{country:slug}', [ContentController::class, 'country'])->name('countries.show');
Route::view('/protection', 'content.protection')->name('protection');
Route::get('/services', [\App\Http\Controllers\ServiceController::class, 'index'])->name('services');
Route::get('/services/{service:slug}', [\App\Http\Controllers\ServiceController::class, 'show'])->name('services.show');
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

Route::post('/visitor-country', function (Request $request) {
    $validated = $request->validate(['country' => ['required', 'string', function ($attribute, $value, $fail) {
        if ($value !== 'auto' && ! \App\Models\Country::where('code', $value)->exists()) {
            $fail(__('validation.in', ['attribute' => $attribute]));
        }
    }]]);
    $validated['country'] === 'auto'
        ? $request->session()->forget(\App\Services\VisitorHotline::SESSION_CHOICE)
        : $request->session()->put(\App\Services\VisitorHotline::SESSION_CHOICE, $validated['country']);

    return back();
})->middleware('throttle:10,1')->name('visitor-country');

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
    Route::post('/account/verify-email', [SupportToolsController::class,'sendMemberVerification'])->middleware('throttle:3,10')->name('account.verification.send');
    Route::get('/account/requests/{consultationRequest}', [ConsultationController::class, 'memberRequest'])->name('account.requests.show');
    Route::get('/account', [AuthController::class, 'account'])->name('account');
    Route::post('/services/{service:slug}/request', [\App\Http\Controllers\ServiceController::class, 'request'])->middleware('throttle:5,10')->name('services.request');
    Route::get('/account/invoices/{invoice}', [\App\Http\Controllers\ServiceController::class, 'invoice'])->name('account.invoices.show');
    Route::put('/account', [AuthController::class, 'updateProfile'])->name('account.update');
    Route::put('/account/password', [AuthController::class, 'updatePassword'])->name('account.password');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
