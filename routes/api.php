<?php

/*
|--------------------------------------------------------------------------
| API Routes Loader
|--------------------------------------------------------------------------
|
| This file loads versioned API routes. All endpoints are organized
| under version prefixes for backward compatibility.
|
| Current version: v1 (/api/v1/*)
|
*/

// The v1 routes are loaded via bootstrap/app.php withRouting()
// This file serves as a fallback for any non-versioned routes.

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'KerjaIn API',
        'version' => 'v1',
        'docs' => '/api/v1',
    ]);
});
