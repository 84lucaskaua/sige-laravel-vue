<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

use App\Http\Controllers\DashboardController;
Route::middleware('auth:sanctum')->get('/dashboard', [DashboardController::class, 'index']);

use App\Http\Controllers\LoteController;
Route::middleware('auth:sanctum')->apiResource('/lotes', LoteController::class);

use App\Http\Controllers\ItemLoteController;
Route::middleware('auth:sanctum')->get('/lotes/{idLote}/itens', [ItemLoteController::class, 'index']);
Route::middleware('auth:sanctum')->post('/lotes/{idLote}/itens', [ItemLoteController::class, 'store']);
Route::middleware('auth:sanctum')->put('/itens/{id}', [ItemLoteController::class, 'update']);
Route::middleware('auth:sanctum')->patch('/itens/{id}/baixa', [ItemLoteController::class, 'baixa']);