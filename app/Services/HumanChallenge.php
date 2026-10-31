<?php
namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HumanChallenge
{
    /** Signed-in active members already passed a check at login, so they are not asked again. */
    public function required(Request $request): bool
    {
        return ! $request->user('web')?->is_active;
    }

    public function issue(Request $request, string $action): array
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $token = Str::random(40);
        $challenges = array_filter($request->session()->get('human_challenges', []), fn ($c) => $c['expires'] > time());
        $challenges = array_slice($challenges, -39, null, true);
        $challenges[$token] = ['answer'=>hash('sha256', (string)($a+$b)), 'action'=>$action, 'expires'=>time()+1800];
        $request->session()->put('human_challenges', $challenges);
        return ['token'=>$token, 'question'=>"$a + $b"];
    }

    public function verify(Request $request): bool
    {
        $token = $request->input('human_token');
        $answer = $request->input('human_answer');
        if (!is_string($token) || !is_string($answer)) return false;
        $challenges = $request->session()->get('human_challenges', []);
        $challenge = $challenges[$token] ?? null;
        unset($challenges[$token]);
        $request->session()->put('human_challenges', $challenges);
        $answer = strtr(trim($answer), array_combine(preg_split('//u', '٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹', -1, PREG_SPLIT_NO_EMPTY), str_split('01234567890123456789')));
        return $challenge && $challenge['expires'] > time()
            && $challenge['action'] === $request->path()
            && preg_match('/^\d{1,2}$/', $answer)
            && hash_equals($challenge['answer'], hash('sha256', $answer))
            && !$request->filled('human_website');
    }
}
