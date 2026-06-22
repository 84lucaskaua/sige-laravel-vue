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
    public function update(Request $request, $id)
{
    $item = ItemLote::findOrFail($id);

    $request->validate([
        'nome'       => 'required|string',
        'quantidade' => 'required|integer|min:0',
        'categoria'  => 'required|string',
    ]);

    $item->update($request->only([
        'nome', 'sku', 'quantidade', 'estoque_minimo',
        'unidade_medida', 'data_validade', 'fornecedor',
        'localizacao', 'prioridade_abc', 'categoria',
    ]));

    return response()->json($item);
}
public function baixa(Request $request, $id)
{
    $item = ItemLote::findOrFail($id);

    $request->validate([
        'quantidade' => 'required|integer|min:1|max:' . $item->quantidade,
    ]);

    $item->update([
        'quantidade' => $item->quantidade - $request->quantidade,
    ]);

    return response()->json($item);
}
public function entrada(Request $request, $id)
{
    $request->validate([
        'quantidade' => 'required|integer|min:1',
        'motivo'     => 'nullable|string|max:255',
    ]);

    $item = ItemLote::findOrFail($id);
    $item->quantidade += $request->quantidade;
    $item->save();

    return response()->json($item);
}
}