<?php

namespace App\Services;

use App\Models\ItemLote;

class AbcPriorityService
{
    public function recalcularTodos(): void
    {
        $itens = ItemLote::where('prioridade_manual', false)
            ->orderByDesc('quantidade')
            ->get();

        if ($itens->isEmpty()) {
            return;
        }

        $totalGeral = $itens->sum('quantidade');

        if ($totalGeral <= 0) {
            foreach ($itens as $item) {
                $item->update(['prioridade_abc' => 'C']);
            }
            return;
        }

        $acumulado = 0;

        foreach ($itens as $item) {
            $acumulado += $item->quantidade;
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