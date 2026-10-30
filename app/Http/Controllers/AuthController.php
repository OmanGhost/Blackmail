<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة.'])->onlyInput('email');
        }
        if (! $request->user()->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'هذا الحساب موقوف.']);
        }
        $request->session()->regenerate();


        return redirect()->intended(route('account'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
            'terms' => ['accepted'],
        ]);
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'member'], ['label_ar' => 'عضو']);
        $user->roles()->attach($role);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account')->with('success', 'تم إنشاء حسابك بنجاح.');
    }

    public function account(Request $request): View
    {
        $filters = $request->validate([
            'tab' => ['nullable', 'in:overview,requests,profile,security'],
            'status' => ['nullable', 'in:active,ended,new,in_review,in_progress,contacted,completed,closed'],
            'q' => ['nullable', 'string', 'max:30'],
            'sort' => ['nullable', 'in:newest,oldest,updated'],
        ]);
        $user = $request->user();
        $base = $user->consultationRequests();
        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->whereIn('status', ['new', 'in_review', 'in_progress'])->count(),
            'replied' => (clone $base)->where('status', 'contacted')->count(),
            'closed' => (clone $base)->whereIn('status', ['closed', 'completed'])->count(),
        ];
        $query = $user->consultationRequests()->withCount('messages');
        if (($filters['status'] ?? null) === 'active') {
            $query->whereIn('status', ['new', 'in_review', 'in_progress']);
        } elseif (($filters['status'] ?? null) === 'ended') {
            $query->whereIn('status', ['completed', 'closed']);
        } elseif (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (filled($filters['q'] ?? null)) {
            $number = strtr(trim($filters['q']), array_combine(preg_split('//u', '٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY), str_split('0123456789')));
            $query->where('id', ctype_digit($number) ? (int) $number - 100000 : -1);
        }
        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest('id'),
            'updated' => $query->latest('updated_at'),
            default => $query->latest('id'),
        };
        return view('auth.account', [
            'user' => $user, 'requests' => $query->paginate(8)->withQueryString(),
            'stats' => $stats, 'tab' => $filters['tab'] ?? 'overview',
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$request->user()->id]]);
        $request->user()->update($data);

        return redirect()->route('account', ['tab' => 'profile'])->with('success', 'تم تحديث الملف الشخصي.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()]]);
        $request->user()->update(['password' => $data['password']]);

        return redirect()->route('account', ['tab' => 'security'])->with('success', 'تم تغيير كلمة المرور.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
