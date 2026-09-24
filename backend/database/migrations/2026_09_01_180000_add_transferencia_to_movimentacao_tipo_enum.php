<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE movimentacao MODIFY tipo ENUM('ENTRADA', 'SAIDA', 'PERDA', 'AJUSTE', 'TRANSFERENCIA') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE movimentacao MODIFY tipo ENUM('ENTRADA', 'SAIDA', 'PERDA', 'AJUSTE') NOT NULL");
    }
};