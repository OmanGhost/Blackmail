<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class RedirectLegacyAdmin
{
    public function handle(Request $request,Closure $next)
    {
        if (preg_match('~^admin(?:/|$)~i',$request->path())) {
            return redirect(url('/'))->withHeaders(['Cache-Control'=>'no-store, private']);
        }
        return $next($request);
    }
}
