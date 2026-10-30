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
        return view('auth.account', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$request->user()->id]]);
        $request->user()->update($data);

        return back()->with('success', 'تم تحديث الملف الشخصي.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()]]);
        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'تم تغيير كلمة المرور.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
