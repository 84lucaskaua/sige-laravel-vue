<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * Controller de Usuários
 *
 * Gerencia o cadastro de usuários do sistema.
 * Só o admin pode criar, editar e desativar outros usuários.
 */
class UsuarioController extends Controller
{
    /**
     * Lista todos os usuários ativos
     */
    public function index(): JsonResponse
    {
        $usuarios = Usuario::where('ativo', true)
            ->select('id', 'nome', 'email', 'perfil', 'foto_url', 'created_at')
            ->orderBy('nome')
            ->get();

        return response()->json($usuarios);
    }

    /**
     * Cria um novo usuário
     */
    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome'   => 'required|string|max:255',
            'email'  => 'required|email|unique:usuarios,email',
            'senha'  => 'required|string|min:8|confirmed', // confirmed = precisa do campo senha_confirmation
            'perfil' => 'required|in:admin,operador,visualizador',
        ]);

        // Nunca salva a senha em texto puro — sempre criptografada
        $usuario = Usuario::create([
            'nome'   => $dados['nome'],
            'email'  => $dados['email'],
            'senha'  => Hash::make($dados['senha']),
            'perfil' => $dados['perfil'],
            'ativo'  => true,
        ]);

        return response()->json([
            'id'     => $usuario->id,
            'nome'   => $usuario->nome,
            'email'  => $usuario->email,
            'perfil' => $usuario->perfil,
        ], 201);
    }

    /**
     * Atualiza dados de um usuário
     *
     * A senha só é atualizada se um novo valor for enviado.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $usuario = Usuario::findOrFail($id);

        $dados = $request->validate([
            'nome'   => 'required|string|max:255',
            'email'  => "required|email|unique:usuarios,email,{$id}",
            'perfil' => 'required|in:admin,operador,visualizador',
            // Senha é opcional na edição
            'senha'  => 'nullable|string|min:8|confirmed',
        ]);

        // Atualiza os dados básicos
        $usuario->nome   = $dados['nome'];
        $usuario->email  = $dados['email'];
        $usuario->perfil = $dados['perfil'];

        // Só muda a senha se uma nova for enviada
        if (!empty($dados['senha'])) {
            $usuario->senha = Hash::make($dados['senha']);
        }

        $usuario->save();

        return response()->json([
            'id'     => $usuario->id,
            'nome'   => $usuario->nome,
            'email'  => $usuario->email,
            'perfil' => $usuario->perfil,
        ]);
    }

    /**
     * Desativa um usuário
     *
     * Não apaga do banco para preservar o histórico de ações dele.
     * Um usuário não pode desativar a si mesmo.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        // Impede que o admin desative a própria conta
        if ($request->user()->id === $id) {
            return response()->json([
                'mensagem' => 'Você não pode desativar sua própria conta.',
            ], 422);
        }

        $usuario = Usuario::findOrFail($id);
        $usuario->update(['ativo' => false]);

        // Revoga todos os tokens do usuário desativado
        $usuario->tokens()->delete();

        return response()->json([
            'mensagem' => "Usuário '{$usuario->nome}' desativado com sucesso.",
        ]);
    }
}
