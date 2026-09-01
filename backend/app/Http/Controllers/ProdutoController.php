<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\ItemLote;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    public function index(Request $request)
    {
        $query = Produto::query()
            ->withSum('itensLote', 'quantidade')
            ->with(['itensLote' => function ($q) {
                $q->whereNotNull('data_validade')->orderBy('data_validade', 'asc');
            }]);

        if ($request->boolean('estoque_baixo')) {
            // agora compara o total agregado (via having, depois do withSum) em vez do campo por-lote
            $query->havingRaw('COALESCE(itens_lote_sum_quantidade, 0) <= estoque_minimo');
        }

        if ($request->filled('busca')) {
            $query->where(function ($q) use ($request) {
                $q->where('nome', 'like', '%' . $request->busca . '%')
                  ->orWhere('sku', 'like', '%' . $request->busca . '%');
            });
        }

        $produtos = $query->get()->map(function ($produto) {
            $produto->quantidade_total   = $produto->itens_lote_sum_quantidade ?? 0;
            $produto->proxima_validade   = optional($produto->itensLote->first())->data_validade;
            return $produto;
        });

        return response()->json($produtos);
    }

    public function destroy(int $id)
    {
        // Agora exclui o Produto (e os item_lote em cascata, via FK cascadeOnDelete)
        $produto = Produto::findOrFail($id);
        $produto->delete();

        return response()->json(['message' => 'Produto excluído com sucesso.']);
    }
}