<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Ux2Dev\Borica\Laravel\Http\Controllers\BoricaCallbackController;

Route::post('/callback', BoricaCallbackController::class)
    ->name('borica.callback');
