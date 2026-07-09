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

        $pergunta = $this->normalizar($request->input('mensagem'));

        // ---- Intenção: agradecimento / despedida ----
        if ($this->contem($pergunta, [
            'obrigado', 'obrigada', 'valeu', 'vlw', 'brigado', 'brigada', 'thanks',
            'tchau', 'ate mais', 'ate logo', 'falou', 'flw',
        ])) {
            return $this->responder('De nada! Qualquer coisa é só chamar. 😊');
        }

        // ---- Intenção: saudação / small talk ----
        if ($this->contem($pergunta, [
            'oi', 'ola', 'bom dia', 'boa tarde', 'boa noite', 'eae', 'e ai',
            'tudo bem', 'blz', 'beleza', 'salve', 'opa', 'fala',
            'como vc esta', 'como voce esta', 'como vc ta', 'como voce ta',
            'tudo certo', 'tudo joia', 'tudo tranquilo', 'suave',
            'quem e vc', 'quem e voce', 'o que vc faz', 'o que voce faz',
            'o que vc e', 'o que voce e', 'pra que vc serve', 'para que voce serve',
            'me ajuda', 'pode me ajudar', 'preciso de ajuda', 'socorro',
            'o que vc sabe fazer', 'quais comandos', 'como funciona',
        ])) {
            return $this->responder('Oi! Posso te ajudar com informações sobre estoque, validades, perdas e movimentações. O que você quer saber?');
        }

        // ---- Intenção: vencimentos / validade ----
        if ($this->contem($pergunta, [
            'vence', 'vencendo', 'vencimento', 'vencimentos', 'validade', 'validades',
            'expirando', 'expira', 'expiram', 'prazo', 'prazos',
            'perto de vencer', 'proximo do vencimento', 'proximos do vencimento',
            'proximo de vencer', 'proximos de vencer', 'venc', 'data de validade',
            'o que ta vencendo', 'o que esta vencendo', 'algo vencendo',
            'tem algo vencendo', 'vai vencer', 'vao vencer',
        ])) {
            return $this->vencimentos();
        }

        // ---- Intenção: estoque crítico / baixo ----
        if ($this->contem($pergunta, [
            'critico', 'criticos', 'critica', 'criticas', 'acabando', 'acabou',
            'minimo', 'minimos', 'baixo estoque', 'estoque baixo', 'estoque minimo',
            'faltando', 'em falta', 'preciso repor', 'precisa repor', 'repor estoque',
            'reposicao', 'zerado', 'zerados', 'zerando', 'no vermelho',
            'o que esta faltando', 'o que ta faltando', 'quase acabando',
            'quase zerado', 'precisa comprar', 'tem que comprar',
        ])) {
            return $this->estoqueCritico();
        }

        // ---- Intenção: perdas ----
        if ($this->contem($pergunta, [
            'perda', 'perdas', 'perdi', 'perdemos', 'descarte', 'descartado',
            'descartados', 'jogado fora', 'jogamos fora', 'desperdicio',
            'desperdicios', 'quebra', 'quebras', 'quebrado', 'estragou',
            'estragado', 'vencidos que jogamos', 'o que foi perdido',
            'o que perdemos', 'prejuizo',
        ])) {
            return $this->perdas();
        }

        // ---- Intenção: movimentações / histórico ----
        if ($this->contem($pergunta, [
            'movimenta', 'movimentacao', 'movimentacoes', 'entrada', 'entradas',
            'saida', 'saidas', 'ultima', 'ultimas', 'ultimo', 'ultimos',
            'historico', 'log de estoque', 'o que mudou', 'o que aconteceu',
            'o que rolou', 'novidades', 'atualizacoes recentes',
            'quem mexeu', 'quem alterou', 'quem tirou', 'quem colocou',
        ])) {
            return $this->movimentacoes();
        }

        // ---- Intenção: quantidade/localização de produto específico ----
        if (preg_match(
            '/\b(quant[ao]s?|quantidade|estoque de|tem |tenho |temos|possui|possuimos|existe|existem|ha |disponivel|disponiveis|sobrou|sobrando|onde esta|onde estao|onde fica|localiza[cç][aã]o de|cade|achar|encontrar|procurando|procuro)\b/u',
            $pergunta
        )) {
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

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = str_replace(
            ['á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç'],
            ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c'],
            $texto
        );
        $texto = preg_replace('/\s+/', ' ', $texto);
        return trim($texto);
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
        $palavrasParaRemover = [
            'quantas?', 'quantidade de', 'quantidade', 'estoque de', 'estoque',
            'tenho', 'temos', 'tem', 'possuimos', 'possui', 'existe', 'existem', 'ha',
            'disponivel', 'disponiveis', 'sobrou', 'sobrando',
            'onde esta', 'onde estao', 'onde fica', 'cade',
            'localizacao de', 'localizacao', 'achar', 'encontrar',
            'procurando', 'procuro',
            'de', 'do', 'da', 'no', 'na', 'o', 'a', 'os', 'as',
        ];

        $padrao = '/\b(' . implode('|', $palavrasParaRemover) . ')\b/u';
        $termo = preg_replace($padrao, '', $pergunta);
        $termo = str_replace('?', '', $termo);
        $termo = preg_replace('/\s+/', ' ', $termo);
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