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

        // Guarda o total movimentado de cada item num array auxiliar,
        // SEM atribuir como atributo do model (evita que o Eloquent
        // tente salvar essa coluna inexistente no update()).
        $totaisPorItem = [];
        foreach ($itens as $item) {
            $totaisPorItem[$item->id_item] = $movimentos[$item->id_item] ?? 0;
        }

        $itensOrdenados = $itens->sortByDesc(function ($item) use ($totaisPorItem) {
            return $totaisPorItem[$item->id_item];
        })->values();

        $totalGeral = array_sum($totaisPorItem);

        if ($totalGeral <= 0) {
            foreach ($itensOrdenados as $item) {
                if ($item->prioridade_abc !== 'C') {
                    $item->update(['prioridade_abc' => 'C']);
                }
            }
            return;
        }

        $acumulado = 0;
        foreach ($itensOrdenados as $item) {
            $acumulado += $totaisPorItem[$item->id_item];
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