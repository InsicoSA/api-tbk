<?php

use App\Http\Controllers\WebpayPlusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/webpayplus/create', [WebpayPlusController::class, 'createTransaction']);

Route::any('/webpayplus/returnUrl',  [WebpayPlusController::class, 'commitTransaction'])->name('returnUrl');

Route::get('/webpayplus/comprobante', [WebpayPlusController::class, 'getTransactionComprobante']);

Route::get('/webpayplus/status', [WebpayPlusController::class, 'getTransactionStatus']);