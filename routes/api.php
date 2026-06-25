<?php

use App\Http\Controllers\PhoneEventsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', static function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('files/upload', [PhoneEventsController::class, 'readFile'])->name('files.upload');
