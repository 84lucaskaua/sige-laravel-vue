<?php
namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\Lote;
use App\Models\Movimentacao;
use App\Models\Categoria;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $hoje    = Carbon::today();
        $em30dias = Carbon::today()->addDays(30);

        // Cards
        $totalProdutos   = Produto::count();
        $totalLotes      = Lote::where('status', 'ATIVO')->count();
        $totalCategorias = Categoria::count();

        $estoqueCritico = Produto::whereColumn('estoque_atual', '<=', 'estoque_minimo')
            ->where('estoque_minimo', '>', 0)
            ->count();

        $vencendoEm30Dias = Lote::where('status', 'ATIVO')
            ->whereNotNull('data_validade')
            ->whereBetween('data_validade', [$hoje, $em30dias])
            ->count();

        // Movimentos recentes
        $movimentosRecentes = Movimentacao::with(['lote.produto', 'usuario'])
            ->orderByDesc('data_movimentacao')
            ->limit(10)
            ->get()
            ->map(fn($m) => [
                'id'             => $m->id_movimentacao,
                'tipo'           => strtolower($m->tipo),
                'quantidade'     => $m->quantidade,
                'data_movimento' => $m->data_movimentacao,
                'usuario'        => $m->usuario ? ['nome' => $m->usuario->nome] : null,
                'item_lote'      => $m->lote ? [
                    'produto' => $m->lote->produto ? ['nome' => $m->lote->produto->nome] : null
                ] : null,
            ]);

        // Evolução do estoque — últimos 30 dias
        $evolucao = collect();
        for ($i = 29; $i >= 0; $i--) {
            $dia = Carbon::today()->subDays($i);

            $entradas = Movimentacao::whereIn('tipo', ['ENTRADA'])
                ->whereDate('data_movimentacao', $dia)
                ->sum('quantidade');

            $saidas = Movimentacao::whereIn('tipo', ['SAIDA', 'PERDA'])
                ->whereDate('data_movimentacao', $dia)
                ->sum('quantidade');

            $estoqueTotal = Movimentacao::whereIn('tipo', ['ENTRADA'])
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

        // Distribuição por categoria
        $distribuicao = Categoria::withCount('produtos')
            ->having('produtos_count', '>', 0)
            ->get();

        $totalItens = $distribuicao->sum('produtos_count');

        $distribuicao = $distribuicao->map(fn($c) => [
            'categoria'  => $c->nome,
            'quantidade' => $c->produtos_count,
            'percentual' => $totalItens > 0
                ? round(($c->produtos_count / $totalItens) * 100, 1)
                : 0,
        ])->sortByDesc('percentual')->values();

        return response()->json([
            'resumo' => [
                'totalProdutos'    => $totalProdutos,
                'totalLotes'       => $totalLotes,
                'estoqueCritico'   => $estoqueCritico,
                'vencendoEm30Dias' => $vencendoEm30Dias,
                'totalCategorias'  => $totalCategorias,
            ],
            'movimentosRecentes'     => $movimentosRecentes,
            'evolucaoEstoque'        => $evolucao,
            'distribuicaoCategorias' => $distribuicao,
        ]);
    }
}
