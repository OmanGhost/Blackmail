<?php
namespace App\Support;
class SafeNavigation
{
    public static function allows(mixed $value): bool
    {
        if (!is_string($value) || $value==='' || trim($value)!==$value) return false;
        $decoded=$value;
        for($i=0;$i<4;$i++) {
            if (preg_match('/[\\\\\x00-\x20\x7f]/',$decoded) || str_starts_with($decoded,'//')) return false;
            $next=rawurldecode($decoded);
            if ($next===$decoded) break;
            $decoded=$next;
        }
        if (str_starts_with($value,'/') || str_starts_with($value,'#')) return true;
        $parts=parse_url($value);
        return $parts!==false && in_array(strtolower($parts['scheme']??''),['http','https'],true)
            && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']);
    }
    public static function url(mixed $value): string
    {
        return static::allows($value) ? (str_starts_with($value,'#')?$value:url($value)) : '#';
    }
}
