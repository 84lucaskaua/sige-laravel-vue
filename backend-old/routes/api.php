<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AutenticacaoController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\MovimentoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RelatorioController;

/*
 * ============================================================
 * Rotas da API do SIGE
 * ============================================================
 *
 * Rotas públicas: acessíveis sem login
 * Rotas protegidas: precisam do token no header Authorization
 *
 * Perfis de acesso:
 *   admin       → acesso total
 *   operador    → pode criar e movimentar, mas não gerenciar usuários
 *   visualizador → só leitura
 */

// ---- Rotas públicas (sem autenticação) ----
Route::post('/login', [AutenticacaoController::class, 'login']);

// ---- Rotas protegidas (requer token do Sanctum) ----
Route::middleware('auth:sanctum')->group(function () {

    // Autenticação
    Route::post('/logout', [AutenticacaoController::class, 'logout']);
    Route::get('/me', [AutenticacaoController::class, 'meusDados']);

    // Dashboard (todos os perfis podem ver)
    Route::get('/dashboard', [DashboardController::class, 'resumo']);

    // Categorias (leitura para todos, criação/edição só para admin e operador)
    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::post('/categorias', [CategoriaController::class, 'store'])
        ->middleware('perfil:admin,operador');
    Route::put('/categorias/{id}', [CategoriaController::class, 'update'])
        ->middleware('perfil:admin,operador');
    Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy'])
        ->middleware('perfil:admin');

    // Produtos
    Route::get('/produtos', [ProdutoController::class, 'index']);
    Route::get('/produtos/{id}', [ProdutoController::class, 'show']);
    Route::post('/produtos', [ProdutoController::class, 'store'])
        ->middleware('perfil:admin,operador');
    Route::put('/produtos/{id}', [ProdutoController::class, 'update'])
        ->middleware('perfil:admin,operador');
    Route::delete('/produtos/{id}', [ProdutoController::class, 'destroy'])
        ->middleware('perfil:admin');

    // Lotes
    Route::get('/lotes', [LoteController::class, 'index']);
    Route::get('/lotes/{id}', [LoteController::class, 'show']);
    Route::post('/lotes', [LoteController::class, 'store'])
        ->middleware('perfil:admin,operador');
    Route::put('/lotes/{id}', [LoteController::class, 'update'])
        ->middleware('perfil:admin,operador');

    // Movimentos de estoque (entrada e saída)
    Route::get('/movimentos', [MovimentoController::class, 'index']);
    Route::post('/movimentos/entrada', [MovimentoController::class, 'entrada'])
        ->middleware('perfil:admin,operador');
    Route::post('/movimentos/saida', [MovimentoController::class, 'saida'])
        ->middleware('perfil:admin,operador');

    // Itens de lote disponíveis (para o select de movimentação)
    Route::get('/lotes/itens', [LoteController::class, 'listarItens']);

    // Relatórios (todos os perfis podem visualizar)
    Route::get('/relatorios/estoque',    [RelatorioController::class, 'estoque']);
    Route::get('/relatorios/vencimentos',[RelatorioController::class, 'vencimentos']);
    Route::get('/relatorios/auditoria',  [RelatorioController::class, 'auditoria']);

    // Usuários (só o admin pode gerenciar)
    Route::get('/usuarios', [UsuarioController::class, 'index'])
        ->middleware('perfil:admin');
    Route::post('/usuarios', [UsuarioController::class, 'store'])
        ->middleware('perfil:admin');
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])
        ->middleware('perfil:admin');
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])
        ->middleware('perfil:admin');
});
