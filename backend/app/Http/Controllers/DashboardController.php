<?php
namespace App\Http\Controllers;

use App\Models\ItemLote;
use App\Models\Lote;
use App\Models\Movimentacao;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $hoje     = Carbon::today();
        $em30dias = Carbon::today()->addDays(30);

        $totalLotes = Lote::count();
        $totalItens = ItemLote::count();

        $produtosEstoqueCritico = ItemLote::whereColumn('quantidade', '<=', 'estoque_minimo')
            ->where('estoque_minimo', '>', 0)
            ->get()
            ->map(fn($i) => [
                'id'             => $i->id_item,
                'nome'           => $i->nome,
                'quantidade'     => $i->quantidade,
                'estoque_minimo' => $i->estoque_minimo,
                'unidade_medida' => $i->unidade_medida,
            ]);

        $estoqueCritico = $produtosEstoqueCritico->count();

        $produtosVencendo = ItemLote::with('lote')
            ->whereNotNull('data_validade')
            ->whereBetween('data_validade', [$hoje, $em30dias])
            ->orderBy('data_validade')
            ->get()
            ->map(fn($i) => [
                'id'             => $i->id_item,
                'nome'           => $i->nome,
                'lote'           => $i->lote?->numero_lote,
                'data_validade'  => $i->data_validade,
                'dias_restantes' => Carbon::today()->diffInDays(Carbon::parse($i->data_validade), false),
                'quantidade'     => $i->quantidade,
                'unidade_medida' => $i->unidade_medida,
            ]);

        $vencendoEm30Dias = $produtosVencendo->count();

        $totalCategorias = ItemLote::whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->count('categoria');

        $movimentosRecentes = Movimentacao::with(['item', 'usuario'])
            ->orderByDesc('data_movimentacao')
            ->limit(10)
            ->get()
            ->map(fn($m) => [
                'id'             => $m->id_movimentacao,
                'tipo'           => strtolower($m->tipo),
                'quantidade'     => $m->quantidade,
                'data_movimento' => $m->data_movimentacao,
                'usuario'        => $m->usuario ? ['nome' => $m->usuario->name ?? $m->usuario->nome ?? null] : null,
                'item_lote'      => $m->item ? [
                    'produto' => ['nome' => $m->item->nome]
                ] : null,
            ]);

        $evolucao = collect();
        for ($i = 29; $i >= 0; $i--) {
            $dia = Carbon::today()->subDays($i);

            $entradas = Movimentacao::where('tipo', 'ENTRADA')
                ->whereDate('data_movimentacao', $dia)
                ->sum('quantidade');

            $saidas = Movimentacao::whereIn('tipo', ['SAIDA', 'PERDA'])
                ->whereDate('data_movimentacao', $dia)
                ->sum('quantidade');

            $estoqueTotal = Movimentacao::where('tipo', 'ENTRADA')
                ->whereDate('data_movimentacao', '<=', $dia)
                ->sum('quantidade')
                -
                Movimentacao::whereIn('tipo', ['SAIDA', 'PERDA'])
                ->whereDate('data_movimentacao', '<=', $dia)
                ->sum('quantidade');

            $evolucao->push([
                'label'        => $dia->format('d/m'),
                'entradas'     => (int) $entradas,
                'saidas'       => (int) $saidas,
                'estoqueTotal' => max(0, (int) $estoqueTotal),
            ]);
        }

        $distribuicao = ItemLote::whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->selectRaw('categoria, COUNT(*) as total')
            ->groupBy('categoria')
            ->get();

        $totalParaPercentual = $distribuicao->sum('total');

        $distribuicao = $distribuicao->map(fn($c) => [
            'categoria'  => $c->categoria,
            'quantidade' => $c->total,
            'percentual' => $totalParaPercentual > 0
                ? round(($c->total / $totalParaPercentual) * 100, 1)
                : 0,
        ])->sortByDesc('percentual')->values();

        $topProdutos = ItemLote::orderByDesc('quantidade')
            ->limit(10)
            ->get()
            ->map(fn($i) => [
                'id_produto'     => $i->id_item,
                'nome'           => $i->nome,
                'estoque_atual'  => $i->quantidade,
                'estoque_minimo' => $i->estoque_minimo,
                'categoria'      => $i->categoria ? ['nome' => $i->categoria] : null,
            ]);

        return response()->json([
            'resumo' => [
                'totalProdutos'    => $totalItens,
                'totalLotes'       => $totalLotes,
                'estoqueCritico'   => $estoqueCritico,
                'vencendoEm30Dias' => $vencendoEm30Dias,
                'totalCategorias'  => $totalCategorias,
            ],
            'produtosEstoqueCritico' => $produtosEstoqueCritico,
            'produtosVencendo'       => $produtosVencendo,
            'movimentosRecentes'     => $movimentosRecentes,
            'evolucaoEstoque'        => $evolucao,
            'distribuicaoCategorias' => $distribuicao,
            'topProdutos'            => $topProdutos,
        ]);
    }
}