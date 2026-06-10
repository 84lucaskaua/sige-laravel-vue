<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Criar todas as tabelas do SIGE
 *
 * Ordem de criação importa por causa das chaves estrangeiras:
 * 1. usuarios
 * 2. categorias
 * 3. produtos
 * 4. lotes
 * 5. itens_lote
 * 6. movimentos
 * 7. logs_auditoria
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- Tabela de usuários ----
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('senha');                          // Sempre criptografada
            $table->enum('perfil', ['admin', 'operador', 'visualizador'])->default('visualizador');
            $table->string('foto_url')->nullable();
            $table->boolean('ativo')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        // ---- Tabela de categorias ----
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('descricao')->nullable();
            $table->string('cor', 7)->default('#6B7280');   // Cor em hex
            $table->timestamps();
        });

        // ---- Tabela de produtos ----
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->enum('unidade', ['UN', 'CX', 'PCT', 'KG', 'G', 'L', 'ML', 'FR', 'RL', 'KIT', 'EMB']);
            $table->unsignedInteger('estoque_minimo')->default(0);
            $table->string('foto_url')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // ---- Tabela de lotes ----
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();               // Ex: LOT-2024-001
            $table->string('descricao')->nullable();
            $table->date('data_entrada');
            $table->timestamps();
        });

        // ---- Tabela de itens dentro de um lote ----
        Schema::create('itens_lote', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->constrained('lotes')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos');
            $table->unsignedInteger('quantidade')->default(0);
            $table->unsignedInteger('estoque_minimo')->default(0);
            $table->date('validade')->nullable();
            $table->string('fornecedor')->nullable();
            $table->string('localizacao')->nullable();        // Ex: "Prateleira A3"
            $table->unsignedInteger('preco_unitario')->default(0); // Em centavos
            $table->enum('prioridade', ['A', 'B', 'C'])->nullable();
            $table->timestamps();
        });

        // ---- Tabela de movimentos (entradas e saídas) ----
        Schema::create('movimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_lote_id')->constrained('itens_lote');
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->enum('tipo', ['entrada', 'saida']);
            $table->unsignedInteger('quantidade');
            $table->string('motivo')->nullable();
            $table->string('fornecedor')->nullable();
            $table->text('observacao')->nullable();
            $table->datetime('data_movimento');
            $table->timestamps();
        });

        // ---- Tabela de logs de auditoria ----
        Schema::create('logs_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('acao');                           // Ex: criou_produto
            $table->string('entidade');                      // Ex: produto
            $table->unsignedBigInteger('entidade_id')->nullable();
            $table->json('detalhes')->nullable();             // Informações extras em JSON
            $table->string('ip', 45)->nullable();
            $table->datetime('criado_em');
        });

        // ---- Tabela de tokens do Sanctum ----
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Desfaz todas as migrations (drop nas tabelas na ordem inversa)
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('logs_auditoria');
        Schema::dropIfExists('movimentos');
        Schema::dropIfExists('itens_lote');
        Schema::dropIfExists('lotes');
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('usuarios');
    }
};
