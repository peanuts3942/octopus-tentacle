<?php

namespace App\Providers;

use App\Models\TentacleSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class TentacleSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $tentacleId = config('app.tentacle_id');

        if (! $tentacleId) {
            return;
        }

        $this->loadThemeSettings($tentacleId);
    }

    protected function loadThemeSettings(int $tentacleId): void
    {
        try {
            $themeSetting = TentacleSetting::getCachedSetting($tentacleId, 'theme');
        } catch (\Exception $e) {
            return;
        }

        if (! $themeSetting || empty($themeSetting['options']['items'][0])) {
            return;
        }

        $themeItem = $themeSetting['options']['items'][0];

        $theme = [
            'site_name' => $themeItem['site_name'] ?? config('tentacle.theme.site_name'),
            'logo_url' => $themeItem['logo_url'] ?? config('tentacle.theme.logo_url'),
            'favicon_url' => $themeItem['favicon_url'] ?? config('tentacle.theme.favicon_url'),
            'primary_color' => $themeItem['primary_color'] ?? config('tentacle.theme.primary_color'),
            'contact_email' => $themeItem['contact_email'] ?? config('tentacle.theme.contact_email'),
            'discord_url' => $themeItem['discord_url'] ?? config('tentacle.theme.discord_url'),
            'telegram_url' => $themeItem['telegram_url'] ?? config('tentacle.theme.telegram_url'),
            'linktree_url' => $themeItem['linktree_url'] ?? config('tentacle.theme.linktree_url'),
            'font_url' => $themeItem['font_url'] ?? config('tentacle.theme.font_url'),
            'font_family' => $themeItem['font_family'] ?? config('tentacle.theme.font_family'),
        ];

        config(['tentacle.theme' => $theme]);

        View::share('theme', (object) $theme);
    }
}
