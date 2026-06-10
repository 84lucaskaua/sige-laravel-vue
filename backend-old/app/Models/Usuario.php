<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Model de Usuário
 *
 * Representa um usuário do sistema SIGE.
 * Usa o HasApiTokens do Sanctum para autenticação via token.
 */
class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Nome da tabela no banco de dados
    protected $table = 'usuarios';

    // Campos que podem ser preenchidos em massa (ex: ao criar via formulário)
    protected $fillable = [
        'nome',
        'email',
        'senha',
        'perfil',      // admin, operador, visualizador
        'foto_url',
        'ativo',
    ];

    // Campos que NUNCA devem aparecer em respostas JSON
    protected $hidden = [
        'senha',
        'remember_token',
    ];

    // Campos com conversão automática de tipo
    protected $casts = [
        'ativo'             => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    // O Laravel usa 'password' por padrão, mas nosso campo é 'senha'
    protected $authPasswordName = 'senha';

    // ============================================================
    // Relacionamentos
    // ============================================================

    /**
     * Um usuário pode ter feitos muitos movimentos de estoque
     */
    public function movimentos()
    {
        return $this->hasMany(Movimento::class, 'usuario_id');
    }

    /**
     * Um usuário pode ter gerado muitos logs de auditoria
     */
    public function logs()
    {
        return $this->hasMany(LogAuditoria::class, 'usuario_id');
    }

    // ============================================================
    // Helpers de perfil
    // ============================================================

    /**
     * Verifica se o usuário é administrador
     */
    public function ehAdmin(): bool
    {
        return $this->perfil === 'admin';
    }

    /**
     * Verifica se o usuário pode fazer movimentações de estoque
     */
    public function podeMovimentar(): bool
    {
        return in_array($this->perfil, ['admin', 'operador']);
    }

    /**
     * Verifica se o usuário pode cadastrar/editar produtos
     */
    public function podeCadastrar(): bool
    {
        return in_array($this->perfil, ['admin', 'operador']);
    }
}
