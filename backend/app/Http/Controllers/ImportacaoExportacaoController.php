<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;
use App\Models\Lote;
use App\Models\ItemLote;
use App\Models\Movimentacao;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
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

    // ─── TEMPLATE XLSX ────────────────────────────────────────
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();

        // ── Aba 1: Planilha ──────────────────────────────
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Planilha');

        $headers = ['CÓDIGO', 'DESCRIÇÃO', 'UNIDADE', 'SALDO', 'VALIDADE'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9E2F3');

        // Linha de exemplo (destacada em amarelo)
        $sheet->fromArray(
            ['ES0610000000001', 'ABAIXADOR DE MADEIRA PARA LÍNGUA', 'PCT', 6, '31/12/2024'],
            null,
            'A2'
        );
        $sheet->getStyle('A2:E2')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFF2CC');

        // Outro exemplo, sem código (SKU automático) e validade no formato mês/ano
        $sheet->fromArray(['', 'ACETONA 500ML', 'UN', 2, '06/2026'], null, 'A3');
        $sheet->getStyle('A3:E3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFF2CC');

        // Linha de agrupamento de exemplo (ignorada pelo sistema)
        $sheet->setCellValue('B4', 'LETRA A');
        $sheet->getStyle('B4')->getFont()->setItalic(true)->setBold(true);

        // Linhas em branco prontas pra preencher
        for ($row = 5; $row <= 33; $row++) {
            $sheet->setCellValueExplicit("D{$row}", '', DataType::TYPE_STRING);
        }

        foreach (['A' => 22, 'B' => 45, 'C' => 12, 'D' => 10, 'E' => 14] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->freezePane('A2');

        // ── Aba 2: Instruções ─────────────────────────────
        $instrucoes = $spreadsheet->createSheet();
        $instrucoes->setTitle('Instruções');
        $instrucoes->setCellValue('A1', 'Como preencher esta planilha');
        $instrucoes->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $texto = [
            '',
            'CÓDIGO: opcional. Se vazio, o sistema gera um SKU automático a partir da descrição.',
            'DESCRIÇÃO: obrigatório. Nome do produto.',
            'UNIDADE: opcional. Se vazio, assume "UN".',
            'SALDO: obrigatório. Quantidade em estoque. Linhas sem saldo são ignoradas.',
            'VALIDADE: opcional. Aceita datas como 31/12/2025, 2025-12-31, ou "12/2025" (assume dia 1º do mês).',
            '',
            'Linhas amarelas: exemplos de preenchimento correto — pode apagar antes de importar.',
            '',
            'Linhas de agrupamento (opcional): use uma linha só com "LETRA A", "LETRA B" etc. na coluna DESCRIÇÃO,',
            'deixando as demais colunas vazias, para separar visualmente grupos de produtos. O sistema ignora',
            'essas linhas automaticamente na importação — elas não geram produtos nem erros.',
            '',
            'Evite: linhas totalmente em branco no meio dos dados e alterar os nomes das colunas no cabeçalho.',
            '',
            'Se o CÓDIGO já existir no sistema, o estoque desse produto é incrementado (somado) em vez de duplicado.',
        ];
        foreach ($texto as $i => $linha) {
            $instrucoes->setCellValue('A' . ($i + 2), $linha);
        }
        $instrucoes->getColumnDimension('A')->setWidth(100);
        foreach ($instrucoes->getRowIterator() as $r) {
            $instrucoes->getStyle('A' . $r->getRowIndex())->getAlignment()->setWrapText(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'modelo_importacao_almoxarifado.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ─── PREVIEW: parseia Excel, cria/atualiza produtos, retorna itens (sem criar lote ainda) ───
    public function previewImportacao(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('arquivo')->getRealPath());
            $sheet       = $spreadsheet->getActiveSheet();
            $rows        = $sheet->toArray(null, true, true, false);

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

            $dataRows  = array_slice($rows, $headerIndex + 1);
            $itens     = [];
            $ignorados = 0;

            foreach ($dataRows as $row) {
                $descricao = isset($colMap['descricao']) ? trim((string)($row[$colMap['descricao']] ?? '')) : '';
                $saldo     = isset($colMap['saldo'])     ? $row[$colMap['saldo']]                           : null;

                if ($descricao === '' || is_null($saldo) || $saldo === '') {
                    $ignorados++;
                    continue;
                }
                if (preg_match('/^LETRA\s+[A-Z]$/i', $descricao)) {
                    $ignorados++;
                    continue;
                }

                $codigo       = isset($colMap['codigo'])   ? trim((string)($row[$colMap['codigo']]   ?? '')) : '';
                $unidade      = isset($colMap['unidade'])  ? trim((string)($row[$colMap['unidade']]  ?? 'UN')) : 'UN';
                $validade     = isset($colMap['validade']) ? $row[$colMap['validade']]                         : null;
                $quantidade   = (int) $saldo;
                $dataValidade = $this->converterValidade($validade);

                $sku = $codigo !== ''
                    ? $codigo
                    : 'GEN-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $descricao), 0, 12));

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
                }

                $itens[] = [
                    'produto_id' => $produto->id_produto,
                    'sku'        => $sku,
                    'nome'       => $descricao,
                    'unidade'    => $unidade ?: 'UN',
                    'quantidade' => $quantidade,
                    'validade'   => $dataValidade,
                ];
            }

            return response()->json([
                'itens'     => $itens,
                'ignorados' => $ignorados,
                'total'     => count($itens),
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao ler planilha: ' . $e->getMessage()], 500);
        }
    }

    // ─── CONFIRMAR: cria lote(s) + item_lote a partir da escolha do usuário ───
    public function confirmarImportacao(Request $request)
    {
        $request->validate([
            'modo'               => 'required|in:unico,multiplo',
            'lote.numero_lote'   => 'required_if:modo,unico|nullable|string',
            'lote.data_validade' => 'nullable|date',
            'itens'              => 'required_if:modo,unico|array',
            'itens.*.sku'        => 'required_with:itens|string',
            'itens.*.nome'       => 'required_with:itens|string',
            'itens.*.quantidade' => 'required_with:itens|integer|min:0',
            'itens.*.unidade'    => 'nullable|string',
            'itens.*.validade'   => 'nullable|date',
            'lotes'                       => 'required_if:modo,multiplo|array',
            'lotes.*.numero_lote'         => 'nullable|string',
            'lotes.*.data_validade'       => 'nullable|date',
            'lotes.*.itens'               => 'required_if:modo,multiplo|array',
            'lotes.*.itens.*.sku'         => 'required_with:lotes.*.itens|string',
            'lotes.*.itens.*.nome'        => 'required_with:lotes.*.itens|string',
            'lotes.*.itens.*.quantidade'  => 'required_with:lotes.*.itens|integer|min:0',
            'lotes.*.itens.*.unidade'     => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $lotesCriados = 0;

            if ($request->modo === 'unico') {
                $this->criarLoteComItens(
                    $request->input('lote.numero_lote') ?: ('LOTE-' . now()->format('Ymd') . '-' . rand(1000, 9999)),
                    $request->input('lote.data_validade'),
                    $request->itens
                );
                $lotesCriados = 1;
            } else {
                foreach ($request->lotes as $loteData) {
                    $this->criarLoteComItens(
                        $loteData['numero_lote'] ?: ('LOTE-' . now()->format('Ymd') . '-' . rand(1000, 9999)),
                        $loteData['data_validade'] ?? null,
                        $loteData['itens']
                    );
                    $lotesCriados++;
                }
            }

            DB::commit();
            return response()->json([
                'message'       => 'Importação concluída com sucesso!',
                'lotes_criados' => $lotesCriados,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro na importação: ' . $e->getMessage()], 500);
        }
    }

    // ─── Helper: cria um Lote e seus ItemLote, atualiza estoque e movimentação ───
    private function criarLoteComItens(string $numeroLote, ?string $dataValidade, array $itens): Lote
    {
        $lote = Lote::create([
            'numero_lote'    => $numeroLote,
            'quantidade'     => 0,
            'status'         => 'ativo',
            'data_entrada'   => now()->toDateString(),
            'data_validade'  => $dataValidade,
            'descricao'      => 'Importação em lote',
            'id_produto'     => null,
            'id_localizacao' => null,
        ]);

        foreach ($itens as $it) {
            if ((int) $it['quantidade'] <= 0) continue;

            $produto = Produto::where('sku', $it['sku'])->first();

            $item = ItemLote::where('id_lote', $lote->id_lote)
                ->where('sku', $it['sku'])
                ->first();

            if ($item) {
                $item->increment('quantidade', $it['quantidade']);
            } else {
                ItemLote::create([
                    'id_lote'           => $lote->id_lote,
                    'nome'              => $it['nome'],
                    'sku'               => $it['sku'],
                    'quantidade'        => $it['quantidade'],
                    'estoque_minimo'    => 0,
                    'unidade_medida'    => $it['unidade'] ?? 'UN',
                    'data_validade'     => $it['validade'] ?? $dataValidade,
                    'fornecedor'        => null,
                    'localizacao'       => null,
                    'prioridade_abc'    => 'C',
                    'prioridade_manual' => null,
                    'categoria'         => null,
                ]);
            }

            $lote->increment('quantidade', $it['quantidade']);
            if ($produto) {
                $produto->increment('estoque_atual', $it['quantidade']);
            }

            Movimentacao::create([
                'tipo'              => 'ENTRADA',
                'quantidade'        => $it['quantidade'],
                'data_movimentacao' => now()->toDateString(),
                'observacao'        => 'Importação via planilha Excel',
                'id_lote'           => $lote->id_lote,
                'id_item'           => null,
                'id_usuario'        => Auth::id(),
            ]);
        }

        return $lote;
    }

    // ─── Converte validade do Excel para Y-m-d ─────────────────
    private function converterValidade(mixed $valor): ?string
    {
        if (is_null($valor) || $valor === '' || $valor === 0) return null;

        if (is_numeric($valor) && $valor > 1000) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$valor)
                    ->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        if (preg_match('/F[:\s]*(\d{1,2})\/(\d{4})/i', (string)$valor, $m)) {
            return $m[2] . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-01';
        }

        if (preg_match('/^(\d{1,2})\/(\d{4})$/', trim((string)$valor), $m)) {
            return $m[2] . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-01';
        }

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
// ─── EXPORTAR PRODUTOS XLSX ───────────────────────────────
    public function exportarProdutosXLSX()
    {
        $produtos = Produto::with(['categoria', 'fornecedor'])->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Produtos');

        $headers = ['SKU', 'Nome', 'Categoria', 'Fornecedor', 'Unidade', 'Estoque Atual', 'Estoque Mínimo', 'Preço Custo', 'Prioridade ABC'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9E2F3');

        $row = 2;
        foreach ($produtos as $p) {
            $sheet->fromArray([
                $p->sku,
                $p->nome,
                $p->categoria->nome ?? '',
                $p->fornecedor->nome ?? '',
                $p->unidade_medida,
                $p->estoque_atual,
                $p->estoque_minimo,
                $p->preco_custo,
                $p->prioridade_abc,
            ], null, "A{$row}");
            $row++;
        }

        foreach (['A' => 20, 'B' => 40, 'C' => 18, 'D' => 18, 'E' => 10, 'F' => 14, 'G' => 14, 'H' => 14, 'I' => 14] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->freezePane('A2');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'produtos_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
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
// ─── EXPORTAR MOVIMENTAÇÕES XLSX ──────────────────────────
public function exportarMovimentacoesXLSX()
{
    $movs = Movimentacao::with(['lote.produto'])->orderBy('data_movimentacao', 'desc')->get();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Movimentações');

    $headers = ['Data', 'Tipo', 'Produto', 'SKU', 'Quantidade', 'Observação'];
    $sheet->fromArray($headers, null, 'A1');
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    $sheet->getStyle('A1:F1')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB('D9E2F3');

    $row = 2;
    foreach ($movs as $m) {
        $produto = $m->lote->produto ?? null;
        $sheet->fromArray([
            $m->data_movimentacao ?? '',
            $m->tipo,
            $produto->nome ?? '',
            $produto->sku  ?? '',
            $m->quantidade,
            $m->observacao ?? '',
        ], null, "A{$row}");
        $row++;
    }

    foreach (['A' => 14, 'B' => 12, 'C' => 32, 'D' => 20, 'E' => 12, 'F' => 40] as $col => $width) {
        $sheet->getColumnDimension($col)->setWidth($width);
    }
    $sheet->freezePane('A2');

    $writer = new Xlsx($spreadsheet);

    return response()->streamDownload(function () use ($writer) {
        $writer->save('php://output');
    }, 'movimentacoes_' . now()->format('Ymd') . '.xlsx', [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
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