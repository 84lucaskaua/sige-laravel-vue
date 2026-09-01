<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;
use App\Models\Lote;
use App\Models\ItemLote;
use App\Models\Movimentacao;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Carbon\Carbon;

class ImportacaoController extends Controller
{
    public function stats()
    {
        return response()->json([
            'lotes'         => Lote::count(),
            'produtos'      => Produto::count(),
            'movimentacoes' => Movimentacao::count(),
            'itens_estoque' => ItemLote::where('quantidade', '>', 0)->count(),
        ]);
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Planilha');

        $headers = ['CÓDIGO', 'DESCRIÇÃO', 'UNIDADE', 'SALDO', 'VALIDADE'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9E2F3');

        $sheet->fromArray(
            ['ES0610000000001', 'ABAIXADOR DE MADEIRA PARA LÍNGUA', 'PCT', 6, '31/12/2024'],
            null,
            'A2'
        );
        $sheet->getStyle('A2:E2')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFF2CC');

        $sheet->fromArray(['', 'ACETONA 500ML', 'UN', 2, '06/2026'], null, 'A3');
        $sheet->getStyle('A3:E3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFF2CC');

        $sheet->setCellValue('B4', 'LETRA A');
        $sheet->getStyle('B4')->getFont()->setItalic(true)->setBold(true);

        for ($row = 5; $row <= 33; $row++) {
            $sheet->setCellValueExplicit("D{$row}", '', DataType::TYPE_STRING);
        }

        foreach (['A' => 22, 'B' => 45, 'C' => 12, 'D' => 10, 'E' => 14] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->freezePane('A2');

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

                // Apenas consulta — nunca cria o produto no preview
                $produtoExistente = Produto::where('sku', $sku)->first();

                $itens[] = [
                    'produto_id'        => $produtoExistente->id_produto ?? null, // null = produto novo
                    'sku'               => $sku,
                    'nome'              => $descricao,
                    'unidade'           => $unidade ?: 'UN',
                    'quantidade'        => $quantidade,
                    'validade'          => $dataValidade,
                    'produto_existente' => (bool) $produtoExistente, // útil pro frontend avisar na tabela
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

            // Resolve o produto AGORA (na confirmação), de forma atômica — não no preview
            $produto = Produto::firstOrCreate(
                ['sku' => $it['sku']],
                [
                    'nome'           => $it['nome'],
                    'unidade_medida' => $it['unidade'] ?? 'UN',
                    'preco_custo'    => 0,
                    'estoque_minimo' => 0,
                    'estoque_atual'  => 0,
                    'prioridade_abc' => 'C',
                    'id_categoria'   => null,
                    'id_fornecedor'  => null,
                ]
            );

            $validadeItem = $it['validade'] ?? $dataValidade;

            // Agrupa por PRODUTO + VALIDADE, não por SKU sozinho — preserva lotes com validades diferentes
            $item = ItemLote::where('id_lote', $lote->id_lote)
                ->where('id_produto', $produto->id_produto)
                ->where('data_validade', $validadeItem)
                ->first();

            if ($item) {
                $item->increment('quantidade', $it['quantidade']);
            } else {
                $item = ItemLote::create([
                    'id_lote'           => $lote->id_lote,
                    'id_produto'        => $produto->id_produto,
                    'quantidade'        => $it['quantidade'],
                    'unidade_medida'    => $it['unidade'] ?? 'UN',
                    'data_validade'     => $validadeItem,
                    'localizacao'       => null,
                    'prioridade_abc'    => null,
                    'prioridade_manual' => false,
                ]);
            }

            $lote->increment('quantidade', $it['quantidade']);
            $produto->increment('estoque_atual', $it['quantidade']);

            Movimentacao::registrar('ENTRADA', $it['quantidade'], $lote->id_lote, $item->id_item ?? null, 'Importação via planilha Excel');
        }

        return $lote;
    }

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
}