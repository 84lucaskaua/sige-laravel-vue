<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model de Produto
 *
 * Representa um produto cadastrado no almoxarifado.
 */
class Produto extends Model
{
    use HasFactory;

    protected $table = 'produtos';

    protected $fillable = [
        'codigo',
        'nome',
        'descricao',
        'categoria_id',
        'unidade',          // UN, CX, PCT, KG...
        'estoque_minimo',   // Quantidade mínima antes de alertar
        'foto_url',
        'ativo',
    ];

    protected $casts = [
        'ativo'          => 'boolean',
        'estoque_minimo' => 'integer',
    ];

    // ============================================================
    // Relacionamentos
    // ============================================================

    /**
     * Um produto pertence a uma categoria
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Um produto pode ter muitos itens de lote
     */
    public function itensLote()
    {
        return $this->hasMany(ItemLote::class, 'produto_id');
    }

    // ============================================================
    // Helpers de estoque
    // ============================================================

    /**
     * Calcula a quantidade total disponível somando todos os lotes
     */
    public function quantidadeTotal(): int
    {
        return $this->itensLote()->sum('quantidade');
    }

    /**
     * Verifica se o estoque está abaixo do mínimo
     */
    public function estoqueAbaixoDoMinimo(): bool
    {
        return $this->quantidadeTotal() < $this->estoque_minimo;
    }
}
