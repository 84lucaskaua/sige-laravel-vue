<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PerfilController extends Controller
{
    public function atualizar(Request $request)
    {
        $usuario = Auth::user();

        $request->validate([
            'nome'     => 'required|string|max:255',
            'foto_url' => 'nullable|string',
        ]);

        $usuario->name     = $request->nome;
        $usuario->foto_url = $request->foto_url;
        $usuario->save();

        return response()->json([
            'mensagem' => 'Perfil atualizado com sucesso!',
            'usuario'  => $usuario,
        ]);
    }

    public function alterarSenha(Request $request)
    {
        $usuario = Auth::user();

        $request->validate([
            'senha_atual'             => 'required|string',
            'nova_senha'              => 'required|string|min:6',
            'nova_senha_confirmation' => 'required|same:nova_senha',
        ]);

        if (!Hash::check($request->senha_atual, $usuario->password)) {
            return response()->json([
                'message' => 'Senha atual incorreta.',
            ], 422);
        }

        $usuario->password = Hash::make($request->nova_senha);
        $usuario->save();

        return response()->json([
            'mensagem' => 'Senha alterada com sucesso!',
        ]);
    }
}