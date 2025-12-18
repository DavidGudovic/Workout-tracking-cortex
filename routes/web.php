<?php

use Illuminate\Support\Facades\Route;

Route::get('/docs', function () {
    return view('swagger');
});

Route::get('/openapi.json', function () {
    return response()->file(base_path('openapi.json'));
});
