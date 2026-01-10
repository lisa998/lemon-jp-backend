<?php

use App\Http\Controllers\LingerieProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/lingerie/products', [LingerieProductController::class, 'create']);

Route::put('/lingerie/products/{id}', [LingerieProductController::class, 'update']);

Route::delete('/lingerie/products/{id}', [LingerieProductController::class, 'delete']);

Route::get('/lingerie/products', [LingerieProductController::class, 'get']);
