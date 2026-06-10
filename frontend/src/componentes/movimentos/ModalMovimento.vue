<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('fechar')">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">

      <!-- Título com cor diferente para entrada e saída -->
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-bold" :class="tipo === 'entrada' ? 'text-green-700' : 'text-red-700'">
          {{ tipo === 'entrada' ? '⬆️ Registrar Entrada' : '⬇️ Registrar Saída' }}
        </h2>
        <button @click="$emit('fechar')" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
      </div>

      <form @submit.prevent="salvar">

        <!-- Seleção do item de lote -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Produto / Item de Lote *</label>
          <select v-model="formulario.item_lote_id" required class="campo-texto">
            <option value="">Selecione um produto...</option>
            <option v-for="item in itensLote" :key="item.id" :value="item.id">
              {{ item.produto?.nome }} — Lote {{ item.lote?.numero }} ({{ item.quantidade }} disponíveis)
            </option>
          </select>
        </div>

        <!-- Quantidade -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade *</label>
          <input
            v-model="formulario.quantidade"
            type="number"
            min="1"
            required
            class="campo-texto"
            placeholder="Ex: 10"
          />
        </div>

        <!-- Fornecedor (só para entrada) -->
        <div v-if="tipo === 'entrada'" class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Fornecedor</label>
          <input v-model="formulario.fornecedor" type="text" class="campo-texto" placeholder="Nome do fornecedor" />
        </div>

        <!-- Motivo (só para saída) -->
        <div v-if="tipo === 'saida'" class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Motivo da saída *</label>
          <select v-model="formulario.motivo" required class="campo-texto">
            <option value="">Selecione o motivo...</option>
            <option value="Uso na cozinha">Uso na cozinha</option>
            <option value="Uso em limpeza">Uso em limpeza</option>
            <option value="Descarte por vencimento">Descarte por vencimento</option>
            <option value="Quebra ou avaria">Quebra ou avaria</option>
            <option value="Transferência">Transferência</option>
            <option value="Outro">Outro</option>
          </select>
        </div>

        <!-- Observação -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">Observação</label>
          <textarea v-model="formulario.observacao" rows="2" class="campo-texto" placeholder="Opcional"></textarea>
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
          <button
            type="submit"
            :disabled="salvando"
            :class="tipo === 'entrada' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'"
            class="px-4 py-2 text-white rounded-lg disabled:opacity-50 transition"
          >
            {{ salvando ? 'Salvando...' : 'Confirmar' }}
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/servicos/api'

const props = defineProps({
  tipo: { type: String, required: true }, // 'entrada' ou 'saida'
})
const emit = defineEmits(['fechar', 'salvo'])

const itensLote = ref([])
const salvando  = ref(false)
const erro      = ref('')

const formulario = ref({
  item_lote_id: '',
  quantidade:   1,
  fornecedor:   '',
  motivo:       '',
  observacao:   '',
})

/**
 * Carrega todos os itens de lote disponíveis para o select
 */
async function carregarItensLote() {
  try {
    const resposta = await api.get('/lotes/itens')
    itensLote.value = resposta.data
  } catch {
    // Silencioso — a lista ficará vazia mas não travará o modal
  }
}

/**
 * Envia o movimento para o backend
 */
async function salvar() {
  erro.value    = ''
  salvando.value = true

  try {
    // Endpoint diferente para entrada e saída
    const endpoint = props.tipo === 'entrada'
      ? '/movimentos/entrada'
      : '/movimentos/saida'

    await api.post(endpoint, formulario.value)
    emit('salvo')

  } catch (erroHttp) {
    erro.value = erroHttp.response?.data?.mensagem
      || erroHttp.response?.data?.message
      || 'Erro ao registrar movimento.'
  } finally {
    salvando.value = false
  }
}

onMounted(carregarItensLote)
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
