<?php
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: nullable e foreign keys já definidos em create_produto_table.
    }

    public function down(): void
    {
        // No-op
    }
};