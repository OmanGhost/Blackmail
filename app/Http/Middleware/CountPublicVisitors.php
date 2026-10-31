<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CountPublicVisitors
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $publicRoutes = ['home','help','blog','encyclopedia','categories.show','articles.show','countries','countries.show','protection','tools','consultation','blackmail.help','contact'];
        if ($request->isMethod('GET') && $response->isSuccessful()
            && in_array($request->route()?->getName(), $publicRoutes, true)
            && !preg_match('/bot|crawler|spider|slurp|headless/i', $request->userAgent() ?? '')
            && !$request->session()->get('public_visit_counted')) {
            DB::table('platform_visit_totals')->where('id', 1)->increment('visitor_sessions');
            $request->session()->put('public_visit_counted', true);
        }
        return $response;
    }
}
