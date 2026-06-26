<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;
use App\Models\Lote;
use App\Models\ItemLote;
use App\Models\Movimentacao;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class ImportacaoExportacaoController extends Controller
{
    // ─── STATS ────────────────────────────────────────────────
    public function stats()
    {
        return response()->json([
            'lotes'         => Lote::count(),
            'produtos'      => Produto::count(),
            'movimentacoes' => Movimentacao::count(),
            'itens_estoque' => ItemLote::where('quantidade', '>', 0)->count(),
        ]);
    }

    // ─── TEMPLATE CSV ─────────────────────────────────────────
    public function downloadTemplate()
    {
        $csv  = "CODIGO,DESCRICAO,UNIDADE,SALDO,VALIDADE\n";
        $csv .= "ES0610000000001,\"ABAIXADOR DE MADEIRA PARA LÍNGUA\",PCT,6,2024-12-31\n";
        $csv .= ",\"ACETONA 500ML\",UN,2,2026-06-15\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_importacao.csv"',
        ]);
    }

    // ─── IMPORTAR EXCEL (.xlsx) ───────────────────────────────
    public function importarExcel(Request $request)
{
    $request->validate([
        'arquivo' => 'required|file',
    ]);

        DB::beginTransaction();
        try {
            $spreadsheet = IOFactory::load($request->file('arquivo')->getRealPath());
            $sheet       = $spreadsheet->getActiveSheet();
            $rows        = $sheet->toArray(null, true, true, false);

            // Encontra a linha de cabeçalho (contém "DESCRIÇÃO" ou "DESCRICAO")
            $headerIndex = null;
            foreach ($rows as $i => $row) {
                $joined = implode(' ', array_map('mb_strtoupper', array_filter($row, 'is_string')));
                if (str_contains($joined, 'DESCRI')) {
                    $headerIndex = $i;
                    break;
                }
            }

            if ($headerIndex === null) {
                return response()->json(['message' => 'Cabeçalho não encontrado na planilha.'], 422);
            }

            // Mapeia colunas pelo cabeçalho
            $header = array_map(fn($v) => mb_strtoupper(trim((string)$v)), $rows[$headerIndex]);
            $colMap = [];
            foreach ($header as $idx => $col) {
                if (str_contains($col, 'CODIGO') || str_contains($col, 'CÓDIGO')) $colMap['codigo']    = $idx;
                if (str_contains($col, 'DESCRI'))                                  $colMap['descricao'] = $idx;
                if (str_contains($col, 'UNIDADE'))                                 $colMap['unidade']   = $idx;
                if (str_contains($col, 'SALDO'))                                   $colMap['saldo']     = $idx;
                if (str_contains($col, 'VALIDADE'))                                $colMap['validade']  = $idx;
            }

            if (!isset($colMap['descricao'], $colMap['saldo'])) {
                return response()->json(['message' => 'Colunas obrigatórias (DESCRIÇÃO, SALDO) não encontradas.'], 422);
            }

            $produtosNovos = 0;
            $lotesCriados  = 0;
            $ignorados     = 0;

            // Linhas de dados começam após o cabeçalho
            $dataRows = array_slice($rows, $headerIndex + 1);

            foreach ($dataRows as $row) {
                $descricao = isset($colMap['descricao']) ? trim((string)($row[$colMap['descricao']] ?? '')) : '';
                $saldo     = isset($colMap['saldo'])     ? $row[$colMap['saldo']]                           : null;

                // Ignora linhas vazias ou de agrupamento ("LETRA A", "LETRA B"...)
                if ($descricao === '' || is_null($saldo) || $saldo === '') {
                    $ignorados++;
                    continue;
                }
                if (preg_match('/^LETRA\s+[A-Z]$/i', $descricao)) {
                    $ignorados++;
                    continue;
                }

                $codigo    = isset($colMap['codigo'])   ? trim((string)($row[$colMap['codigo']]   ?? '')) : '';
                $unidade   = isset($colMap['unidade'])  ? trim((string)($row[$colMap['unidade']]  ?? 'UN')) : 'UN';
                $validade  = isset($colMap['validade']) ? $row[$colMap['validade']]                         : null;
                $quantidade = (int) $saldo;

                // Converte validade
                $dataValidade = $this->converterValidade($validade);

                // SKU: usa código se existir, senão gera pelo nome
                $sku = $codigo !== ''
                    ? $codigo
                    : 'GEN-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $descricao), 0, 12));

                // Busca ou cria produto
                $produto = Produto::where('sku', $sku)->first();
                if (!$produto) {
                    $produto = Produto::create([
                        'nome'           => $descricao,
                        'sku'            => $sku,
                        'unidade_medida' => $unidade ?: 'UN',
                        'preco_custo'    => 0,
                        'estoque_minimo' => 0,
                        'estoque_atual'  => 0,
                        'prioridade_abc' => 'C',
                        'id_categoria'   => null,
                        'id_fornecedor'  => null,
                    ]);
                    $produtosNovos++;
                }

                // Mesmo SKU + mesma data_validade → incrementa lote existente
                $loteExistente = Lote::where('id_produto', $produto->id_produto)
                    ->where('data_validade', $dataValidade)
                    ->first();

                if ($loteExistente) {
                    $loteExistente->increment('quantidade', $quantidade);
                    $produto->increment('estoque_atual', $quantidade);

                    $item = ItemLote::where('id_lote', $loteExistente->id_lote)->first();
                    if ($item) $item->increment('quantidade', $quantidade);
                } else {
                    // Novo lote
                    $numeroLote = 'LOTE-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $sku), 0, 10))
                        . '-' . now()->format('YmdHis')
                        . '-' . rand(100, 999);

                    $lote = Lote::create([
                        'numero_lote'    => $numeroLote,
                        'quantidade'     => $quantidade,
                        'status'         => 'ativo',
                        'data_entrada'   => now()->toDateString(),
                        'data_validade'  => $dataValidade,
                        'descricao'      => $descricao,
                        'id_produto'     => $produto->id_produto,
                        'id_localizacao' => null,
                    ]);

                    ItemLote::create([
                        'id_lote'           => $lote->id_lote,
                        'nome'              => $descricao,
                        'sku'               => $sku,
                        'quantidade'        => $quantidade,
                        'estoque_minimo'    => 0,
                        'unidade_medida'    => $unidade ?: 'UN',
                        'data_validade'     => $dataValidade,
                        'fornecedor'        => null,
                        'localizacao'       => null,
                        'prioridade_abc'    => 'C',
                        'prioridade_manual' => null,
                        'categoria'         => null,
                    ]);

                    Movimentacao::create([
                        'tipo'              => 'ENTRADA',
                        'quantidade'        => $quantidade,
                        'data_movimentacao' => now()->toDateString(),
                        'observacao'        => 'Importação via planilha Excel',
                        'id_lote'           => $lote->id_lote,
                        'id_item'           => null,
                        'id_usuario'        => auth()->id(),
                    ]);

                    $produto->increment('estoque_atual', $quantidade);
                    $lotesCriados++;
                }
            }

            DB::commit();

            return response()->json([
                'message'        => 'Importação concluída com sucesso!',
                'produtos_novos' => $produtosNovos,
                'lotes_criados'  => $lotesCriados,
                'ignorados'      => $ignorados,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro na importação: ' . $e->getMessage()], 500);
        }
    }

    // ─── Converte validade do Excel para Y-m-d ─────────────────
    private function converterValidade($valor): ?string
    {
        if (is_null($valor) || $valor === '' || $valor === 0) return null;

        // Número serial do Excel (ex: 45474)
        if (is_numeric($valor) && $valor > 1000) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$valor)
                    ->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // Texto tipo "F:12/2025" ou "F:06/2025"
        if (preg_match('/F[:\s]*(\d{1,2})\/(\d{4})/i', (string)$valor, $m)) {
            return $m[2] . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-01';
        }

        // Texto tipo "12/2025"
        if (preg_match('/^(\d{1,2})\/(\d{4})$/', trim((string)$valor), $m)) {
            return $m[2] . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-01';
        }

        // Já no formato Y-m-d
        try {
            return Carbon::parse((string)$valor)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    // ─── EXPORTAR BACKUP ──────────────────────────────────────
    public function exportarBackup()
    {
        $dados = [
            'exportado_em'  => now()->toISOString(),
            'produtos'      => Produto::all(),
            'lotes'         => Lote::all(),
            'item_lotes'    => ItemLote::all(),
            'movimentacoes' => Movimentacao::all(),
        ];

        return response()->json($dados, 200, [
            'Content-Disposition' => 'attachment; filename="backup_sige_' . now()->format('Ymd_His') . '.json"',
        ]);
    }

    // ─── EXPORTAR PRODUTOS CSV ────────────────────────────────
    public function exportarProdutosCSV()
    {
        $produtos = Produto::with(['categoria', 'fornecedor'])->get();

        $csv = "SKU,Nome,Categoria,Fornecedor,Unidade,Estoque_Atual,Estoque_Minimo,Preco_Custo,Prioridade_ABC\n";
        foreach ($produtos as $p) {
            $csv .= implode(',', [
                "\"{$p->sku}\"",
                '"' . str_replace('"', '""', $p->nome) . '"',
                '"' . ($p->categoria->nome ?? '') . '"',
                '"' . ($p->fornecedor->nome ?? '') . '"',
                $p->unidade_medida,
                $p->estoque_atual,
                $p->estoque_minimo,
                $p->preco_custo,
                $p->prioridade_abc,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="produtos_' . now()->format('Ymd') . '.csv"',
        ]);
    }

    // ─── EXPORTAR MOVIMENTAÇÕES CSV ───────────────────────────
    public function exportarMovimentacoesCSV()
    {
        $movs = Movimentacao::with(['lote.produto'])->orderBy('data_movimentacao', 'desc')->get();

        $csv = "Data,Tipo,Produto,SKU,Quantidade,Observacao\n";
        foreach ($movs as $m) {
            $produto = $m->lote->produto ?? null;
            $csv .= implode(',', [
                '"' . ($m->data_movimentacao ?? '') . '"',
                '"' . $m->tipo . '"',
                '"' . ($produto->nome ?? '') . '"',
                '"' . ($produto->sku  ?? '') . '"',
                $m->quantidade,
                '"' . str_replace('"', '""', $m->observacao ?? '') . '"',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="movimentacoes_' . now()->format('Ymd') . '.csv"',
        ]);
    }

    // ─── RESTAURAR BACKUP ─────────────────────────────────────
    public function restaurarBackup(Request $request)
    {
        $request->validate(['arquivo' => 'required|file|mimes:json,txt']);

        $conteudo = json_decode(
            file_get_contents($request->file('arquivo')->getRealPath()),
            true
        );

        if (!$conteudo) {
            return response()->json(['message' => 'JSON inválido.'], 422);
        }

        DB::beginTransaction();
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Movimentacao::truncate();
            ItemLote::truncate();
            Lote::truncate();
            Produto::truncate();

            foreach ($conteudo['produtos']      ?? [] as $p) Produto::insert($p);
            foreach ($conteudo['lotes']         ?? [] as $l) Lote::insert($l);
            foreach ($conteudo['item_lotes']    ?? [] as $i) ItemLote::insert($i);
            foreach ($conteudo['movimentacoes'] ?? [] as $m) Movimentacao::insert($m);

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