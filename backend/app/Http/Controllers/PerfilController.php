<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PerfilController extends Controller
{
    public function atualizar(Request $request)
    {
        /** @var User $usuario */
        $usuario = Auth::user();

        $request->validate([
            'nome'  => 'required|string|max:255',
            'foto'  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $usuario->name = $request->nome;

        if ($request->hasFile('foto')) {
            // Remove foto antiga se existir
            if ($usuario->foto_url) {
                Storage::disk('public')->delete($usuario->foto_url);
            }

            $caminho = $request->file('foto')->store('fotos', 'public');
            $usuario->foto_url = $caminho;
        }

        $usuario->save();

        return response()->json([
            'mensagem' => 'Perfil atualizado com sucesso!',
            'usuario'  => [
                'id'       => $usuario->id,
                'name'     => $usuario->name,
                'email'    => $usuario->email,
                'perfil'   => $usuario->perfil,
                'foto_url' => $usuario->foto_url
                    ? asset('storage/' . $usuario->foto_url)
                    : null,
            ],
        ]);
    }

    public function alterarSenha(Request $request)
    {
        /** @var User $usuario */
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