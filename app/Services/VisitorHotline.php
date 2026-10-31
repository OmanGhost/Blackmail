<?php

namespace App\Services;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use MaxMind\Db\Reader;
use Throwable;

/**
 * Picks the reporting phone number shown in the site header.
 *
 * Country order: the visitor's own choice, then a trusted proxy header (if configured),
 * then a local IP database lookup. Only the two-letter country code is kept in the session;
 * the visitor's IP is never stored or sent to another service.
 */
class VisitorHotline
{
    public const SESSION_CHOICE = 'visitor_country.choice';
    public const SESSION_DETECTED = 'visitor_country.detected';

    public function resolve(Request $request): array
    {
        $chosen = $request->hasSession() ? $request->session()->get(self::SESSION_CHOICE) : null;
        $code = $chosen ?: $this->detect($request);
        $authority = $code ? $this->authorityFor($code) : null;
        $en = app()->getLocale() === 'en';

        if ($authority) {
            return [
                'type' => 'authority',
                'code' => $code,
                'chosen' => (bool) $chosen,
                'country' => $en ? $authority['country_en'] : $authority['country_ar'],
                'label' => $en ? ($authority['name_en'] ?: $authority['name_ar']) : $authority['name_ar'],
                'phone' => $authority['phone'],
                'tel' => $this->tel($authority['phone']),
                'url' => $authority['url'],
            ];
        }

        // Inside Oman the local number works; elsewhere show the international format.
        $phone = $code === config('platform.hotline.country') ? config('platform.hotline.local') : config('platform.hotline.international');

        return [
            'type' => 'platform',
            'code' => $code,
            'chosen' => (bool) $chosen,
            'country' => null,
            'label' => null,
            'phone' => $phone,
            'tel' => $this->tel(config('platform.hotline.international')),
            'url' => null,
        ];
    }

    public function detect(Request $request): ?string
    {
        $header = config('platform.geo.header');
        if ($header && ($code = $this->normalize($request->header($header)))) {
            return $code;
        }
        if (! $request->hasSession()) {
            return $this->lookup($request->ip());
        }
        $session = $request->session();
        if (! $session->has(self::SESSION_DETECTED)) {
            // Store '' for "unknown" so the database is opened once per session.
            $session->put(self::SESSION_DETECTED, $this->lookup($request->ip()) ?? '');
        }

        return $session->get(self::SESSION_DETECTED) ?: null;
    }

    public function lookup(?string $ip): ?string
    {
        $path = config('platform.geo.database');
        if (! $ip || ! $path || ! is_file($path) || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }
        try {
            $reader = new Reader($path);
            $record = $reader->get($ip);
            $reader->close();
        } catch (Throwable) {
            return null;
        }

        return $this->normalize($record['country']['iso_code'] ?? null);
    }

    public function normalize(mixed $code): ?string
    {
        return is_string($code) && preg_match('/^[A-Za-z]{2}$/', $code) ? strtoupper($code) : null;
    }

    /** First verified, published authority with a phone number in a verified, published country. */
    public function authorityFor(string $code): ?array
    {
        $version = Cache::get('visitor-hotline:version', 0);

        return Cache::remember("visitor-hotline:{$version}:{$code}", 600, function () use ($code) {
            $country = Country::query()->verified()->where('code', $code)->first();
            $authority = $country?->reportingAuthorities()
                ->where('verification_status', 'verified')->where('is_published', true)
                ->whereNotNull('phone')->where('phone', '!=', '')->orderBy('id')->first();

            return $authority ? [
                'country_ar' => $country->name_ar, 'country_en' => $country->name_en,
                'name_ar' => $authority->name_ar, 'name_en' => $authority->name_en,
                'phone' => trim($authority->phone), 'url' => route('countries.show', $country),
            ] : false;
        }) ?: null;
    }

    public static function flush(): void
    {
        Cache::forever('visitor-hotline:version', Cache::get('visitor-hotline:version', 0) + 1);
    }

    private function tel(string $phone): string
    {
        return preg_replace('/(?!^\+)[^\d]/', '', trim($phone));
    }
}
