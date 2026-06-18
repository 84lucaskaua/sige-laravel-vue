<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model {
    protected $table      = 'usuario';
    protected $primaryKey = 'id_usuario';
    protected $fillable   = ['nome', 'email', 'senha', 'nivel_acesso', 'ativo'];
}