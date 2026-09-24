<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Antes de aplicar o UNIQUE, garante que não existe SKU duplicado sobrando
        $duplicados = DB::table('produto')
            ->select('sku')
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->groupBy('sku')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('sku');

        if ($duplicados->isNotEmpty()) {
            // Se ainda houver duplicata, a migration para aqui — rode antes
            // a migration de limpeza (cleanup_produto_duplicates) que já fizemos.
            throw new \RuntimeException(
                'Ainda existem SKUs duplicados em produto: ' . $duplicados->implode(', ') .
                '. Rode a migration de limpeza antes desta.'
            );
        }

        Schema::table('produto', function (Blueprint $table) {
            $table->unique('sku');
        });
    }

    public function down(): void
    {
        Schema::table('produto', function (Blueprint $table) {
            $table->dropUnique(['sku']);
        });
    }
};