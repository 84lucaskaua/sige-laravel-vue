<?php
namespace App\Http\Controllers;

use App\Models\Lote;
use App\Helpers\AuditHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        AuditHelper::log('Criacao', 'Lote "' . $lote->numero_lote . '" criado.');

        return response()->json($lote, 201);
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

        AuditHelper::log('Edicao', 'Lote "' . $lote->numero_lote . '" atualizado.');

        return response()->json($lote);
    }

    public function destroy($id)
    {
        $lote = Lote::findOrFail($id);

        DB::table('movimentacao')->where('id_lote', $id)->delete();

        AuditHelper::log('Exclusao', 'Lote "' . $lote->numero_lote . '" excluido.');

        $lote->delete();
        return response()->json(['message' => 'Lote excluido com sucesso.']);
    }
}