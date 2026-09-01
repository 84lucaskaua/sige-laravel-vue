<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Adiciona id_produto em item_lote (nullable por enquanto, pra dar tempo do backfill)
        Schema::table('item_lote', function (Blueprint $table) {
            $table->unsignedBigInteger('id_produto')->nullable()->after('id_lote');
        });

        // 2. BACKFILL: agrupa item_lote existentes por (nome, sku) e cria/associa um Produto pra cada grupo
        $grupos = DB::table('item_lote')
            ->select('nome', 'sku', DB::raw('MIN(categoria) as categoria'), DB::raw('MIN(fornecedor) as fornecedor'),
                     DB::raw('MIN(unidade_medida) as unidade_medida'), DB::raw('MIN(estoque_minimo) as estoque_minimo'))
            ->groupBy('nome', 'sku')
            ->get();

        foreach ($grupos as $grupo) {
            // Tenta achar produto já existente com esse sku, senão cria
            $idProduto = DB::table('produto')->where('sku', $grupo->sku)->value('id_produto');

            if (!$idProduto) {
                $idProduto = DB::table('produto')->insertGetId([
                    'nome'           => $grupo->nome,
                    'sku'            => $grupo->sku,
                    'unidade_medida' => $grupo->unidade_medida ?? 'UN',
                    'estoque_minimo' => $grupo->estoque_minimo ?? 0,
                    'estoque_atual'  => 0, // recalculado no passo 3
                    'id_categoria'   => null, // ajuste manual depois se 'categoria' era texto livre em item_lote
                    'id_fornecedor'  => null, // idem
                ], 'id_produto');
            }

            DB::table('item_lote')
                ->where('nome', $grupo->nome)
                ->where('sku', $grupo->sku)
                ->update(['id_produto' => $idProduto]);
        }

        // 3. Recalcula estoque_atual de cada produto somando as quantidades dos lotes
        DB::statement('
            UPDATE produto p
            SET estoque_atual = (
                SELECT COALESCE(SUM(il.quantidade), 0)
                FROM item_lote il
                WHERE il.id_produto = p.id_produto
            )
        ');

        // 4. Agora que todo mundo tem id_produto, torna a coluna obrigatória e cria a FK
        Schema::table('item_lote', function (Blueprint $table) {
            $table->unsignedBigInteger('id_produto')->nullable(false)->change();
            $table->foreign('id_produto')->references('id_produto')->on('produto')->cascadeOnDelete();
        });

        // 5. NÃO remova ainda nome/sku/categoria/fornecedor de item_lote nesta migration.
        //    Faça isso numa migration separada DEPOIS de atualizar o frontend e confirmar
        //    que tudo está lendo de produto->nome, produto->sku etc.
        //    Quando for a hora, rode:
        //    Schema::table('item_lote', function (Blueprint $table) {
        //        $table->dropColumn(['nome', 'sku', 'categoria', 'fornecedor', 'estoque_minimo']);
        //    });
    }

    public function down(): void
    {
        Schema::table('item_lote', function (Blueprint $table) {
            $table->dropForeign(['id_produto']);
            $table->dropColumn('id_produto');
        });
    }
};