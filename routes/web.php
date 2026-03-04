<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return redirect('/store');
});

Route::get('/store', [StoreController::class, 'index'])->name('store');
Route::get('/cart', [StoreController::class, 'cart'])->name('cart');

Route::post('/cart/add', [StoreController::class, 'add'])->name('cart.add');
Route::post('/cart/remove', [StoreController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [StoreController::class, 'clear'])->name('cart.clear');

Route::post('/checkout', [PaymentController::class, 'checkout'])->name('checkout');

Route::get('/qr/{md5}', [PaymentController::class, 'qrPage'])->name('qr.page');

Route::get('/store/success/{md5}', [PaymentController::class, 'storeSuccess'])->name('store.success');
Route::get('/paid/{md5}', [PaymentController::class, 'paidPage'])->name('paid.page');
