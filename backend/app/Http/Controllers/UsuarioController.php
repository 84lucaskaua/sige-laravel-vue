<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\AuditHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::select('id', 'name', 'email', 'perfil', 'ativo', 'created_at')
            ->orderBy('id', 'asc')
            ->get();
        return response()->json($usuarios);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'perfil'   => 'required|in:root,operador,visualizador',
        ]);

        $usuario = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'perfil'   => $request->perfil,
        ]);

        AuditHelper::log('Criacao', 'Usuario "' . $usuario->name . '" criado.');

        return response()->json($usuario, 201);
    }

    public function update(Request $request, int $id)
    {
        $usuario = User::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6|confirmed',
            'perfil'   => 'required|in:root,operador,visualizador',
        ]);

        $dados = [
            'name'   => $request->name,
            'email'  => $request->email,
            'perfil' => $request->perfil,
        ];

        if ($request->filled('password')) {
            $dados['password'] = Hash::make($request->password);
        }

        $usuario->update($dados);

        AuditHelper::log('Edicao', 'Usuario "' . $usuario->name . '" atualizado.');

        return response()->json($usuario);
    }

    public function alternarStatus(Request $request, int $id)
    {
        $usuario = User::findOrFail($id);

        $request->validate([
            'ativo' => 'required|boolean',
        ]);

        if ($usuario->id === Auth::id()) {
            return response()->json(['mensagem' => 'Você não pode inativar o próprio usuário.'], 422);
        }

        $usuario->update(['ativo' => $request->ativo]);

        if (!$request->ativo) {
            $usuario->tokens()->delete();
        }

        $acao = $request->ativo ? 'Ativacao' : 'Inativacao';
        AuditHelper::log($acao, 'Usuario "' . $usuario->name . '" ' . ($request->ativo ? 'ativado' : 'inativado') . '.');

        return response()->json($usuario);
    }
}