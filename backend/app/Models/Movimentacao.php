<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth; 

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
    return $this->belongsTo(User::class, 'id_usuario');
}
public static function registrar(string $tipo, int $quantidade, int $idLote, ?int $idItem, ?string $observacao = null): self
{
    return self::create([
        'tipo'              => $tipo,
        'quantidade'        => $quantidade,
        'data_movimentacao' => now(),
        'observacao'        => $observacao,
        'id_lote'           => $idLote,
        'id_item'           => $idItem,
        'id_usuario'        => Auth::id(),
    ]);
}
}