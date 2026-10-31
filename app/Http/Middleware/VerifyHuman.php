<?php
namespace App\Http\Middleware;

use App\Services\HumanChallenge;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class VerifyHuman
{
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->method(), ['POST','PUT','PATCH','DELETE'], true)
            && !\App\Support\PanelAccess::matches($request) && !$request->is('livewire/*')
            && !$request->routeIs('locale','logout','quick-exit','consultation.forget')) {
            $key = 'human-check:'.hash('sha256', $request->session()->getId());
            if (RateLimiter::tooManyAttempts($key, 10)) {
                throw ValidationException::withMessages(['human_answer'=>app()->getLocale()==='en'?'Too many attempts. Wait a minute and refresh the form.':'محاولات كثيرة. انتظر دقيقة وأعد فتح النموذج.']);
            }
            if (!app(HumanChallenge::class)->verify($request)) {
                RateLimiter::hit($key, 60);
                throw ValidationException::withMessages(['human_answer'=>app()->getLocale()==='en'?'Human verification failed or expired. Answer the new question and try again.':'إجابة التحقق غير صحيحة أو منتهية. أجب عن السؤال الجديد وحاول مجددًا.']);
            }
        }
        return $next($request);
    }
}
