<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatbotController extends Controller
{
    public function perguntar(Request $request)
    {
        $request->validate([
            'mensagem' => 'required|string|max:500',
        ]);

        $pergunta = mb_strtolower(trim($request->input('mensagem')));

        // ---- Detecta a intenção pela pergunta ----

        if ($this->contem($pergunta, ['oi', 'ola', 'olá', 'bom dia', 'boa tarde', 'boa noite'])) {
            return $this->responder('Oi! Posso te ajudar com informações sobre estoque, validades, perdas e movimentações. O que você quer saber?');
        }

        if ($this->contem($pergunta, ['vence', 'vencendo', 'vencimento', 'validade', 'expirando'])) {
            return $this->vencimentos();
        }

        if ($this->contem($pergunta, ['critico', 'crítico', 'acabando', 'minimo', 'mínimo', 'baixo estoque', 'estoque baixo'])) {
            return $this->estoqueCritico();
        }

        if ($this->contem($pergunta, ['perda', 'perdas', 'perdi', 'perdemos'])) {
            return $this->perdas();
        }

        if ($this->contem($pergunta, ['movimenta', 'entrada', 'saida', 'saída', 'ultima', 'última'])) {
            return $this->movimentacoes();
        }

        if ($this->contem($pergunta, ['quantas', 'quantidade de', 'estoque de', 'tem ', 'temos '])) {
            return $this->buscarProduto($pergunta);
        }

        return $this->responder(
            "Não entendi bem. Você pode perguntar coisas como:\n" .
            "• \"o que vence essa semana?\"\n" .
            "• \"estoque crítico\"\n" .
            "• \"perdas do mês\"\n" .
            "• \"quantas luvas temos?\""
        );
    }

    private function contem(string $texto, array $palavras): bool
    {
        foreach ($palavras as $p) {
            if (str_contains($texto, $p)) return true;
        }
        return false;
    }

    private function responder(string $texto)
    {
        return response()->json(['resposta' => $texto]);
    }

    private function vencimentos()
    {
        $itens = DB::table('item_lote')
            ->whereNotNull('data_validade')
            ->where('data_validade', '<=', now()->addDays(7))
            ->orderBy('data_validade')
            ->select('nome', 'quantidade', 'unidade_medida', 'data_validade')
            ->limit(10)
            ->get();

        if ($itens->isEmpty()) {
            return $this->responder('Nenhum item vencendo nos próximos 7 dias. 👍');
        }

        $linhas = $itens->map(function ($item) {
            $data = \Carbon\Carbon::parse($item->data_validade)->format('d/m/Y');
            return "• {$item->nome} — {$item->quantidade} {$item->unidade_medida} (vence em {$data})";
        })->implode("\n");

        return $this->responder("Itens vencendo nos próximos 7 dias:\n\n{$linhas}");
    }

    private function estoqueCritico()
    {
        $itens = DB::table('item_lote')
            ->whereColumn('quantidade', '<=', 'estoque_minimo')
            ->select('nome', 'quantidade', 'estoque_minimo', 'unidade_medida')
            ->limit(10)
            ->get();

        if ($itens->isEmpty()) {
            return $this->responder('Nenhum item em estoque crítico no momento. 👍');
        }

        $linhas = $itens->map(function ($item) {
            return "• {$item->nome} — {$item->quantidade} {$item->unidade_medida} (mínimo: {$item->estoque_minimo})";
        })->implode("\n");

        return $this->responder("Itens com estoque crítico:\n\n{$linhas}");
    }

    private function perdas()
    {
        $perdas = DB::table('perda')
            ->join('lote', 'perda.id_lote', '=', 'lote.id_lote')
            ->join('produto', 'lote.id_produto', '=', 'produto.id_produto')
            ->where('perda.data_perda', '>=', now()->subDays(30))
            ->select('produto.nome', 'perda.quantidade', 'perda.razao', 'perda.data_perda')
            ->orderByDesc('perda.data_perda')
            ->limit(10)
            ->get();

        if ($perdas->isEmpty()) {
            return $this->responder('Nenhuma perda registrada nos últimos 30 dias.');
        }

        $totalUnidades = $perdas->sum('quantidade');

        $linhas = $perdas->map(function ($p) {
            $data = \Carbon\Carbon::parse($p->data_perda)->format('d/m/Y');
            return "• {$p->nome} — {$p->quantidade} un ({$p->razao}, {$data})";
        })->implode("\n");

        return $this->responder("Perdas nos últimos 30 dias ({$totalUnidades} unidades no total):\n\n{$linhas}");
    }

    private function movimentacoes()
    {
        $movs = DB::table('movimentacao')
            ->join('item_lote', 'movimentacao.id_item', '=', 'item_lote.id_item')
            ->select('item_lote.nome', 'movimentacao.tipo', 'movimentacao.quantidade', 'movimentacao.data_movimentacao')
            ->orderByDesc('movimentacao.data_movimentacao')
            ->limit(10)
            ->get();

        if ($movs->isEmpty()) {
            return $this->responder('Nenhuma movimentação registrada ainda.');
        }

        $linhas = $movs->map(function ($m) {
            $data = \Carbon\Carbon::parse($m->data_movimentacao)->format('d/m/Y H:i');
            return "• {$m->tipo} — {$m->nome} ({$m->quantidade} un, {$data})";
        })->implode("\n");

        return $this->responder("Últimas movimentações:\n\n{$linhas}");
    }

    private function buscarProduto(string $pergunta)
    {
        // Remove palavras de comando pra tentar isolar o nome do produto
        $termo = str_replace(
            ['quantas', 'quantidade de', 'estoque de', 'temos', 'tem', '?'],
            '', $pergunta
        );
        $termo = trim($termo);

        if (strlen($termo) < 2) {
            return $this->responder('Qual produto você quer consultar?');
        }

        $itens = DB::table('item_lote')
            ->where('nome', 'like', "%{$termo}%")
            ->select('nome', 'quantidade', 'unidade_medida', 'estoque_minimo')
            ->limit(10)
            ->get();

        if ($itens->isEmpty()) {
            return $this->responder("Não encontrei nenhum item com \"{$termo}\" no nome.");
        }

        $linhas = $itens->map(function ($item) {
            return "• {$item->nome} — {$item->quantidade} {$item->unidade_medida} (mínimo: {$item->estoque_minimo})";
        })->implode("\n");

        return $this->responder("Encontrei:\n\n{$linhas}");
    }
}