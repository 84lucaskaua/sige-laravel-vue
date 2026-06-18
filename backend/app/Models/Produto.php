<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model {
    protected $table      = 'produto';
    protected $primaryKey = 'id_produto';
    protected $fillable   = ['nome', 'sku', 'unidade_medida', 'preco_custo', 'estoque_minimo', 'estoque_atual', 'prioridade_abc', 'id_categoria', 'id_fornecedor'];

    public function categoria() {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }
    public function fornecedor() {
        return $this->belongsTo(Fornecedor::class, 'id_fornecedor');
    }
    public function lotes() {
        return $this->hasMany(Lote::class, 'id_produto');
    }
}