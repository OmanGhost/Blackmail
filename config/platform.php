<?php
return [
    // Override per installation; this supplements authentication, not a replacement for it.
    'admin_path' => env('ADMIN_PATH', 'control-room'),
    // Shown in the header when the visitor's country is unknown or not verified yet.
    'hotline' => [
        'local' => env('PLATFORM_HOTLINE_LOCAL', '78444499'),
        'international' => env('PLATFORM_HOTLINE_INTERNATIONAL', '+968-78444499'),
        'country' => 'OM',
    ],
    // Visitor country detection. The IP lookup runs locally; visitor IPs are not sent anywhere.
    'geo' => [
        // Set only when a trusted proxy adds the header (e.g. CF-IPCountry behind Cloudflare).
        'header' => env('GEO_COUNTRY_HEADER'),
        'database' => env('GEO_DATABASE_PATH', storage_path('app/geoip/dbip-country-lite.mmdb')),
    ],
    'updates' => [
        'repository' => 'OmanGhost/Blackmail',
        'token' => env('GITHUB_UPDATE_TOKEN'),
        'interval_hours' => 12,
        // Separate web root (e.g. cPanel public_html) that should also receive public files. Detected automatically when empty.
        'public_mirror' => env('UPDATE_PUBLIC_PATH'),
    ],
];
