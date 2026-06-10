<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model de Log de Auditoria
 *
 * Registra todas as ações importantes feitas no sistema.
 * Exemplo: "admin criou produto", "operador fez saída de 10 unidades"
 * Isso serve para rastrear quem fez o quê e quando.
 */
class LogAuditoria extends Model
{
    use HasFactory;

    protected $table = 'logs_auditoria';

    // Logs de auditoria não devem ser editados, só criados
    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'acao',         // Ex: 'criou_produto', 'fez_saida', 'editou_usuario'
        'entidade',     // Ex: 'produto', 'lote', 'usuario'
        'entidade_id',  // ID do item que foi afetado
        'detalhes',     // JSON com informações extras
        'ip',           // IP de quem fez a ação
        'criado_em',
    ];

    protected $casts = [
        'detalhes'  => 'array',   // Converte JSON ↔ array automaticamente
        'criado_em' => 'datetime',
    ];

    /**
     * O log foi gerado por um usuário
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
