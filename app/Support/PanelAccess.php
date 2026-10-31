<?php
namespace App\Support;
use Illuminate\Http\Request;
class PanelAccess
{
    public static function path(): string
    {
        $path=config('platform.admin_path');
        if (!is_string($path) || !preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{7,63}$/D',$path)
            || in_array(strtolower($path),['admin','consultation','stop-blackmail','privacy-center','directory-request','forgot-password','reset-password'],true)) {
            throw new \InvalidArgumentException('ADMIN_PATH must be a unique 8-64 character URL segment using letters, digits, hyphens or underscores.');
        }
        return $path;
    }
    public static function matches(Request $request): bool
    {
        $path=static::path();
        return $request->is($path,$path.'/*');
    }
    public static function loginUrl(): string { return url(static::path().'/login'); }
}
