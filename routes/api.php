<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/login', [App\Http\Controllers\Login::class, 'login']);
Route::post('/registers', [App\Http\Controllers\Register::class, 'register']);
Route::middleware(['auth:sanctum', 'check.app.key'])->get('/protected-data', function (Request $request) {
    return $request->user();
});


