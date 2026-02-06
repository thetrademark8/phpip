<?php

use App\Http\Controllers\Api\TeamLeaderWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// TeamLeader webhook (no authentication required)
Route::post('teamleader/webhook', [TeamLeaderWebhookController::class, 'handle'])
    ->name('teamleader.webhook');
