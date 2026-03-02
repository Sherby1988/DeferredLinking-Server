<?php

use App\Http\Controllers\Web\RedirectController;
use Illuminate\Support\Facades\Route;

// Short link redirect — catch-all, must be last
Route::get('/{shortCode}', [RedirectController::class, 'handle'])
    ->middleware('resolve.app')
    ->where('shortCode', '[a-zA-Z0-9]{1,16}');
