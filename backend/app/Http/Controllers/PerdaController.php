<?php

namespace App\Http\Controllers;

use App\Models\Movimentacao;
use App\Models\ItemLote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PerdaController extends Controller
{
    public function index()
    {
        $perdas = Movimentacao::with('item')
            ->where('tipo', 'PERDA')
            ->orderBy('data_movimentacao', 'desc')
            ->get();

        return response()->json($perdas);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_item'    => 'required|integer|exists:item_lote,id_item',
            'quantidade' => 'required|integer|min:1',
            'motivo'     => 'required|string|max:255',
        ]);

        $item = ItemLote::findOrFail($request->id_item);

        if ($request->quantidade > $item->quantidade) {
            return response()->json([
                'message' => 'Quantidade maior que o estoque disponível.',
            ], 422);
        }

        $item->update([
            'quantidade' => $item->quantidade - $request->quantidade,
        ]);

        $perda = Movimentacao::create([
            'tipo'              => 'PERDA',
            'quantidade'        => $request->quantidade,
            'data_movimentacao' => now(),
            'observacao'        => $request->motivo,
            'id_lote'           => $item->id_lote,
            'id_item'           => $item->id_item,
            'id_usuario'        => Auth::id(),
        ]);

        return response()->json($perda->load('item'), 201);
    }

    public function estatisticas()
    {
        $total     = Movimentacao::where('tipo', 'PERDA')->count();
        $unidades  = Movimentacao::where('tipo', 'PERDA')->sum('quantidade');
        $esteMes   = Movimentacao::where('tipo', 'PERDA')
            ->whereMonth('data_movimentacao', now()->month)
            ->whereYear('data_movimentacao', now()->year)
            ->count();

        return response()->json([
            'total'    => $total,
            'unidades' => $unidades,
            'esteMes'  => $esteMes,
        ]);
    }
}