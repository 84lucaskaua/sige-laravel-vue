<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Localizacao extends Model {
    protected $table      = 'localizacao';
    protected $primaryKey = 'id_localizacao';
    protected $fillable   = ['corredor', 'prateleira', 'setor'];
}