<?php

namespace App\Http\Controllers;

use App\Models\ItemLote;
use App\Models\LogAuditoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

/**
 * Controller de Relatórios
 *
 * Gera relatórios para as três abas da página de relatórios:
 * - Posição atual do estoque
 * - Produtos vencidos ou próximos do vencimento
 * - Log de auditoria (quem fez o quê no sistema)
 */
class RelatorioController extends Controller
{
    /**
     * Relatório de estoque atual
     *
     * Agrupa todos os itens de lote por produto e soma as quantidades.
     * Mostra a situação de cada produto (normal, crítico ou zerado).
     */
    public function estoque(): JsonResponse
    {
        $estoque = DB::table('itens_lote')
            ->join('produtos', 'itens_lote.produto_id', '=', 'produtos.id')
            ->leftJoin('categorias', 'produtos.categoria_id', '=', 'categorias.id')
            ->where('produtos.ativo', true)
            ->groupBy('produtos.id', 'produtos.nome', 'produtos.unidade', 'produtos.estoque_minimo', 'categorias.nome')
            ->select(
                'produtos.id as produto_id',
                'produtos.nome',
                'produtos.unidade',
                'produtos.estoque_minimo',
                'categorias.nome as categoria',
                DB::raw('SUM(itens_lote.quantidade) as quantidade_total')
            )
            ->orderBy('produtos.nome')
            ->get();

        return response()->json($estoque);
    }

    /**
     * Relatório de vencimentos
     *
     * Lista itens que já venceram ou que vencem nos próximos 30 dias.
     */
    public function vencimentos(): JsonResponse
    {
        $itensComProblema = ItemLote::with(['produto', 'lote'])
            ->whereNotNull('validade')
            // Vencidos (validade antes de hoje) ou vencendo nos próximos 30 dias
            ->where(function ($query) {
                $query->where('validade', '<', now())
                      ->orWhere(function ($q) {
                          $q->where('validade', '>=', now())
                            ->where('validade', '<=', now()->addDays(30));
                      });
            })
            ->orderBy('validade')
            ->get();

        return response()->json($itensComProblema);
    }

    /**
     * Log de auditoria
     *
     * Retorna as últimas 200 ações registradas no sistema.
     */
    public function auditoria(): JsonResponse
    {
        $logs = LogAuditoria::with('usuario')
            ->orderByDesc('criado_em')
            ->limit(200)
            ->get();

        return response()->json($logs);
    }
}
