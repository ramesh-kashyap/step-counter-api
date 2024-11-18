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
Route::post('/sendCodephone', [App\Http\Controllers\Register::class, 'sendCodephone']);


Route::any('/forgot_submit', [App\Http\Controllers\Login::class, 'forgot_password_submit']);

Route::post('/step-bonus', [App\Http\Controllers\UserPanel\stepCount::class, 'checkstep_bonus']);

Route::middleware(['auth:sanctum', 'check.app.key'])->group(function () {
    // All routes within this group will use the auth:sanctum and check.app.key middleware
Route::get('/confirmPay', [App\Http\Controllers\UserPanel\Invest::class, 'confirm_pay']);
Route::post('/upload-image', [App\Http\Controllers\Register::class, 'uploadImage']);

Route::get('/user-inforamtion', [App\Http\Controllers\UserPanel\Profile::class, 'user_info']);
Route::get('/stepHistory', [App\Http\Controllers\UserPanel\stepCount::class, 'step_history']);
Route::get('/incomeReport', [App\Http\Controllers\UserPanel\Profile::class, 'income_report']);
Route::post('/checkPaymentStatus', [App\Http\Controllers\UserPanel\Dashboard::class, 'checkPaymentStatus']);
Route::any('/dynamicupicallback', [App\Http\Controllers\Cron::class, 'dynamicupicallback']);
Route::get( '/levelTeam', [App\Http\Controllers\UserPanel\Team::class, 'LevelTeam']);
Route::post('/edit-number', [App\Http\Controllers\UserPanel\Profile::class, 'change_number']);
Route::get('/transactionHistory', [App\Http\Controllers\UserPanel\AddFund::class, 'index']);
Route::any('/confirmDeposit', [App\Http\Controllers\UserPanel\Invest::class, 'confirmDeposit']);
Route::post('/WithdrawRequest', [App\Http\Controllers\UserPanel\WithdrawRequest::class, 'WithdrawRequest']);
Route::post('/edit-password', [App\Http\Controllers\UserPanel\Profile::class, 'change_password_post']);
Route::post('/Step', [App\Http\Controllers\UserPanel\stepCount::class, 'step_count']);
Route::get('/userInfo', [App\Http\Controllers\UserPanel\Profile::class, 'user_info']);
Route::get('/stepHistory', [App\Http\Controllers\UserPanel\stepCount::class, 'step_history']);
Route::get('/incomeReport', [App\Http\Controllers\UserPanel\Profile::class, 'income_report']);
Route::post('/checkPaymentStatus', [App\Http\Controllers\UserPanel\Dashboard::class, 'checkPaymentStatus']);
Route::any('/dynamicupicallback', [App\Http\Controllers\Cron::class, 'dynamicupicallback']);
Route::get( '/levelTeam', [App\Http\Controllers\UserPanel\Team::class, 'LevelTeam']);
Route::post('/edit-number', [App\Http\Controllers\UserPanel\Profile::class, 'change_number']);
Route::post('/updateProfile', [App\Http\Controllers\Register::class, 'update_profile']);
Route::get('/confirmPay', [App\Http\Controllers\UserPanel\Invest::class, 'confirm_pay']);

});
