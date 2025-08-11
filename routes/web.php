<?php

use Illuminate\Support\Facades\Route;
use Ssbhattarai\MagicLink\Http\Controllers\MagicLinkController;

Route::post('/request', [MagicLinkController::class, 'requestLink'])
    ->name('magiclink.request');

Route::get('/login/{token}', [MagicLinkController::class, 'login'])
    ->name('magiclink.login');

Route::get('/', [MagicLinkController::class, 'requestView'])
    ->name('magiclink.view');
