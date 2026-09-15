<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogDeAuditoria extends Model
{
    protected $table = 'log_de_auditoria';
    protected $primaryKey = 'id_log';
    public $timestamps = false;

    protected $fillable = [
        'nome_entidade',
        'id_entidade',
        'acao',
        'descricao',
        'dados_antes',
        'dados_depois',
        'id_usuario',
        'data_criacao',
    ];

    protected $casts = [
        'data_criacao' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(\App\Models\User::class, 'id_usuario');
    }
}