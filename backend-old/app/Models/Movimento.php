<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model de Movimento
 *
 * Registra toda entrada ou saída de produto no almoxarifado.
 * É o histórico completo de movimentações do estoque.
 */
class Movimento extends Model
{
    use HasFactory;

    protected $table = 'movimentos';

    protected $fillable = [
        'item_lote_id',
        'usuario_id',
        'tipo',          // 'entrada' ou 'saida'
        'quantidade',
        'motivo',        // Ex: "Compra", "Uso na cozinha", "Vencimento"
        'fornecedor',    // Preenchido só em entradas
        'observacao',
        'data_movimento',
    ];

    protected $casts = [
        'quantidade'     => 'integer',
        'data_movimento' => 'datetime',
    ];

    // ============================================================
    // Relacionamentos
    // ============================================================

    /**
     * O movimento pertence a um item de lote específico
     */
    public function itemLote()
    {
        return $this->belongsTo(ItemLote::class, 'item_lote_id');
    }

    /**
     * O movimento foi feito por um usuário
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // ============================================================
    // Helpers
    // ============================================================

    /**
     * Verifica se é uma entrada de estoque
     */
    public function ehEntrada(): bool
    {
        return $this->tipo === 'entrada';
    }

    /**
     * Verifica se é uma saída de estoque
     */
    public function ehSaida(): bool
    {
        return $this->tipo === 'saida';
    }
}
