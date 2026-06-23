<?php

namespace App\Http\Controllers;

use App\Models\ItemLote;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemLote::with('lote');

        if ($request->boolean('estoque_baixo')) {
            $query->whereColumn('quantidade', '<=', 'estoque_minimo');
        }

        if ($request->filled('busca')) {
            $query->where(function ($q) use ($request) {
                $q->where('nome', 'like', '%' . $request->busca . '%')
                  ->orWhere('sku', 'like', '%' . $request->busca . '%');
            });
        }

        return response()->json($query->get());
    }

    public function destroy($id)
    {
        $item = ItemLote::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Produto excluído com sucesso.']);
    }
}