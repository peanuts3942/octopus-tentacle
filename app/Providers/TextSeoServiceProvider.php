<?php

namespace App\Providers;

use App\Helpers\TranslationHelper;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class TextSeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $currentLocale = config('app.locale', 'en');
            $tentacleId = config('tentacle.id');

            $textseo = TranslationHelper::getSeoTexts($currentLocale, $tentacleId);
            $view->with('textseo', $textseo);
        });
    }
}
