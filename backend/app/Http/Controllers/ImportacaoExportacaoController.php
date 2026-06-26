<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;
use App\Models\Lote;
use App\Models\ItemLote;
use App\Models\Movimentacao;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportacaoExportacaoController extends Controller
{
    // ─── STATS ────────────────────────────────────────────────
    public function stats()
    {
        return response()->json([
            'lotes'          => Lote::count(),
            'produtos'       => Produto::count(),
            'movimentacoes'  => Movimentacao::count(),
            'itens_estoque'  => ItemLote::where('quantidade', '>', 0)->count(),
        ]);
    }

    // ─── EXPORTAR ─────────────────────────────────────────────
    public function exportarBackup()
    {
        $data = [
            'exportado_em' => now()->toISOString(),
            'produtos'     => Produto::all(),
            'lotes'        => Lote::all(),
            'item_lotes'   => ItemLote::all(),
            'movimentacoes'=> Movimentacao::all(),
        ];

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="backup_sige_' . now()->format('Ymd_His') . '.json"');
    }

    public function exportarProdutosCSV()
    {
        $produtos = Produto::with('itemLotes')->get();

        $csv = "SKU,Nome,Descricao,Categoria,Unidade,Estoque Total\n";
        foreach ($produtos as $p) {
            $estoque = $p->itemLotes->sum('quantidade');
            $csv .= "\"{$p->sku}\",\"{$p->nome}\",\"{$p->descricao}\",\"{$p->categoria}\",\"{$p->unidade_medida}\",{$estoque}\n";
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="produtos_' . now()->format('Ymd') . '.csv"');
    }

    public function exportarMovimentacoesCSV()
    {
        $movs = Movimentacao::with('produto')->orderBy('created_at', 'desc')->get();

        $csv = "Data,Tipo,Produto,Quantidade,Observacao\n";
        foreach ($movs as $m) {
            $csv .= "\"{$m->created_at->format('d/m/Y H:i')}\",\"{$m->tipo}\",\"{$m->produto->nome}\",{$m->quantidade},\"{$m->observacao}\"\n";
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="movimentacoes_' . now()->format('Ymd') . '.csv"');
    }

    // ─── TEMPLATE CSV ─────────────────────────────────────────
    public function downloadTemplate()
    {
        $csv = "SKU,Nome,Descricao,Categoria,Unidade,Quantidade,Data_Validade,Preco_Custo,Fornecedor\n";
        $csv .= "PROD001,\"Produto Exemplo\",\"Descricao do produto\",\"Categoria A\",UN,100,2026-12-31,15.50,\"Fornecedor X\"\n";

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="template_importacao.csv"');
    }

    // ─── IMPORTAR CSV ─────────────────────────────────────────
    public function importarProdutosCSV(Request $request)
    {
        $request->validate(['arquivo' => 'required|file|mimes:csv,txt']);

        $file = $request->file('arquivo');

        $csv = Reader::createFromPath($file->getPathname(), 'r');
        $csv->setHeaderOffset(0);

        $registros = collect(iterator_to_array($csv->getRecords()));

        if ($registros->isEmpty()) {
            return response()->json(['message' => 'Arquivo CSV vazio.'], 422);
        }

        DB::beginTransaction();
        try {
            $criados    = 0;
            $atualizados = 0;
            $lotesCriados = 0;

            // Agrupa por SKU + Data_Validade → cada grupo = 1 lote
            $grupos = $registros->groupBy(fn($r) => trim($r['SKU']) . '||' . trim($r['Data_Validade']));

            foreach ($grupos as $chave => $itens) {
                [$sku, $dataValidade] = explode('||', $chave);
                $primeiroItem = $itens->first();

                // Cria ou atualiza produto
                $produto = Produto::firstOrCreate(
                    ['sku' => $sku],
                    [
                        'nome'           => $primeiroItem['Nome'],
                        'descricao'      => $primeiroItem['Descricao'] ?? null,
                        'categoria'      => $primeiroItem['Categoria'] ?? null,
                        'unidade_medida' => $primeiroItem['Unidade'] ?? 'UN',
                        'preco_custo'    => $primeiroItem['Preco_Custo'] ?? 0,
                        'ativo'          => true,
                    ]
                );

                $produto->wasRecentlyCreated ? $criados++ : $atualizados++;

                // Quantidade total do grupo
                $qtdTotal = $itens->sum(fn($r) => (int) $r['Quantidade']);

                // Cria lote único para este SKU + data validade
                $lote = Lote::create([
                    'produto_id'        => $produto->id,
                    'data_validade'     => $dataValidade ? Carbon::parse($dataValidade) : null,
                    'fornecedor'        => $primeiroItem['Fornecedor'] ?? null,
                    'quantidade_inicial'=> $qtdTotal,
                    'status'            => 'ativo',
                ]);
                $lotesCriados++;

                // Cria item_lote
                ItemLote::create([
                    'lote_id'    => $lote->id,
                    'produto_id' => $produto->id,
                    'quantidade' => $qtdTotal,
                    'status'     => 'disponivel',
                ]);

                // Registra movimentação de ENTRADA
                Movimentacao::create([
                    'produto_id'  => $produto->id,
                    'lote_id'     => $lote->id,
                    'tipo'        => 'ENTRADA',
                    'quantidade'  => $qtdTotal,
                    'observacao'  => 'Importação via CSV',
                ]);
            }

            DB::commit();

            return response()->json([
                'message'       => 'Importação concluída com sucesso!',
                'produtos_novos'=> $criados,
                'produtos_atualizados' => $atualizados,
                'lotes_criados' => $lotesCriados,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro na importação: ' . $e->getMessage()], 500);
        }
    }

    // ─── RESTAURAR BACKUP ─────────────────────────────────────
    public function restaurarBackup(Request $request)
    {
        $request->validate(['arquivo' => 'required|file|mimes:json,txt']);

        $conteudo = json_decode(file_get_contents($request->file('arquivo')->getPathname()), true);

        if (!$conteudo) {
            return response()->json(['message' => 'JSON inválido.'], 422);
        }

        DB::beginTransaction();
        try {
            // Limpa e restaura na ordem certa (respeita FK)
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Movimentacao::truncate();
            ItemLote::truncate();
            Lote::truncate();
            Produto::truncate();

            foreach ($conteudo['produtos'] ?? [] as $p) {
                Produto::insert($p);
            }
            foreach ($conteudo['lotes'] ?? [] as $l) {
                Lote::insert($l);
            }
            foreach ($conteudo['item_lotes'] ?? [] as $i) {
                ItemLote::insert($i);
            }
            foreach ($conteudo['movimentacoes'] ?? [] as $m) {
                Movimentacao::insert($m);
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();

            return response()->json(['message' => 'Backup restaurado com sucesso!']);
        } catch (\Exception $e) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return response()->json(['message' => 'Erro ao restaurar: ' . $e->getMessage()], 500);
        }
    }
}