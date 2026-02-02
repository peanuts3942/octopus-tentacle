<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Theme Settings (loaded dynamically from DB)
    |--------------------------------------------------------------------------
    |
    | These are default values that will be overridden by TentacleSettings
    | from the database when available. The TentacleSettingsServiceProvider
    | loads the actual values at boot time.
    |
    */
    'theme' => [
        'site_name' => env('APP_NAME', 'Tentacle'),
        'logo_url' => '',
        'favicon_url' => '',
        'primary_color' => '#E85D04',
        'contact_email' => '',
        'discord_url' => '',
        'telegram_url' => '',
        'linktree_url' => '',
        'font_url' => 'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap',
        'font_family' => 'Montserrat',
    ],
];
