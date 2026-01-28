<?php

use App\Http\Controllers\LingerieProductColorController;
use App\Http\Controllers\LingerieProductController;
use App\Http\Controllers\LingerieProductColorImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/lingerie/products', [LingerieProductController::class, 'create']);

Route::put('/lingerie/products/{id}', [LingerieProductController::class, 'update']);

Route::delete('/lingerie/products/{id}', [LingerieProductController::class, 'delete']);

Route::get('/lingerie/products', [LingerieProductController::class, 'get']);

Route::get('/lingerie/products/{id}', [LingerieProductController::class, 'getSkus']);

Route::get('/lingerie/colors',[LingerieProductColorController::class, 'get']);

Route::get('/lingerie/colors/{id}',[LingerieProductColorController::class, 'getById']);

Route::post('/lingerie/colors',[LingerieProductColorController::class, 'create']);

Route::put('/lingerie/colors/{id}',[LingerieProductColorController::class, 'update']);

Route::get('/lingerie/products/{productId}/color-images',[LingerieProductColorImageController::class, 'get']);

Route::post('/lingerie/products/{productId}/color-images',[LingerieProductColorImageController::class, 'create']);

Route::put('/lingerie/products/{productId}/color-images/{colorImageId}',[LingerieProductColorImageController::class, 'update']);


