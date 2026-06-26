<?php

namespace App\Services;

use App\Models\ItemLote;
use App\Models\Movimentacao;

class AbcPriorityService
{
    public function recalcularTodos(): void
    {
        $itens = ItemLote::where('prioridade_manual', false)->get();

        if ($itens->isEmpty()) {
            return;
        }

        // Movimento real (entradas + saídas) por item, igual ao usado no relatório ABC.
        // Itens sem nenhuma movimentação registrada entram com 0.
        $movimentos = Movimentacao::whereIn('tipo', ['ENTRADA', 'SAIDA'])
            ->whereIn('id_item', $itens->pluck('id_item'))
            ->selectRaw('id_item, SUM(quantidade) as total')
            ->groupBy('id_item')
            ->pluck('total', 'id_item');

        $itens = $itens->map(function ($item) use ($movimentos) {
            $item->movimento_calculado = $movimentos[$item->id_item] ?? 0;
            return $item;
        })->sortByDesc('movimento_calculado')->values();

        $totalGeral = $itens->sum('movimento_calculado');

        if ($totalGeral <= 0) {
            foreach ($itens as $item) {
                if ($item->prioridade_abc !== 'C') {
                    $item->update(['prioridade_abc' => 'C']);
                }
            }
            return;
        }

        $acumulado = 0;

        foreach ($itens as $item) {
            $acumulado += $item->movimento_calculado;
            $percentual = $acumulado / $totalGeral;

            if ($percentual <= 0.80) {
                $classe = 'A';
            } elseif ($percentual <= 0.95) {
                $classe = 'B';
            } else {
                $classe = 'C';
            }

            if ($item->prioridade_abc !== $classe) {
                $item->update(['prioridade_abc' => $classe]);
            }
        }
    }
}