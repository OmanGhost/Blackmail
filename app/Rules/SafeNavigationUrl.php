<?php
namespace App\Rules;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
class SafeNavigationUrl implements ValidationRule
{
    public function validate(string $attribute,mixed $value,Closure $fail): void
    {
        if (!\App\Support\SafeNavigation::allows($value)) $fail(app()->getLocale()==='en'?'Use a local path beginning with / or an HTTP(S) URL.':'استخدم مسارًا محليًا يبدأ بـ / أو رابط HTTP(S) صالحًا.');
    }
}
