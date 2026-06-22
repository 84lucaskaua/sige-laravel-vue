<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemLote extends Model
{
    protected $table      = 'item_lote';
    protected $primaryKey = 'id_item';
    public $timestamps = false;

    protected $fillable = [
        'id_lote',
        'nome',
        'sku',
        'quantidade',
        'estoque_minimo',
        'unidade_medida',
        'data_validade',
        'fornecedor',
        'localizacao',
        'prioridade_abc',
        'categoria',
    ];
}