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
        $produtosEstoqueCritico = $this->buscarProdutosEstoqueCritico();
        $produtosVencendo       = $this->buscarProdutosVencendo();
        $totalCategorias        = $this->contarCategorias();
        $movimentosRecentes     = $this->buscarMovimentosRecentes();
        $evolucao               = $this->calcularEvolucaoEstoque();
        $distribuicao           = $this->calcularDistribuicaoCategorias();
        $topProdutos            = $this->buscarTopProdutos();

        $resumo = [
            'totalProdutos'    => ItemLote::count(),
            'totalLotes'       => Lote::count(),
            'estoqueCritico'   => $produtosEstoqueCritico->count(),
            'vencendoEm30Dias' => $produtosVencendo->count(),
            'totalCategorias'  => $totalCategorias,
        ];

        return response()->json([
            'resumo'                 => $resumo,
            'produtosEstoqueCritico' => $produtosEstoqueCritico,
            'produtosVencendo'       => $produtosVencendo,
            'movimentosRecentes'     => $movimentosRecentes,
            'evolucaoEstoque'        => $evolucao,
            'distribuicaoCategorias' => $distribuicao,
            'topProdutos'            => $topProdutos,
        ]);
    }

    private function buscarProdutosEstoqueCritico()
    {
        return ItemLote::whereColumn('quantidade', '<=', 'estoque_minimo')
            ->where('estoque_minimo', '>', 0)
            ->get()
            ->map(fn($i) => [
                'id'             => $i->id_item,
                'nome'           => $i->nome,
                'quantidade'     => $i->quantidade,
                'estoque_minimo' => $i->estoque_minimo,
                'unidade_medida' => $i->unidade_medida,
            ]);
    }

    private function buscarProdutosVencendo()
    {
        $hoje     = Carbon::today();
        $em30dias = Carbon::today()->addDays(30);

        return ItemLote::with('lote')
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
    }

    private function contarCategorias(): int
    {
        return ItemLote::whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->count('categoria');
    }

    private function buscarMovimentosRecentes()
    {
        return Movimentacao::with(['item', 'usuario'])
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
    }

        private function calcularEvolucaoEstoque()
    {
         $inicio = Carbon::today()->subDays(29);

        $movs = Movimentacao::selectRaw("DATE(data_movimentacao) as dia, tipo, SUM(quantidade) as total")
        ->where('data_movimentacao', '>=', $inicio)
        ->groupBy('dia', 'tipo')
        ->get()
        ->groupBy('dia');

        $saldoInicial = Movimentacao::where('data_movimentacao', '<', $inicio)
        ->selectRaw("SUM(CASE WHEN tipo = 'ENTRADA' THEN quantidade ELSE -quantidade END) as saldo")
        ->value('saldo') ?? 0;

        $evolucao = collect();
        $acumulado = $saldoInicial;

        for ($i = 29; $i >= 0; $i--) {
        $dia = Carbon::today()->subDays($i);
        $chave = $dia->format('Y-m-d');
        $doDia = $movs->get($chave, collect());

        $entradas = $doDia->where('tipo', 'ENTRADA')->sum('total');
        $saidas   = $doDia->whereIn('tipo', ['SAIDA', 'PERDA'])->sum('total');
        $acumulado += $entradas - $saidas;

        $evolucao->push([
            'label'        => $dia->format('d/m'),
            'entradas'     => (int) $entradas,
            'saidas'       => (int) $saidas,
            'estoqueTotal' => max(0, (int) $acumulado),
        ]);
     }

        return $evolucao;
    }

    private function calcularDistribuicaoCategorias()
    {
        $distribuicao = ItemLote::whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->selectRaw('categoria, COUNT(*) as total')
            ->groupBy('categoria')
            ->get();

        $totalParaPercentual = $distribuicao->sum('total');

        return $distribuicao->map(fn($c) => [
            'categoria'  => $c->categoria,
            'quantidade' => $c->total,
            'percentual' => $totalParaPercentual > 0
                ? round(($c->total / $totalParaPercentual) * 100, 1)
                : 0,
        ])->sortByDesc('percentual')->values();
    }

    private function buscarTopProdutos()
    {
        return ItemLote::orderByDesc('quantidade')
            ->limit(10)
            ->get()
            ->map(fn($i) => [
                'id_produto'     => $i->id_item,
                'nome'           => $i->nome,
                'estoque_atual'  => $i->quantidade,
                'estoque_minimo' => $i->estoque_minimo,
                'categoria'      => $i->categoria ? ['nome' => $i->categoria] : null,
            ]);
    }
}