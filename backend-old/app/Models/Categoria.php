<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model de Categoria
 *
 * Organiza os produtos em grupos (ex: Material de Limpeza, Alimentos)
 */
class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = [
        'nome',
        'descricao',
        'cor',   // Cor para exibição visual (ex: #3B82F6)
    ];

    /**
     * Uma categoria pode ter muitos produtos
     */
    public function produtos()
    {
        return $this->hasMany(Produto::class, 'categoria_id');
    }
}
