<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Lote extends Model {
    protected $table      = 'lote';
    protected $primaryKey = 'id_lote';
    protected $fillable   = ['numero_lote', 'quantidade', 'status', 'data_entrada', 'data_validade', 'descricao', 'id_produto', 'id_localizacao'];

    public function produto() {
        return $this->belongsTo(Produto::class, 'id_produto')->with('categoria');
    }
    public function localizacao() {
        return $this->belongsTo(Localizacao::class, 'id_localizacao');
    }
    public function movimentacoes() {
        return $this->hasMany(Movimentacao::class, 'id_lote');
    }
}