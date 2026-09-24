<?php

namespace App\Http\Controllers;

use App\Models\Movimentacao;
use App\Helpers\AuditHelper;
use Carbon\Carbon;

class MovimentacaoController extends Controller
{
    public function index()
    {
        $movimentacoes = Movimentacao::with(['lote', 'item.produto', 'usuario'])
            ->orderBy('data_movimentacao', 'desc')
            ->get()
            ->map(fn($m) => [
                'id'         => $m->id_movimentacao,
                'data'       => Carbon::parse($m->data_movimentacao)
                        ->setTimezone('America/Sao_Paulo')
                        ->format('Y-m-d\TH:i:s'),
                'produto'    => $m->item?->produto?->nome ?? '—',
                'sku'        => $m->item?->produto?->sku  ?? '—',
                'lote'       => $m->lote?->numero_lote    ?? '—',
                'tipo'       => $m->tipo === 'ENTRADA' ? 'Entrada' : 'Saída',
                'quantidade' => $m->quantidade,
                'motivo'     => $m->observacao ?? '',
                'usuario'    => $m->usuario?->name ?? '—',
    ]);

        return response()->json($movimentacoes);
    }

    public function destroy(int $id)
    {
        $mov = Movimentacao::with(['item.produto'])->findOrFail($id);

        AuditHelper::log(
            'Exclusao',
            'Movimentação de ' . ($mov->tipo === 'ENTRADA' ? 'entrada' : 'saída') .
            ' (' . $mov->quantidade . ' un.) do produto "' . ($mov->item?->produto?->nome ?? '—') . '" excluída.'
        );

        $mov->delete();
        return response()->json(['message' => 'Movimentação excluída.']);
    }
}