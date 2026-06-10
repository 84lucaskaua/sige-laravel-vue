<?php

namespace App\Http\Controllers;

use App\Models\Movimento;
use App\Models\ItemLote;
use App\Models\LogAuditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller de Movimentos
 *
 * Cuida das entradas e saídas de produtos do estoque.
 * Toda movimentação atualiza a quantidade do item no lote.
 */
class MovimentoController extends Controller
{
    /**
     * Lista o histórico de movimentos
     *
     * Pode filtrar por tipo (entrada/saída), produto ou período.
     */
    public function index(Request $request): JsonResponse
    {
        $movimentos = Movimento::with(['itemLote.produto', 'usuario'])
            // Filtra por tipo se informado
            ->when($request->tipo, function ($query, $tipo) {
                $query->where('tipo', $tipo);
            })
            // Filtra por produto se informado
            ->when($request->produto_id, function ($query, $produtoId) {
                $query->whereHas('itemLote', function ($q) use ($produtoId) {
                    $q->where('produto_id', $produtoId);
                });
            })
            // Filtra por data inicial
            ->when($request->data_inicio, function ($query, $dataInicio) {
                $query->whereDate('data_movimento', '>=', $dataInicio);
            })
            // Filtra por data final
            ->when($request->data_fim, function ($query, $dataFim) {
                $query->whereDate('data_movimento', '<=', $dataFim);
            })
            ->orderByDesc('data_movimento')
            ->paginate(50); // Limita a 50 por página para não sobrecarregar

        return response()->json($movimentos);
    }

    /**
     * Registra uma nova entrada de produto no estoque
     */
    public function entrada(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'item_lote_id' => 'required|exists:itens_lote,id',
            'quantidade'   => 'required|integer|min:1',
            'fornecedor'   => 'nullable|string|max:255',
            'observacao'   => 'nullable|string',
        ]);

        // Busca o item do lote
        $itemLote = ItemLote::findOrFail($dados['item_lote_id']);

        // Cria o registro de entrada
        $movimento = Movimento::create([
            'item_lote_id'   => $itemLote->id,
            'usuario_id'     => $request->user()->id,
            'tipo'           => 'entrada',
            'quantidade'     => $dados['quantidade'],
            'fornecedor'     => $dados['fornecedor'] ?? null,
            'observacao'     => $dados['observacao'] ?? null,
            'data_movimento' => now(),
        ]);

        // Atualiza a quantidade no estoque
        $itemLote->increment('quantidade', $dados['quantidade']);

        // Registra no log de auditoria
        LogAuditoria::create([
            'usuario_id'  => $request->user()->id,
            'acao'        => 'entrada_estoque',
            'entidade'    => 'item_lote',
            'entidade_id' => $itemLote->id,
            'detalhes'    => [
                'produto'    => $itemLote->produto->nome ?? 'N/A',
                'quantidade' => $dados['quantidade'],
                'fornecedor' => $dados['fornecedor'] ?? null,
            ],
            'ip'        => $request->ip(),
            'criado_em' => now(),
        ]);

        return response()->json($movimento->load(['itemLote.produto', 'usuario']), 201);
    }

    /**
     * Registra uma saída de produto do estoque
     */
    public function saida(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'item_lote_id' => 'required|exists:itens_lote,id',
            'quantidade'   => 'required|integer|min:1',
            'motivo'       => 'required|string|max:255',
            'observacao'   => 'nullable|string',
        ]);

        $itemLote = ItemLote::findOrFail($dados['item_lote_id']);

        // Verifica se tem estoque suficiente antes de registrar a saída
        if ($itemLote->quantidade < $dados['quantidade']) {
            return response()->json([
                'mensagem' => "Estoque insuficiente. Disponível: {$itemLote->quantidade}",
            ], 422);
        }

        // Cria o registro de saída
        $movimento = Movimento::create([
            'item_lote_id'   => $itemLote->id,
            'usuario_id'     => $request->user()->id,
            'tipo'           => 'saida',
            'quantidade'     => $dados['quantidade'],
            'motivo'         => $dados['motivo'],
            'observacao'     => $dados['observacao'] ?? null,
            'data_movimento' => now(),
        ]);

        // Diminui a quantidade no estoque
        $itemLote->decrement('quantidade', $dados['quantidade']);

        // Registra no log de auditoria
        LogAuditoria::create([
            'usuario_id'  => $request->user()->id,
            'acao'        => 'saida_estoque',
            'entidade'    => 'item_lote',
            'entidade_id' => $itemLote->id,
            'detalhes'    => [
                'produto'    => $itemLote->produto->nome ?? 'N/A',
                'quantidade' => $dados['quantidade'],
                'motivo'     => $dados['motivo'],
            ],
            'ip'        => $request->ip(),
            'criado_em' => now(),
        ]);

        return response()->json($movimento->load(['itemLote.produto', 'usuario']), 201);
    }
}
