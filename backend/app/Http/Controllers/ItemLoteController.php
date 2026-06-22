<?php
namespace App\Http\Controllers;

use App\Models\ItemLote;
use Illuminate\Http\Request;

class ItemLoteController extends Controller
{
    public function store(Request $request, $idLote)
    {
        $request->validate([
            'nome'      => 'required|string',
            'quantidade' => 'required|integer|min:1',
            'categoria' => 'required|string',
        ]);

        $item = ItemLote::create([
            'id_lote'        => $idLote,
            'nome'           => $request->nome,
            'sku'            => $request->sku,
            'quantidade'     => $request->quantidade,
            'estoque_minimo' => $request->estoque_minimo ?? 0,
            'unidade_medida' => $request->unidade_medida ?? 'UN',
            'data_validade'  => $request->data_validade ?: null,
            'fornecedor'     => $request->fornecedor,
            'localizacao'    => $request->localizacao,
            'prioridade_abc' => $request->prioridade_abc ?: null,
            'categoria'      => $request->categoria,
        ]);

        return response()->json($item, 201);
    }

    public function index($idLote)
    {
        $itens = ItemLote::where('id_lote', $idLote)->get();
        return response()->json($itens);
    }
}