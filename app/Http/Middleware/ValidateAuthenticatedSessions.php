<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ValidateAuthenticatedSessions
{
    public function handle(Request $request, Closure $next)
    {
        Auth::shouldUse(\App\Support\PanelAccess::matches($request)?'admin':'web');
        foreach (['web','admin'] as $name) {
            $guard=Auth::guard($name);
            if (!$guard->check()) continue;
            $current=User::find($guard->id());
            if (!$current?->is_active || (int)$request->session()->get('auth_versions.'.$name,0)!==(int)$current->auth_version) {
                // Invalidate the whole shared browser session, including case bearer grants.
                Auth::guard('web')->logout();
                Auth::guard('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect(\App\Support\PanelAccess::matches($request) ? \App\Support\PanelAccess::loginUrl() : route('login'));
            }
        }
        return $next($request);
    }
}
