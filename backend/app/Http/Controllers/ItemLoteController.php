<?php

namespace App\Http\Controllers;

use App\Models\ItemLote;
use App\Models\Movimentacao;
use App\Services\AbcPriorityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemLoteController extends Controller
{
    protected AbcPriorityService $abcService;

    public function __construct(AbcPriorityService $abcService)
    {
        $this->abcService = $abcService;
    }

    public function store(Request $request, int $idLote)
    {
        $request->validate([
            'nome'       => 'required|string',
            'quantidade' => 'required|integer|min:1',
            'categoria'  => 'required|string',
        ]);

        $ehManual = $request->filled('prioridade_abc');

        $item = ItemLote::create([
            'id_lote'           => $idLote,
            'nome'              => $request->nome,
            'sku'               => $request->sku,
            'quantidade'        => $request->quantidade,
            'estoque_minimo'    => $request->estoque_minimo ?? 0,
            'unidade_medida'    => $request->unidade_medida ?? 'UN',
            'data_validade'     => $request->data_validade ?: null,
            'fornecedor'        => $request->fornecedor,
            'localizacao'       => $request->localizacao,
            'prioridade_abc'    => $ehManual ? $request->prioridade_abc : null,
            'prioridade_manual' => $ehManual,
            'categoria'         => $request->categoria,
        ]);

        $this->abcService->recalcularTodos();

        return response()->json($item->refresh(), 201);
    }

    public function index(int $idLote)
    {
        $itens = ItemLote::where('id_lote', $idLote)->get();
        return response()->json($itens);
    }

    public function update(Request $request, int $id)
    {
        $item = ItemLote::findOrFail($id);

        $request->validate([
            'nome'       => 'required|string',
            'quantidade' => 'required|integer|min:0',
            'categoria'  => 'required|string',
        ]);

        $ehManual = $request->filled('prioridade_abc');

        $dados = $request->only([
            'nome', 'sku', 'quantidade', 'estoque_minimo',
            'unidade_medida', 'data_validade', 'fornecedor',
            'localizacao', 'categoria',
        ]);

        $dados['prioridade_manual'] = $ehManual;
        $dados['prioridade_abc']    = $ehManual ? $request->prioridade_abc : null;

        $item->update($dados);

        $this->abcService->recalcularTodos();

        return response()->json($item->refresh());
    }

    public function baixa(Request $request, int $id)
    {
        $item = ItemLote::findOrFail($id);

        $request->validate([
            'quantidade' => 'required|integer|min:1|max:' . $item->quantidade,
            'motivo'     => 'nullable|string|max:255',
        ]);

        $item->update([
            'quantidade' => $item->quantidade - $request->quantidade,
        ]);

        Movimentacao::create([
            'tipo'              => 'SAIDA',
            'quantidade'        => $request->quantidade,
            'data_movimentacao' => now(),
            'observacao'        => $request->motivo,
            'id_lote'           => $item->id_lote,
            'id_item'           => $item->id_item,
            'id_usuario'        => Auth::id(),
        ]);

        $this->abcService->recalcularTodos();

        return response()->json($item->refresh());
    }

    public function entrada(Request $request, int $id)
    {
        $request->validate([
            'quantidade' => 'required|integer|min:1',
            'motivo'     => 'nullable|string|max:255',
        ]);

        $item = ItemLote::findOrFail($id);
        $item->quantidade += $request->quantidade;
        $item->save();

        Movimentacao::create([
            'tipo'              => 'ENTRADA',
            'quantidade'        => $request->quantidade,
            'data_movimentacao' => now(),
            'observacao'        => $request->motivo,
            'id_lote'           => $item->id_lote,
            'id_item'           => $item->id_item,
            'id_usuario'        => Auth::id(),
        ]);

        $this->abcService->recalcularTodos();

        return response()->json($item->refresh());
    }

    public function destroy(int $id)
    {
        $item = ItemLote::findOrFail($id);
        $item->delete();

        $this->abcService->recalcularTodos();

        return response()->json(['message' => 'Item excluído com sucesso.']);
    }

    public function historico(int $id)
    {
        $item = ItemLote::findOrFail($id);
        return response()->json(
            Movimentacao::where('id_item', $item->id_item)
                ->orderBy('data_movimentacao', 'desc')
                ->get()
        );
    }
}