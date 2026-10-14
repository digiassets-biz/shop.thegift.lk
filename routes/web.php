<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WebhookLogController;
use App\Http\Controllers\ReturnController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'index'])->name('products.index');
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout/pay', [CheckoutController::class, 'pay'])->name('checkout.pay');
Route::post('/notify', [WebhookController::class, 'notify'])->name('webhook.notify');
Route::get('/webhook-logs', [WebhookLogController::class, 'index'])->name('webhook-logs.index');
Route::get('/return', [ReturnController::class, 'show'])->name('checkout.return');
Route::get('/return/status', [ReturnController::class, 'status'])->name('checkout.return-status');
