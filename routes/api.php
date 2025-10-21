<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Backend\CartController;
use App\Http\Controllers\Backend\BrandController;
use App\Http\Controllers\Backend\OrderController;
use App\Http\Controllers\Backend\PaymentController;
use App\Http\Controllers\Backend\ProductController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\CheckoutController;
use App\Http\Controllers\Backend\ShipmentController;
use App\Http\Controllers\Backend\WishlistController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');






// authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});




Route::apiResource('categories', CategoryController::class);
Route::apiResource('brands', BrandController::class);
// Route::apiResource('products', ProductController::class);


Route::apiResource('products', ProductController::class);
Route::post('/products/{product}/images', [ProductController::class, 'uploadImage']);



// Cart routes (Guest or Auth)
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart/add', [CartController::class, 'add']);
Route::put('/cart/item/{item}', [CartController::class, 'updateItem']);

// Wishlist (only auth users)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/add', [WishlistController::class, 'add']);
    Route::delete('/wishlist/item/{item}', [WishlistController::class, 'remove']);
});

// Checkout (guest or logged-in)
Route::post('/checkout', [CheckoutController::class, 'checkout']);








// 🧾 Orders (Auth user or admin)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('/orders/{order}/refund', [OrderController::class, 'requestRefund']);
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/orders/{order}/payment/initiate', [PaymentController::class, 'initiate']);
    Route::post('/payments/{payment}/success', [PaymentController::class, 'success']);
    Route::post('/payments/{payment}/fail', [PaymentController::class, 'fail']);
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund']);
    Route::get('/payments/history', [PaymentController::class, 'history']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Shipment management
    Route::get('/shipments', [ShipmentController::class, 'index']);
    Route::post('/orders/{order}/shipments', [ShipmentController::class, 'store']);
    Route::put('/shipments/{shipment}/status', [ShipmentController::class, 'updateStatus']);

    // Tracking (public)
    Route::get('/track/{tracking_no}', [ShipmentController::class, 'track']);

    // Delivery zones
    Route::get('/delivery-zones', [ShipmentController::class, 'zones']);
    Route::post('/delivery-zones', [ShipmentController::class, 'addZone']);
});