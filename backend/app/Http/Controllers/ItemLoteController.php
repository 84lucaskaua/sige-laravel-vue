<?php

namespace App\Http\Controllers;

use App\Models\ItemLote;
use App\Models\Movimentacao;
use App\Jobs\RecalcularAbcJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItemLoteController extends Controller
{
    public function store(Request $request, int $idLote)
    {
        $request->validate([
            'nome'           => 'required|string|min:2|max:255',
            'sku'            => 'nullable|string|max:50',
            'quantidade'     => 'required|integer|min:1',
            'categoria'      => 'required|string',
            'data_validade'  => 'nullable|date|after:today|before:2100-01-01',
            'estoque_minimo' => 'nullable|integer|min:0',
        ], [
            'nome.required'          => 'O nome é obrigatório.',
            'nome.min'               => 'O nome deve ter pelo menos 2 caracteres.',
            'nome.max'               => 'O nome não pode ter mais de 255 caracteres.',
            'sku.max'                => 'O SKU não pode ter mais de 50 caracteres.',
            'quantidade.required'    => 'A quantidade é obrigatória.',
            'quantidade.integer'     => 'A quantidade deve ser um número inteiro.',
            'quantidade.min'         => 'A quantidade não pode ser negativa.',
            'categoria.required'     => 'A categoria é obrigatória.',
            'data_validade.date'     => 'Informe uma data válida.',
            'data_validade.after'    => 'A data de validade deve ser futura.',
            'data_validade.before'   => 'A data de validade informada é inválida.',
            'estoque_minimo.integer' => 'O estoque mínimo deve ser um número inteiro.',
            'estoque_minimo.min'     => 'O estoque mínimo não pode ser negativo.',
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

        RecalcularAbcJob::dispatch();

        return response()->json($item->refresh(), 201);
    }

    public function index(int $idLote)
    {
        $itens = ItemLote::where('id_lote', $idLote)
            ->orderBy('ordem')
            ->get();
        return response()->json($itens);
    }

    public function update(Request $request, int $id)
    {
        $item = ItemLote::findOrFail($id);

        $request->validate([
            'nome'           => 'required|string|min:2|max:255',
            'sku'            => 'nullable|string|max:50',
            'quantidade'     => 'required|integer|min:0',
            'categoria'      => 'required|string',
            'data_validade'  => 'nullable|date|after:today|before:2100-01-01',
            'estoque_minimo' => 'nullable|integer|min:0',
        ], [
            'nome.required'          => 'O nome é obrigatório.',
            'nome.min'               => 'O nome deve ter pelo menos 2 caracteres.',
            'nome.max'               => 'O nome não pode ter mais de 255 caracteres.',
            'sku.max'                => 'O SKU não pode ter mais de 50 caracteres.',
            'quantidade.required'    => 'A quantidade é obrigatória.',
            'quantidade.integer'     => 'A quantidade deve ser um número inteiro.',
            'quantidade.min'         => 'A quantidade não pode ser negativa.',
            'categoria.required'     => 'A categoria é obrigatória.',
            'data_validade.date'     => 'Informe uma data válida.',
            'data_validade.after'    => 'A data de validade deve ser futura.',
            'data_validade.before'   => 'A data de validade informada é inválida.',
            'estoque_minimo.integer' => 'O estoque mínimo deve ser um número inteiro.',
            'estoque_minimo.min'     => 'O estoque mínimo não pode ser negativo.',
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

        RecalcularAbcJob::dispatch();

        return response()->json($item->refresh());
    }

    public function baixa(Request $request, int $id)
    {
        $item = ItemLote::findOrFail($id);

        $request->validate([
            'quantidade' => 'required|integer|min:1|max:' . $item->quantidade,
            'motivo'     => 'nullable|string|max:255',
        ], [
            'quantidade.required' => 'A quantidade é obrigatória.',
            'quantidade.integer'  => 'A quantidade deve ser um número inteiro.',
            'quantidade.min'      => 'A quantidade deve ser pelo menos 1.',
            'quantidade.max'      => 'A quantidade não pode ser maior que o estoque disponível.',
            'motivo.max'          => 'O motivo não pode ter mais de 255 caracteres.',
        ]);

        $item->update([
            'quantidade' => $item->quantidade - $request->quantidade,
        ]);

        Movimentacao::registrar('SAIDA', $request->quantidade, $item->id_lote, $item->id_item, $request->motivo);

        RecalcularAbcJob::dispatch();

        return response()->json($item->refresh());
    }

    public function entrada(Request $request, int $id)
    {
        $request->validate([
            'quantidade' => 'required|integer|min:1',
            'motivo'     => 'nullable|string|max:255',
        ], [
            'quantidade.required' => 'A quantidade é obrigatória.',
            'quantidade.integer'  => 'A quantidade deve ser um número inteiro.',
            'quantidade.min'      => 'A quantidade deve ser pelo menos 1.',
            'motivo.max'          => 'O motivo não pode ter mais de 255 caracteres.',
        ]);

        $item = ItemLote::findOrFail($id);
        $item->quantidade += $request->quantidade;
        $item->save();

        Movimentacao::registrar('ENTRADA', $request->quantidade, $item->id_lote, $item->id_item, $request->motivo);

        RecalcularAbcJob::dispatch();

        return response()->json($item->refresh());
    }

    public function reordenar(Request $request, int $idLote)
    {
        $request->validate([
            'itens'           => 'required|array',
            'itens.*.id_item' => 'required|integer|exists:item_lote,id_item',
            'itens.*.ordem'   => 'required|integer|min:0',
        ], [
            'itens.required'           => 'A lista de itens é obrigatória.',
            'itens.array'              => 'A lista de itens está em formato inválido.',
            'itens.*.id_item.required' => 'O ID do item é obrigatório.',
            'itens.*.id_item.exists'   => 'Um dos itens informados não existe.',
            'itens.*.ordem.required'   => 'A ordem é obrigatória.',
            'itens.*.ordem.min'        => 'A ordem não pode ser negativa.',
        ]);

        $idsDoLote = ItemLote::where('id_lote', $idLote)->pluck('id_item')->toArray();

        foreach ($request->itens as $item) {
            if (!in_array($item['id_item'], $idsDoLote)) {
                return response()->json(['message' => 'Item não pertence a este lote.'], 422);
            }
        }

        DB::transaction(function () use ($request) {
            foreach ($request->itens as $item) {
                ItemLote::where('id_item', $item['id_item'])
                    ->update(['ordem' => $item['ordem']]);
            }
        });

        return response()->json(['message' => 'Ordem atualizada com sucesso.']);
    }

    public function destroy(int $id)
    {
        $item = ItemLote::findOrFail($id);
        $item->delete();

        RecalcularAbcJob::dispatch();

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