<?php
return [
    // Override per installation; this supplements authentication, not a replacement for it.
    'admin_path' => env('ADMIN_PATH', 'control-room'),
    'updates' => [
        'repository' => 'OmanGhost/Blackmail',
        'token' => env('GITHUB_UPDATE_TOKEN'),
        'interval_hours' => 12,
    ],
];
