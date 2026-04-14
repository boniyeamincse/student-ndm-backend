<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Base Routes
|--------------------------------------------------------------------------
| Versioned routes are loaded from routes/api_v1.php via bootstrap/app.php.
| Only system-level endpoints live here.
*/

Route::get('/health', fn () => response()->json([
    'success' => true,
    'message' => 'Student Movement NDM API is running.',
    'version' => 'v1',
]));

// Load V1 routes
require __DIR__.'/api_v1.php';

