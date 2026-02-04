<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| All routes are wrapped with the 'tentacle' middleware which resolves
| the current tentacle from TENTACLE_ID env or domain mapping.
|
*/

Route::middleware('tentacle')->group(function () {

    // Home
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/page/{page}', [HomeController::class, 'index'])->name('home.page')->where('page', '[0-9]+');

    // Videos
    Route::get('/'.route_trans('videos').'/{id}-{slug}', [VideoController::class, 'show'])->name('video.show')->where('id', '[0-9]+');
    Route::get('/'.route_trans('videos').'/{id}/related', [VideoController::class, 'relatedVideos'])->name('video.related')->where('id', '[0-9]+');

    // Player (iframe)
    Route::get('/'.route_trans('videos').'/{id}/player', [PlayerController::class, 'index'])->name('video.player')->where('id', '[0-9]+');

    // Categories (Tags)
    Route::get('/'.route_trans('categories'), [TagController::class, 'index'])->name('category.index');
    Route::get('/'.route_trans('categories').'/{slug}', [TagController::class, 'show'])->name('category.show');

    // Models (Channels)
    Route::get('/'.route_trans('models'), [ChannelController::class, 'index'])->name('model.index');
    Route::get('/'.route_trans('models').'/{slug}', [ChannelController::class, 'show'])->name('model.show');

    // Search
    Route::get('/'.route_trans('search'), [SearchController::class, 'index'])->name('search');

    // Ads
    Route::get('/ad/vast', [AdController::class, 'getVASTxml'])->name('ad.vast');

    // Legal
    Route::get('/'.route_trans('dmca'), [LegalController::class, 'dmca'])->name('legal.dmca');
    Route::get('/'.route_trans('remove-content'), [LegalController::class, 'removeContent'])->name('legal.remove');

});
