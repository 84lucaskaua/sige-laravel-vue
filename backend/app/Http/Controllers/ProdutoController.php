<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\LogAuditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller de Produtos
 *
 * Cuida de tudo relacionado ao cadastro de produtos:
 * listar, criar, editar e desativar produtos.
 */
class ProdutoController extends Controller
{
    /**
     * Lista todos os produtos cadastrados
     *
     * Pode filtrar por nome, código ou categoria.
     */
    public function index(Request $request): JsonResponse
    {
        $produtos = Produto::with('categoria')
            ->where('ativo', true)
            // Filtro por nome ou código (se o usuário pesquisar)
            ->when($request->busca, function ($query, $busca) {
                $query->where(function ($q) use ($busca) {
                    $q->where('nome', 'like', "%{$busca}%")
                      ->orWhere('codigo', 'like', "%{$busca}%");
                });
            })
            // Filtro por categoria (se informado)
            ->when($request->categoria_id, function ($query, $categoriaId) {
                $query->where('categoria_id', $categoriaId);
            })
            ->orderBy('nome')
            ->get();

        return response()->json($produtos);
    }

    /**
     * Cadastra um novo produto
     */
    public function store(Request $request): JsonResponse
    {
        // Valida os dados antes de salvar
        $dadosValidados = $request->validate([
            'codigo'         => 'required|string|unique:produtos,codigo',
            'nome'           => 'required|string|max:255',
            'descricao'      => 'nullable|string',
            'categoria_id'   => 'nullable|exists:categorias,id',
            'unidade'        => 'required|in:UN,CX,PCT,KG,G,L,ML,FR,RL,KIT,EMB',
            'estoque_minimo' => 'required|integer|min:0',
            'foto_url'       => 'nullable|string',
        ]);

        // Cria o produto no banco
        $produto = Produto::create($dadosValidados);

        // Registra a ação no log de auditoria
        $this->registrarLog($request, 'criou_produto', 'produto', $produto->id, [
            'nome'   => $produto->nome,
            'codigo' => $produto->codigo,
        ]);

        // Retorna o produto criado com status 201 (Created)
        return response()->json($produto->load('categoria'), 201);
    }

    /**
     * Mostra um produto específico pelo ID
     */
    public function show(int $id): JsonResponse
    {
        $produto = Produto::with('categoria')->findOrFail($id);

        return response()->json($produto);
    }

    /**
     * Atualiza os dados de um produto existente
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $produto = Produto::findOrFail($id);

        $dadosValidados = $request->validate([
            'codigo'         => "required|string|unique:produtos,codigo,{$id}",
            'nome'           => 'required|string|max:255',
            'descricao'      => 'nullable|string',
            'categoria_id'   => 'nullable|exists:categorias,id',
            'unidade'        => 'required|in:UN,CX,PCT,KG,G,L,ML,FR,RL,KIT,EMB',
            'estoque_minimo' => 'required|integer|min:0',
            'foto_url'       => 'nullable|string',
        ]);

        $produto->update($dadosValidados);

        $this->registrarLog($request, 'editou_produto', 'produto', $produto->id, [
            'nome' => $produto->nome,
        ]);

        return response()->json($produto->load('categoria'));
    }

    /**
     * Desativa um produto (não apaga do banco, só marca como inativo)
     *
     * Isso preserva o histórico de movimentações.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $produto = Produto::findOrFail($id);

        $produto->update(['ativo' => false]);

        $this->registrarLog($request, 'desativou_produto', 'produto', $produto->id, [
            'nome' => $produto->nome,
        ]);

        return response()->json([
            'mensagem' => "Produto '{$produto->nome}' desativado com sucesso.",
        ]);
    }

    /**
     * Registra uma ação no log de auditoria
     *
     * Fica separado aqui para não repetir código nos outros métodos.
     */
    private function registrarLog(Request $request, string $acao, string $entidade, int $entidadeId, array $detalhes): void
    {
        LogAuditoria::create([
            'usuario_id'  => $request->user()->id,
            'acao'        => $acao,
            'entidade'    => $entidade,
            'entidade_id' => $entidadeId,
            'detalhes'    => $detalhes,
            'ip'          => $request->ip(),
            'criado_em'   => now(),
        ]);
    }
}
