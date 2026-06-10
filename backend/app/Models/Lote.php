<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model de Lote
 *
 * Um lote é um grupo de itens recebidos juntos.
 * Exemplo: "Lote 001 - Recebimento de Janeiro"
 */
class Lote extends Model
{
    use HasFactory;

    protected $table = 'lotes';

    protected $fillable = [
        'numero',       // Ex: LOT-2024-001
        'descricao',
        'data_entrada',
    ];

    protected $casts = [
        'data_entrada' => 'date',
    ];

    /**
     * Um lote pode ter muitos itens (produtos diferentes)
     */
    public function itens()
    {
        return $this->hasMany(ItemLote::class, 'lote_id');
    }
}
