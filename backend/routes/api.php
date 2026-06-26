<?php

use App\Http\Controllers\ImportacaoExportacaoController;
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
Route::middleware('auth:sanctum')->patch('/itens/{id}/entrada', [ItemLoteController::class, 'entrada']);
Route::middleware('auth:sanctum')->delete('/itens/{id}', [ItemLoteController::class, 'destroy']);
Route::middleware('auth:sanctum')->get('/itens/{id}/historico', [ItemLoteController::class, 'historico']);
use App\Http\Controllers\PerfilController;
Route::middleware('auth:sanctum')->put('/perfil',       [PerfilController::class, 'atualizar']);
Route::middleware('auth:sanctum')->put('/perfil/senha', [PerfilController::class, 'alterarSenha']);
Route::middleware('auth:sanctum')->post('/perfil', [PerfilController::class, 'atualizar']);
use App\Http\Controllers\ProdutoController;
Route::middleware('auth:sanctum')->get('/produtos', [ProdutoController::class, 'index']);
Route::middleware('auth:sanctum')->delete('/produtos/{id}', [ProdutoController::class, 'destroy']);
use App\Http\Controllers\PerdaController;
Route::middleware('auth:sanctum')->get('/perdas', [PerdaController::class, 'index']);
Route::middleware('auth:sanctum')->post('/perdas', [PerdaController::class, 'store']);
Route::middleware('auth:sanctum')->get('/perdas/estatisticas', [PerdaController::class, 'estatisticas']);
use App\Http\Controllers\MovimentacaoController;
Route::middleware('auth:sanctum')->get('/movimentacoes', [MovimentacaoController::class, 'index']);
Route::middleware('auth:sanctum')->delete('/movimentacoes/{id}', [MovimentacaoController::class, 'destroy']);
use App\Http\Controllers\RelatorioController;
Route::middleware('auth:sanctum')->get('/relatorios/estoque',     [RelatorioController::class, 'estoque']);
Route::middleware('auth:sanctum')->get('/relatorios/vencimentos', [RelatorioController::class, 'vencimentos']);
Route::middleware('auth:sanctum')->get('/relatorios/auditoria',   [RelatorioController::class, 'auditoria']);
Route::middleware('auth:sanctum')->get('/relatorios/itens', [RelatorioController::class, 'itens']);
use App\Http\Controllers\RelatorioAvancadoController;
Route::middleware('auth:sanctum')->get('/relatorios-avancados/perdas',   [RelatorioAvancadoController::class, 'perdas']);
Route::middleware('auth:sanctum')->get('/relatorios-avancados/abc',      [RelatorioAvancadoController::class, 'abc']);
// Importação e Exportação
Route::prefix('importacao-exportacao')->group(function () {
    Route::get('/stats',                     [ImportacaoExportacaoController::class, 'stats']);
    Route::get('/exportar/backup',           [ImportacaoExportacaoController::class, 'exportarBackup']);
    Route::get('/exportar/produtos-csv',     [ImportacaoExportacaoController::class, 'exportarProdutosCSV']);
    Route::get('/exportar/movimentacoes-csv',[ImportacaoExportacaoController::class, 'exportarMovimentacoesCSV']);
    Route::get('/template-csv',              [ImportacaoExportacaoController::class, 'downloadTemplate']);
    Route::post('/importar/produtos-csv',    [ImportacaoExportacaoController::class, 'importarProdutosCSV']);
    Route::post('/restaurar/backup',         [ImportacaoExportacaoController::class, 'restaurarBackup']);
});