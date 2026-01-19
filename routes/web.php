<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

// Redirect Link Reset Password dari Email ke Halaman Filament
Route::get('/reset-password/{token}', function ($token, Request $request) {
    return redirect()->route('filament.admin.auth.password-reset.reset', [
        'token' => $token,
        'email' => $request->email,
    ]);
})->name('password.reset');