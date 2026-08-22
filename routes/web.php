<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Broadcast authentication route for private channels
Route::post('/broadcasting/auth', function () {
    return response()->json(['message' => 'Broadcast auth endpoint. Use API token for channel auth.']);
});
