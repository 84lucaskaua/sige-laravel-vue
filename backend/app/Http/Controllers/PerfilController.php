<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    /**
     * Atualizar perfil do usuário (nome, email e foto)
     */
    public function atualizarPerfil(Request $request)
    {
        $usuario = $request->user();

        // MUDANÇA PRINCIPAL: Adicionar validação de email com regra unique
        $validated = $request->validate([
            'nome'  => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario->id),
                // ↑ Isso permite que o email seja único, mas ignora o próprio usuário
            ],
            'foto'         => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
            'remover_foto' => 'nullable|boolean',
        ]);

        // Atualizar nome
        $usuario->name = $validated['nome'];

        // MUDANÇA: Atualizar email
        $usuario->email = $validated['email'];

        // Processar foto (mesmo de antes)
        if ($request->hasFile('foto')) {
            if ($usuario->foto_url) {
                $caminhoAntigo = public_path('storage/' . $usuario->foto_url);
                if (file_exists($caminhoAntigo)) {
                    unlink($caminhoAntigo);
                }
            }

            $arquivo = $request->file('foto');
            $nome = time() . '_' . uniqid() . '.' . $arquivo->getClientOriginalExtension();
            $arquivo->storeAs('usuarios/fotos', $nome, 'public');
            $usuario->foto_url = 'usuarios/fotos/' . $nome;
        } elseif ($request->input('remover_foto')) {
            if ($usuario->foto_url) {
                $caminhoAntigo = public_path('storage/' . $usuario->foto_url);
                if (file_exists($caminhoAntigo)) {
                    unlink($caminhoAntigo);
                }
            }
            $usuario->foto_url = null;
        }

        // Salvar tudo de uma vez
        $usuario->save();

        // MUDANÇA: Retornar email atualizado também
       return response()->json([
    'message' => 'Perfil atualizado com sucesso!',
    'usuario' => [
        'name'     => $usuario->name,
        'email'    => $usuario->email,
        'foto_url' => $usuario->foto_url ? asset('storage/' . $usuario->foto_url) : null,
    ],
]);
    }

    /**
     * Atualizar senha do usuário
     */
    public function atualizarSenha(Request $request)
    {
        $usuario = $request->user();

        $validated = $request->validate([
            'senha_atual'             => 'required|string',
            'nova_senha'              => 'required|string|min:6',
            'nova_senha_confirmation' => 'required|string|same:nova_senha',
        ]);

        // Verificar se a senha atual está correta
        if (!password_verify($validated['senha_atual'], $usuario->password)) {
            return response()->json([
                'message' => 'Senha atual incorreta.',
            ], 422);
        }

        // Atualizar para a nova senha
        $usuario->password = bcrypt($validated['nova_senha']);
        $usuario->save();

        return response()->json([
            'message' => 'Senha atualizada com sucesso!',
        ]);
    }
}