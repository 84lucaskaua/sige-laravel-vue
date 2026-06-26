<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE produto MODIFY id_categoria int(11) NULL');
        DB::statement('ALTER TABLE produto MODIFY id_fornecedor int(11) NULL');

        DB::statement('ALTER TABLE produto ADD CONSTRAINT fk_produto_categoria FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria) ON DELETE SET NULL');
        DB::statement('ALTER TABLE produto ADD CONSTRAINT fk_produto_fornecedor FOREIGN KEY (id_fornecedor) REFERENCES fornecedor(id_fornecedor) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE produto DROP FOREIGN KEY fk_produto_categoria');
        DB::statement('ALTER TABLE produto DROP FOREIGN KEY fk_produto_fornecedor');

        DB::statement('ALTER TABLE produto MODIFY id_categoria bigint(20) unsigned NULL');
        DB::statement('ALTER TABLE produto MODIFY id_fornecedor bigint(20) unsigned NULL');
    }
};