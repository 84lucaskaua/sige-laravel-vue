<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\ItemLote;
use App\Models\LogAuditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Controller de Lotes
 *
 * Gerencia os lotes de entrada de mercadoria.
 * Um lote pode ter vários itens (produtos diferentes).
 */
class LoteController extends Controller
{
    /**
     * Lista todos os lotes com seus itens
     */
    public function index(): JsonResponse
    {
        $lotes = Lote::with(['itens.produto', 'itens.lote'])
            ->orderByDesc('data_entrada')
            ->get();

        return response()->json($lotes);
    }

    /**
     * Mostra um lote específico com todos os detalhes
     */
    public function show(int $id): JsonResponse
    {
        $lote = Lote::with(['itens.produto'])->findOrFail($id);

        return response()->json($lote);
    }

    /**
     * Retorna todos os itens de lote para o select de movimentação
     */
    public function listarItens(): JsonResponse
    {
        $itens = ItemLote::with(['produto', 'lote'])
            ->where('quantidade', '>', 0)
            ->get();

        return response()->json($itens);
    }

    /**
     * Cria um novo lote com seus itens
     *
     * Usa uma "transaction" para garantir que, se der erro ao salvar
     * algum item, o lote inteiro seja cancelado (consistência dos dados).
     */
    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'numero'       => 'required|string|unique:lotes,numero',
            'data_entrada' => 'required|date',
            'descricao'    => 'nullable|string',
            // Valida cada item dentro do array
            'itens'                  => 'nullable|array',
            'itens.*.produto_id'     => 'required|exists:produtos,id',
            'itens.*.quantidade'     => 'required|integer|min:1',
            'itens.*.estoque_minimo' => 'nullable|integer|min:0',
            'itens.*.validade'       => 'nullable|date',
            'itens.*.localizacao'    => 'nullable|string',
            'itens.*.fornecedor'     => 'nullable|string',
            'itens.*.prioridade'     => 'nullable|in:A,B,C',
        ]);

        // Tudo dentro desta transação deve funcionar junto
        $lote = DB::transaction(function () use ($dados, $request) {
            // Cria o lote
            $lote = Lote::create([
                'numero'       => $dados['numero'],
                'data_entrada' => $dados['data_entrada'],
                'descricao'    => $dados['descricao'] ?? null,
            ]);

            // Cria cada item do lote
            foreach ($dados['itens'] ?? [] as $dadosDoItem) {
                ItemLote::create([
                    'lote_id'        => $lote->id,
                    'produto_id'     => $dadosDoItem['produto_id'],
                    'quantidade'     => $dadosDoItem['quantidade'],
                    'estoque_minimo' => $dadosDoItem['estoque_minimo'] ?? 0,
                    'validade'       => $dadosDoItem['validade']    ?? null,
                    'localizacao'    => $dadosDoItem['localizacao'] ?? null,
                    'fornecedor'     => $dadosDoItem['fornecedor']  ?? null,
                    'prioridade'     => $dadosDoItem['prioridade']  ?? null,
                ]);
            }

            // Registra no log de auditoria
            LogAuditoria::create([
                'usuario_id'  => $request->user()->id,
                'acao'        => 'criou_lote',
                'entidade'    => 'lote',
                'entidade_id' => $lote->id,
                'detalhes'    => [
                    'numero'         => $lote->numero,
                    'total_de_itens' => count($dados['itens'] ?? []),
                ],
                'ip'        => $request->ip(),
                'criado_em' => now(),
            ]);

            return $lote;
        });

        return response()->json($lote->load('itens.produto'), 201);
    }

    /**
     * Atualiza dados básicos de um lote (sem alterar os itens)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $lote = Lote::findOrFail($id);

        $dados = $request->validate([
            'numero'       => "required|string|unique:lotes,numero,{$id}",
            'data_entrada' => 'required|date',
            'descricao'    => 'nullable|string',
        ]);

        $lote->update($dados);

        return response()->json($lote->load('itens.produto'));
    }
}
