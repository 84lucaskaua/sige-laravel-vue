<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model de Item de Lote
 *
 * É a linha de um lote. Cada item representa uma quantidade
 * de um produto específico dentro de um lote.
 * Exemplo: 50 unidades de Luva Cirúrgica no Lote 001
 */
class ItemLote extends Model
{
    use HasFactory;

    protected $table = 'itens_lote';

    protected $fillable = [
        'lote_id',
        'produto_id',
        'quantidade',
        'estoque_minimo',
        'validade',
        'fornecedor',
        'localizacao',      // Prateleira ou setor onde o item está
        'preco_unitario',   // Em centavos (para evitar problemas com float)
        'prioridade',       // A, B ou C (classificação ABC)
    ];

    protected $casts = [
        'quantidade'     => 'integer',
        'estoque_minimo' => 'integer',
        'preco_unitario' => 'integer',
        'validade'       => 'date',
    ];

    // ============================================================
    // Relacionamentos
    // ============================================================

    /**
     * Um item de lote pertence a um lote
     */
    public function lote()
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    /**
     * Um item de lote pertence a um produto
     */
    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    /**
     * Um item de lote pode ter muitas movimentações
     */
    public function movimentos()
    {
        return $this->hasMany(Movimento::class, 'item_lote_id');
    }

    // ============================================================
    // Helpers
    // ============================================================

    /**
     * Retorna o preço formatado em reais
     * Ex: 1500 centavos → "R$ 15,00"
     */
    public function precoFormatado(): string
    {
        $emReais = $this->preco_unitario / 100;
        return 'R$ ' . number_format($emReais, 2, ',', '.');
    }

    /**
     * Verifica se o produto está próximo do vencimento (nos próximos 30 dias)
     */
    public function proximoDoVencimento(): bool
    {
        if (!$this->validade) {
            return false;
        }

        $diasRestantes = now()->diffInDays($this->validade, false);
        return $diasRestantes >= 0 && $diasRestantes <= 30;
    }

    /**
     * Verifica se o produto já está vencido
     */
    public function estaVencido(): bool
    {
        if (!$this->validade) {
            return false;
        }

        return $this->validade->isPast();
    }
}
