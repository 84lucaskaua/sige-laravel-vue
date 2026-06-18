<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Movimentacao extends Model {
    protected $table      = 'movimentacao';
    protected $primaryKey = 'id_movimentacao';
    protected $fillable   = ['tipo', 'quantidade', 'data_movimentacao', 'observacao', 'id_lote', 'id_usuario'];

    public function lote() {
        return $this->belongsTo(Lote::class, 'id_lote')->with('produto');
    }
    public function usuario() {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}