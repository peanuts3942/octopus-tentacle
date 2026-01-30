<?php

use App\Http\Controllers\ChannelController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
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
    Route::get('/videos/{id}-{slug}', [VideoController::class, 'show'])->name('video.show')->where('id', '[0-9]+');

    // Categories (Tags)
    Route::get('/categories', [TagController::class, 'index'])->name('category.index');
    Route::get('/categories/{slug}', [TagController::class, 'show'])->name('category.show');

    // Models (Channels)
    Route::get('/models', [ChannelController::class, 'index'])->name('model.index');
    Route::get('/models/{slug}', [ChannelController::class, 'show'])->name('model.show');

    // Search
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // Legal
    Route::get('/dmca', [LegalController::class, 'dmca'])->name('legal.dmca');
    Route::get('/remove-content', [LegalController::class, 'removeContent'])->name('legal.remove');

});
