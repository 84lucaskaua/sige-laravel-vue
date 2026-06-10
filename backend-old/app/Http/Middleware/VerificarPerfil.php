<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: Verificar Perfil de Acesso
 *
 * Protege rotas que só devem ser acessadas por certos perfis.
 *
 * Como usar nas rotas:
 *   ->middleware('perfil:admin')         // só admin
 *   ->middleware('perfil:admin,operador') // admin ou operador
 */
class VerificarPerfil
{
    /**
     * Verifica se o usuário logado tem o perfil necessário
     *
     * @param string $perfisPermitidos  Perfis separados por vírgula (ex: "admin,operador")
     */
    public function handle(Request $request, Closure $next, string ...$perfisPermitidos): Response
    {
        $usuario = $request->user();

        // Se não estiver logado, retorna 401 (não autenticado)
        if (!$usuario) {
            return response()->json([
                'mensagem' => 'Você precisa estar logado para acessar este recurso.',
            ], 401);
        }

        // Verifica se o perfil do usuário está na lista de permitidos
        $temPermissao = in_array($usuario->perfil, $perfisPermitidos);

        if (!$temPermissao) {
            return response()->json([
                'mensagem' => 'Você não tem permissão para acessar este recurso.',
            ], 403);
        }

        // Tudo certo, deixa a requisição passar
        return $next($request);
    }
}
