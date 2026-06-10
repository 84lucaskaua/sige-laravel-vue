<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Controller de Autenticação
 *
 * Responsável pelo login e logout dos usuários.
 * Usa o Laravel Sanctum para gerar tokens de acesso.
 */
class AutenticacaoController extends Controller
{
    /**
     * Faz o login do usuário
     *
     * Recebe email e senha, verifica se estão corretos,
     * e retorna um token de acesso para o frontend usar.
     */
    public function login(Request $request): JsonResponse
    {
        // Valida os dados recebidos antes de qualquer coisa
        $request->validate([
            'email' => 'required|email',
            'senha' => 'required|string|min:6',
        ]);

        // Busca o usuário pelo email
        $usuario = Usuario::where('email', $request->email)
                          ->where('ativo', true)
                          ->first();

        // Verifica se o usuário existe e se a senha está correta
        $senhaCorreta = $usuario && Hash::check($request->senha, $usuario->senha);

        if (!$senhaCorreta) {
            // Retorna erro genérico para não revelar qual campo está errado
            throw ValidationException::withMessages([
                'email' => ['Email ou senha incorretos.'],
            ]);
        }

        // Gera um novo token de acesso para este dispositivo
        $token = $usuario->createToken('token-sige')->plainTextToken;

        // Retorna os dados do usuário e o token
        return response()->json([
            'usuario' => [
                'id'      => $usuario->id,
                'nome'    => $usuario->nome,
                'email'   => $usuario->email,
                'perfil'  => $usuario->perfil,
                'foto'    => $usuario->foto_url,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Faz o logout do usuário
     *
     * Revoga o token atual, invalidando o acesso.
     */
    public function logout(Request $request): JsonResponse
    {
        // Apaga o token usado nesta requisição
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'mensagem' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * Retorna os dados do usuário logado
     */
    public function meusDados(Request $request): JsonResponse
    {
        $usuario = $request->user();

        return response()->json([
            'id'     => $usuario->id,
            'nome'   => $usuario->nome,
            'email'  => $usuario->email,
            'perfil' => $usuario->perfil,
            'foto'   => $usuario->foto_url,
        ]);
    }
}
