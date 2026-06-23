<?php
namespace App\Http\Controllers;

use App\Models\Lote;
use Illuminate\Http\Request;

class LoteController extends Controller
{
    public function index()
{
    $lotes = Lote::with('itens')->orderBy('id_lote', 'asc')->get();
    return response()->json($lotes);
}

    public function store(Request $request)
    {
        $request->validate([
            'numero'       => 'required|string|unique:lote,numero_lote',
            'data_entrada' => 'required|date',
        ]);

        $lote = Lote::create([
            'numero_lote'  => $request->numero,
            'data_entrada' => $request->data_entrada,
            'descricao'    => $request->descricao,
            'status'       => 'ATIVO',
        ]);

        return response()->json($lote, 201);
    }

    public function destroy($id)
    {
        $lote = Lote::findOrFail($id);
        $lote->delete();
        return response()->json(['message' => 'Lote excluído com sucesso.']);
    }
    public function update(Request $request, $id)
{
    $lote = Lote::findOrFail($id);

    $request->validate([
        'numero'       => 'required|string|unique:lote,numero_lote,' . $id . ',id_lote',
        'data_entrada' => 'required|date',
        'descricao'    => 'nullable|string',
    ]);

    $lote->update([
        'numero_lote'  => $request->numero,
        'data_entrada' => $request->data_entrada,
        'descricao'    => $request->descricao,
    ]);

    return response()->json($lote);
}
}