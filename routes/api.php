<?php

Route::get('/health-check', function () {
    return response()->json(['status' => 'ok']);
});
