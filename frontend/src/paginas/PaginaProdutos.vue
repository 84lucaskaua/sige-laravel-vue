<template>
  <div class="p-6">

    <!-- Cabeçalho da página -->
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Produtos</h1>

      <!-- Botão de novo produto (só aparece para admin e operador) -->
      <button
        v-if="autenticacao.podeCadastrar"
        @click="abrirModalNovoProduto"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
      >
        + Novo Produto
      </button>
    </div>

    <!-- Barra de busca -->
    <div class="mb-4">
      <input
        v-model="termoDeBusca"
        type="text"
        placeholder="Buscar por nome ou código..."
        class="w-full max-w-md border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="buscarComAtraso"
      />
    </div>

    <!-- Indicador de carregamento -->
    <div v-if="carregando" class="text-center py-12 text-gray-500">
      Carregando produtos...
    </div>

    <!-- Mensagem quando não tem produtos -->
    <div v-else-if="produtos.length === 0" class="text-center py-12 text-gray-500">
      Nenhum produto encontrado.
    </div>

    <!-- Tabela de produtos -->
    <div v-else class="bg-white rounded-xl shadow overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Código</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Nome</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Categoria</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Unidade</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Estoque Mín.</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="produto in produtos"
            :key="produto.id"
            class="border-b hover:bg-gray-50 transition"
          >
            <td class="px-4 py-3 text-sm font-mono text-gray-600">{{ produto.codigo }}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ produto.nome }}</td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ produto.categoria?.nome || '—' }}</td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ produto.unidade }}</td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ produto.estoque_minimo }}</td>
            <td class="px-4 py-3">
              <button
                v-if="autenticacao.podeCadastrar"
                @click="abrirModalEdicao(produto)"
                class="text-blue-600 hover:text-blue-800 text-sm mr-3"
              >
                Editar
              </button>
              <button
                v-if="autenticacao.ehAdmin"
                @click="desativarProduto(produto)"
                class="text-red-500 hover:text-red-700 text-sm"
              >
                Remover
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal de criar/editar produto -->
    <ModalProduto
      v-if="modalAberto"
      :produto="produtoSelecionado"
      @fechar="fecharModal"
      @salvo="aoSalvarProduto"
    />

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'
import ModalProduto from '@/componentes/produtos/ModalProduto.vue'

const autenticacao = useAutenticacaoStore()

// ---- Dados da página ----
const produtos          = ref([])
const carregando        = ref(false)
const termoDeBusca      = ref('')
const modalAberto       = ref(false)
const produtoSelecionado = ref(null)  // null = novo produto; objeto = edição

// Temporizador para a busca com atraso (evita buscar a cada tecla)
let temporizadorBusca = null

/**
 * Carrega a lista de produtos do backend
 */
async function carregarProdutos() {
  carregando.value = true

  try {
    const resposta = await api.get('/produtos', {
      params: { busca: termoDeBusca.value || undefined },
    })
    produtos.value = resposta.data

  } catch (erro) {
    console.error('Erro ao carregar produtos:', erro)
    alert('Não foi possível carregar os produtos.')

  } finally {
    carregando.value = false
  }
}

/**
 * Aguarda 400ms após o usuário parar de digitar para buscar
 * Isso evita uma requisição a cada tecla pressionada
 */
function buscarComAtraso() {
  clearTimeout(temporizadorBusca)
  temporizadorBusca = setTimeout(carregarProdutos, 400)
}

/**
 * Abre o modal para criar um novo produto
 */
function abrirModalNovoProduto() {
  produtoSelecionado.value = null  // Limpa para indicar que é um novo produto
  modalAberto.value = true
}

/**
 * Abre o modal preenchido com os dados do produto para editar
 */
function abrirModalEdicao(produto) {
  produtoSelecionado.value = produto
  modalAberto.value = true
}

/**
 * Fecha o modal
 */
function fecharModal() {
  modalAberto.value = false
  produtoSelecionado.value = null
}

/**
 * Chamado quando o produto é salvo no modal
 */
function aoSalvarProduto() {
  fecharModal()
  carregarProdutos()  // Recarrega a lista
}

/**
 * Desativa um produto após confirmação
 */
async function desativarProduto(produto) {
  const confirmar = confirm(`Tem certeza que deseja remover "${produto.nome}"?`)
  if (!confirmar) return

  try {
    await api.delete(`/produtos/${produto.id}`)
    carregarProdutos()  // Recarrega a lista
  } catch (erro) {
    alert('Não foi possível remover o produto.')
  }
}

// Carrega os produtos quando a página abre
onMounted(carregarProdutos)
</script>
