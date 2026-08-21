<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\Lote;
use App\Models\ItemLote;
use App\Models\Movimentacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackupController extends Controller
{
    public function exportarBackup()
    {
        $dados = [
            'exportado_em'  => now()->toISOString(),
            'produtos'      => Produto::all(),
            'lotes'         => Lote::all(),
            'item_lotes'    => ItemLote::all(),
            'movimentacoes' => Movimentacao::all(),
        ];

        return response()->json($dados, 200, [
            'Content-Disposition' => 'attachment; filename="backup_sige_' . now()->format('Ymd_His') . '.json"',
        ]);
    }

    public function restaurarBackup(Request $request)
    {
        $request->validate(['arquivo' => 'required|file|mimes:json,txt']);

        $conteudo = json_decode(
            file_get_contents($request->file('arquivo')->getRealPath()),
            true
        );

        if (!$conteudo) {
            return response()->json(['message' => 'JSON inválido.'], 422);
        }

        DB::beginTransaction();
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Movimentacao::truncate();
            ItemLote::truncate();
            Lote::truncate();
            Produto::truncate();

            foreach ($conteudo['produtos']      ?? [] as $p) Produto::insert($p);
            foreach ($conteudo['lotes']         ?? [] as $l) Lote::insert($l);
            foreach ($conteudo['item_lotes']    ?? [] as $i) ItemLote::insert($i);
            foreach ($conteudo['movimentacoes'] ?? [] as $m) Movimentacao::insert($m);

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();

            return response()->json(['message' => 'Backup restaurado com sucesso!']);
        } catch (\Exception $e) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return response()->json(['message' => 'Erro ao restaurar: ' . $e->getMessage()], 500);
        }
    }
}