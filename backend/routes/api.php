<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\ItemLoteController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\PerdaController;
use App\Http\Controllers\MovimentacaoController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\RelatorioAvancadoController;
use App\Http\Controllers\ImportacaoController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ChatbotController;

// Auth
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', fn(Request $request) => $request->user());

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::apiResource('/lotes', LoteController::class);

    Route::get('/lotes/{idLote}/itens',   [ItemLoteController::class, 'index']);
    Route::post('/lotes/{idLote}/itens',  [ItemLoteController::class, 'store']);
    Route::put('/itens/{id}',             [ItemLoteController::class, 'update']);
    Route::patch('/itens/{id}/baixa',     [ItemLoteController::class, 'baixa']);
    Route::patch('/itens/{id}/entrada',   [ItemLoteController::class, 'entrada']);
    Route::patch('/lotes/{idLote}/itens/reordenar', [ItemLoteController::class, 'reordenar']);
    Route::delete('/itens/{id}',          [ItemLoteController::class, 'destroy']);
    Route::get('/itens/{id}/historico',   [ItemLoteController::class, 'historico']);

    Route::put('/perfil',       [PerfilController::class, 'atualizarPerfil']);
    Route::post('/perfil',      [PerfilController::class, 'atualizarPerfil']);
    Route::put('/perfil/senha', [PerfilController::class, 'atualizarSenha']);

    Route::get('/produtos',         [ProdutoController::class, 'index']);
    Route::delete('/produtos/{id}', [ProdutoController::class, 'destroy']);

    Route::get('/perdas',              [PerdaController::class, 'index']);
    Route::post('/perdas',             [PerdaController::class, 'store']);
    Route::get('/perdas/estatisticas', [PerdaController::class, 'estatisticas']);

    Route::get('/movimentacoes',         [MovimentacaoController::class, 'index']);
    Route::delete('/movimentacoes/{id}', [MovimentacaoController::class, 'destroy']);

    Route::get('/relatorios/estoque',     [RelatorioController::class, 'estoque']);
    Route::get('/relatorios/vencimentos', [RelatorioController::class, 'vencimentos']);
    Route::get('/relatorios/auditoria',   [RelatorioController::class, 'auditoria']);
    Route::get('/relatorios/itens',       [RelatorioController::class, 'itens']);

    Route::get('/relatorios-avancados/perdas', [RelatorioAvancadoController::class, 'perdas']);
    Route::get('/relatorios-avancados/abc',    [RelatorioAvancadoController::class, 'abc']);

    // ─── Importação ────────────────────────────────────────────
    Route::get('importacao-exportacao/stats',        [ImportacaoController::class, 'stats']);
    Route::get('importacao-exportacao/template-csv',  [ImportacaoController::class, 'downloadTemplate']);
    Route::get('importacao-exportacao/template',      [ImportacaoController::class, 'downloadTemplate']);
    Route::post('importacao-exportacao/preview',      [ImportacaoController::class, 'previewImportacao']);
    Route::post('importacao-exportacao/confirmar',    [ImportacaoController::class, 'confirmarImportacao']);

    // ─── Exportação ────────────────────────────────────────────
    Route::get('/importacao-exportacao/exportar/produtos-csv',       [ExportController::class, 'exportarProdutosCSV']);
    Route::get('/importacao-exportacao/exportar/produtos-xlsx',      [ExportController::class, 'exportarProdutosXLSX']);
    Route::get('/importacao-exportacao/exportar/movimentacoes-csv',  [ExportController::class, 'exportarMovimentacoesCSV']);
    Route::get('/importacao-exportacao/exportar/movimentacoes-xlsx', [ExportController::class, 'exportarMovimentacoesXLSX']);

    // ─── Backup ────────────────────────────────────────────────
    Route::get('importacao-exportacao/exportar/backup',  [BackupController::class, 'exportarBackup']);
    Route::post('importacao-exportacao/restaurar/backup', [BackupController::class, 'restaurarBackup']);

    Route::get('/audit-logs',        [AuditLogController::class, 'index']);
    Route::get('/audit-logs/export', [AuditLogController::class, 'export']);

    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::post('/usuarios', [UsuarioController::class, 'store']);
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);
    Route::patch('/usuarios/{id}/status', [UsuarioController::class, 'alternarStatus']);

    Route::post('/chatbot', [ChatbotController::class, 'perguntar']);

});