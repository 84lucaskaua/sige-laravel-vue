<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\Lote;
use App\Models\ItemLote;
use App\Models\Movimento;
use Illuminate\Http\JsonResponse;

/**
 * Controller do Dashboard
 *
 * Retorna os números de resumo e os movimentos recentes
 * para exibir na tela inicial do sistema.
 */
class DashboardController extends Controller
{
    /**
     * Retorna o resumo geral do almoxarifado
     */
    public function resumo(): JsonResponse
    {
        // Total de produtos ativos cadastrados
        $totalProdutos = Produto::where('ativo', true)->count();

        // Total de lotes cadastrados
        $totalLotes = Lote::count();

        // Conta itens com estoque abaixo do mínimo
        $estoqueCritico = ItemLote::whereColumn('quantidade', '<=', 'estoque_minimo')
            ->where('quantidade', '>', 0)
            ->count();

        // Conta itens que vencem nos próximos 30 dias (e ainda não venceram)
        $vencendoEm30Dias = ItemLote::whereNotNull('validade')
            ->where('validade', '>=', now())
            ->where('validade', '<=', now()->addDays(30))
            ->count();

        // Pega os 10 movimentos mais recentes para exibir no feed
        $movimentosRecentes = Movimento::with(['itemLote.produto', 'usuario'])
            ->orderByDesc('data_movimento')
            ->limit(10)
            ->get();

        return response()->json([
            'resumo' => [
                'totalProdutos'    => $totalProdutos,
                'totalLotes'       => $totalLotes,
                'estoqueCritico'   => $estoqueCritico,
                'vencendoEm30Dias' => $vencendoEm30Dias,
            ],
            'movimentosRecentes' => $movimentosRecentes,
        ]);
    }
}
