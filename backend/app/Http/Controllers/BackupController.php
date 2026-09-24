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

    $lotesBackup = $conteudo['lotes'] ?? [];
    $idsLotesValidos = array_column($lotesBackup, 'id_lote');

    $itensBackup = $conteudo['item_lotes'] ?? [];
    $itensValidos = array_filter($itensBackup, fn($i) => in_array($i['id_lote'], $idsLotesValidos));
    $itensDescartados = count($itensBackup) - count($itensValidos);

    $produtosBackup = $conteudo['produtos'] ?? [];
    $idsProdutosValidos = array_column($produtosBackup, 'id_produto');
    $itensValidos = array_filter($itensValidos, fn($i) => in_array($i['id_produto'], $idsProdutosValidos));

    DB::beginTransaction();
    try {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Movimentacao::truncate();
        ItemLote::truncate();
        Lote::truncate();
        Produto::truncate();

        foreach ($produtosBackup as $p) Produto::insert($p);
        foreach ($lotesBackup    as $l) Lote::insert($l);
        foreach ($itensValidos   as $i) ItemLote::insert($i);
        foreach ($conteudo['movimentacoes'] ?? [] as $m) Movimentacao::insert($m);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        DB::commit();

        $mensagem = 'Backup restaurado com sucesso!';
        if ($itensDescartados > 0) {
            $mensagem .= " ({$itensDescartados} item(ns) órfão(s) do backup foram descartados por referenciar lotes inexistentes.)";
        }

        return response()->json(['message' => $mensagem]);
    } catch (\Exception $e) {
        DB::rollBack();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        return response()->json(['message' => 'Erro ao restaurar: ' . $e->getMessage()], 500);
    }
}
}