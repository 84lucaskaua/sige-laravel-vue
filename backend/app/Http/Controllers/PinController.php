<?php

namespace App\Http\Controllers;

use App\Mail\CodigoPinMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PinController extends Controller
{
    /**
     * Gera um novo PIN e envia por email.
     * Usado tanto na primeira vez quanto quando a pessoa esquece o PIN.
     */
    public function solicitar(Request $request)
    {
        $usuario = $request->user();

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $usuario->pin = Hash::make($codigo);
        $usuario->save();

        Mail::to($usuario->email)->send(new CodigoPinMail($codigo));

        return response()->json([
            'message' => 'PIN enviado para o seu email.',
        ]);
    }

    /**
     * Verifica o PIN. Não expira e não é de uso único —
     * a pessoa usa o mesmo PIN até pedir um novo.
     */
    public function verificar(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:6',
        ]);

        $usuario = $request->user();

        if (!$usuario->pin) {
            return response()->json(['message' => 'Você ainda não tem um PIN. Solicite um.'], 422);
        }

        if (!Hash::check($request->pin, $usuario->pin)) {
            return response()->json(['message' => 'PIN incorreto. Tente novamente.'], 422);
        }

        return response()->json(['message' => 'PIN verificado com sucesso.']);
    }
}