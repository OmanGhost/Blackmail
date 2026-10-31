<?php
namespace App\Support;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
class SessionCredentials
{
    public static function refreshCurrent(User $user): void
    {
        foreach (['web','admin'] as $name) {
            $guard=Auth::guard($name);
            if ($guard->id()!==$user->id) continue;
            $guard->setUser($user);
            session()->put('auth_versions.'.$name,(int)$user->auth_version);
            session()->put('password_hash_'.$name,$user->getAuthPassword());
        }
        session()->regenerate();
    }
}
