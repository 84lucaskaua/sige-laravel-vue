<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Movimentacao extends Model {
    protected $table      = 'movimentacao';
    protected $primaryKey = 'id_movimentacao';
    public $timestamps = false;
    protected $fillable   = ['tipo', 'quantidade', 'data_movimentacao', 'observacao', 'id_lote', 'id_item', 'id_usuario'];

    public function lote() {
        return $this->belongsTo(Lote::class, 'id_lote')->with('produto');
    }
    public function item() {
        return $this->belongsTo(ItemLote::class, 'id_item');
    }
    public function usuario() {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}