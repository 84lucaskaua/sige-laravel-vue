<?php

namespace App\Http\Controllers;

use App\Models\ItemLote;
use App\Models\Movimentacao;
use Carbon\Carbon;

class RelatorioController extends Controller
{
    // Posição atual do estoque agrupada por item
    public function estoque()
    {
        $itens = ItemLote::all()->map(fn($item) => [
            'produto_id'      => $item->id_item,
            'nome'            => $item->nome,
            'categoria'       => $item->categoria ?? '—',
            'quantidade_total' => $item->quantidade,
            'estoque_minimo'  => $item->estoque_minimo ?? 0,
            'unidade'         => $item->unidade_medida ?? 'UN',
        ]);

        return response()->json($itens);
    }

    // Itens vencidos ou que vencem nos próximos 30 dias
    public function vencimentos()
    {
        $limite = Carbon::now()->addDays(30);

        $itens = ItemLote::whereNotNull('data_validade')
            ->where('data_validade', '<=', $limite)
            ->where('quantidade', '>', 0)
            ->with('lote')
            ->get()
            ->map(fn($item) => [
                'id'       => $item->id_item,
                'produto'  => ['nome' => $item->nome, 'unidade' => $item->unidade_medida],
                'lote'     => ['numero' => $item->lote?->numero_lote ?? '—'],
                'quantidade' => $item->quantidade,
                'validade' => $item->data_validade,
            ]);

        return response()->json($itens);
    }

    // Log de auditoria baseado nas movimentações
    public function auditoria()
    {
        $logs = Movimentacao::with(['item', 'usuario'])
            ->orderBy('data_movimentacao', 'desc')
            ->limit(200)
            ->get()
            ->map(fn($m) => [
                'id'       => $m->id_movimentacao,
                'usuario'  => ['nome' => $m->usuario?->name ?? 'Sistema'],
                'acao'     => $m->tipo,
                'detalhes' => [
                    'produto'    => $m->item?->nome ?? '—',
                    'quantidade' => $m->quantidade,
                    'observacao' => $m->observacao ?? '—',
                ],
                'criado_em' => $m->data_movimentacao,
            ]);

        return response()->json($logs);
    }
    public function itens()
{
    $itens = ItemLote::with('lote')->get();
    return response()->json($itens);
}
}