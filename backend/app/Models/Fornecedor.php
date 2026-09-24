<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model {
    protected $table      = 'fornecedor';
    protected $primaryKey = 'id_fornecedor';
    protected $fillable   = ['nome', 'cnpj', 'telefone', 'email', 'endereco'];
}