<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('fechar')">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-lg max-h-screen overflow-y-auto">

      <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-bold text-gray-800">
          {{ ehEdicao ? 'Editar Lote' : 'Novo Lote' }}
        </h2>
        <button @click="$emit('fechar')" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
      </div>

      <form @submit.prevent="salvar">

        <!-- Número do lote -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Número do Lote *</label>
          <input
            v-model="formulario.numero"
            type="text"
            required
            class="campo-texto"
            placeholder="Ex: LOT-2024-001"
          />
        </div>

        <!-- Data de entrada -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Data de Entrada *</label>
          <input v-model="formulario.data_entrada" type="date" required class="campo-texto" />
        </div>

        <!-- Descrição -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
          <input v-model="formulario.descricao" type="text" class="campo-texto" placeholder="Ex: Compra mensal de janeiro" />
        </div>

        <!-- Seção de itens do lote -->
        <div class="mb-6">
          <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold text-gray-700">Itens do Lote</h3>
            <button
              type="button"
              @click="adicionarItem"
              class="text-blue-600 text-sm hover:underline"
            >
              + Adicionar item
            </button>
          </div>

          <!-- Lista de itens -->
          <div
            v-for="(item, indice) in formulario.itens"
            :key="indice"
            class="border rounded-lg p-4 mb-3 bg-gray-50"
          >
            <div class="flex justify-between items-center mb-3">
              <span class="text-sm font-medium text-gray-600">Item {{ indice + 1 }}</span>
              <button
                type="button"
                @click="removerItem(indice)"
                class="text-red-500 hover:text-red-700 text-sm"
              >
                Remover
              </button>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <!-- Produto -->
              <div class="col-span-2">
                <label class="block text-xs text-gray-600 mb-1">Produto *</label>
                <select v-model="item.produto_id" required class="campo-texto text-sm">
                  <option value="">Selecione...</option>
                  <option v-for="produto in produtos" :key="produto.id" :value="produto.id">
                    {{ produto.nome }} ({{ produto.unidade }})
                  </option>
                </select>
              </div>

              <!-- Quantidade -->
              <div>
                <label class="block text-xs text-gray-600 mb-1">Quantidade *</label>
                <input v-model="item.quantidade" type="number" min="1" required class="campo-texto text-sm" />
              </div>

              <!-- Estoque mínimo -->
              <div>
                <label class="block text-xs text-gray-600 mb-1">Estoque Mínimo</label>
                <input v-model="item.estoque_minimo" type="number" min="0" class="campo-texto text-sm" />
              </div>

              <!-- Validade -->
              <div>
                <label class="block text-xs text-gray-600 mb-1">Validade</label>
                <input v-model="item.validade" type="date" class="campo-texto text-sm" />
              </div>

              <!-- Localização -->
              <div>
                <label class="block text-xs text-gray-600 mb-1">Localização</label>
                <input v-model="item.localizacao" type="text" class="campo-texto text-sm" placeholder="Ex: Prateleira A3" />
              </div>

              <!-- Fornecedor -->
              <div>
                <label class="block text-xs text-gray-600 mb-1">Fornecedor</label>
                <input v-model="item.fornecedor" type="text" class="campo-texto text-sm" />
              </div>

              <!-- Prioridade ABC -->
              <div>
                <label class="block text-xs text-gray-600 mb-1">Prioridade</label>
                <select v-model="item.prioridade" class="campo-texto text-sm">
                  <option value="">Sem prioridade</option>
                  <option value="A">A — Alta</option>
                  <option value="B">B — Média</option>
                  <option value="C">C — Baixa</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Mensagem quando não tem itens -->
          <p v-if="formulario.itens.length === 0" class="text-sm text-gray-400 italic text-center py-4">
            Nenhum item adicionado. Clique em "+ Adicionar item".
          </p>
        </div>

        <!-- Erro -->
        <div v-if="erro" class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-600 text-sm">
          {{ erro }}
        </div>

        <!-- Botões -->
        <div class="flex justify-end gap-3">
          <button type="button" @click="$emit('fechar')" class="px-4 py-2 text-gray-600 border rounded-lg hover:bg-gray-50">
            Cancelar
          </button>
          <button type="submit" :disabled="salvando" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ salvando ? 'Salvando...' : 'Salvar Lote' }}
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/servicos/api'

const props = defineProps({
  lote: { type: Object, default: null },
})
const emit = defineEmits(['fechar', 'salvo'])

const ehEdicao = computed(() => !!props.lote)
const produtos = ref([])
const salvando = ref(false)
const erro     = ref('')

// Dados do formulário do lote
const formulario = ref({
  numero:       props.lote?.numero       || '',
  data_entrada: props.lote?.data_entrada || new Date().toISOString().split('T')[0],
  descricao:    props.lote?.descricao    || '',
  itens:        [],
})

/**
 * Cria um item vazio para adicionar na lista
 */
function adicionarItem() {
  formulario.value.itens.push({
    produto_id:     '',
    quantidade:     1,
    estoque_minimo: 0,
    validade:       '',
    localizacao:    '',
    fornecedor:     '',
    prioridade:     '',
  })
}

/**
 * Remove um item da lista pelo índice
 */
function removerItem(indice) {
  formulario.value.itens.splice(indice, 1)
}

/**
 * Carrega os produtos disponíveis para o select
 */
async function carregarProdutos() {
  try {
    const resposta = await api.get('/produtos')
    produtos.value = resposta.data
  } catch {
    // Não é crítico
  }
}

/**
 * Salva o lote com todos os seus itens
 */
async function salvar() {
  erro.value    = ''
  salvando.value = true

  try {
    if (ehEdicao.value) {
      await api.put(`/lotes/${props.lote.id}`, formulario.value)
    } else {
      await api.post('/lotes', formulario.value)
    }
    emit('salvo')
  } catch (erroHttp) {
    const errosValidacao = erroHttp.response?.data?.errors
    if (errosValidacao) {
      erro.value = Object.values(errosValidacao).flat().join('. ')
    } else {
      erro.value = erroHttp.response?.data?.message || 'Erro ao salvar lote.'
    }
  } finally {
    salvando.value = false
  }
}

onMounted(carregarProdutos)
</script>

<style scoped>
.campo-texto {
  width: 100%;
  border: 1px solid #D1D5DB;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
}
.campo-texto:focus {
  border-color: #3B82F6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
}
</style>
