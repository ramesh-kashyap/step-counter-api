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
Route::any('/confirmDeposit', [App\Http\Controllers\UserPanel\Invest::class, 'confirmDeposit']);
Route::post('/WithdrawRequest', [App\Http\Controllers\UserPanel\WithdrawRequest::class, 'WithdrawRequest']);
Route::get('/transactionHistory', [App\Http\Controllers\UserPanel\AddFund::class, 'index']);
Route::post('/edit-password', [App\Http\Controllers\UserPanel\Profile::class, 'change_password_post']);
Route::any('/forgot_submit', [App\Http\Controllers\Login::class, 'forgot_password_submit']);

Route::middleware(['auth:sanctum', 'check.app.key'])->get('/protected-data', function (Request $request) {
        return $request->user();
    
});

