<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller de Categorias
 *
 * Gerencia as categorias dos produtos (criar, listar, editar, remover).
 */
class CategoriaController extends Controller
{
    /**
     * Lista todas as categorias
     */
    public function index(): JsonResponse
    {
        $categorias = Categoria::withCount('produtos')
            ->orderBy('nome')
            ->get();

        return response()->json($categorias);
    }

    /**
     * Cria uma nova categoria
     */
    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome'      => 'required|string|max:100|unique:categorias,nome',
            'descricao' => 'nullable|string',
            'cor'       => 'nullable|string|max:7', // Ex: #3B82F6
        ]);

        $categoria = Categoria::create($dados);

        return response()->json($categoria, 201);
    }

    /**
     * Atualiza uma categoria existente
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $categoria = Categoria::findOrFail($id);

        $dados = $request->validate([
            'nome'      => "required|string|max:100|unique:categorias,nome,{$id}",
            'descricao' => 'nullable|string',
            'cor'       => 'nullable|string|max:7',
        ]);

        $categoria->update($dados);

        return response()->json($categoria);
    }

    /**
     * Remove uma categoria
     *
     * Só pode remover se não houver produtos vinculados.
     */
    public function destroy(int $id): JsonResponse
    {
        $categoria = Categoria::withCount('produtos')->findOrFail($id);

        // Impede remoção se houver produtos nesta categoria
        if ($categoria->produtos_count > 0) {
            return response()->json([
                'mensagem' => "Não é possível remover: há {$categoria->produtos_count} produto(s) nesta categoria.",
            ], 422);
        }

        $categoria->delete();

        return response()->json([
            'mensagem' => "Categoria '{$categoria->nome}' removida com sucesso.",
        ]);
    }
}
