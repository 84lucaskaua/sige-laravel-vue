<template>
  <div class="p-6">

    <!-- Cabeçalho -->
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Movimentos</h1>

      <!-- Botões de entrada e saída (só para quem pode movimentar) -->
      <div v-if="autenticacao.podeMovimentar" class="flex gap-2">
        <button
          @click="abrirModalEntrada"
          class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition"
        >
          ⬆️ Registrar Entrada
        </button>
        <button
          @click="abrirModalSaida"
          class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition"
        >
          ⬇️ Registrar Saída
        </button>
      </div>
    </div>

    <!-- Filtros -->
    <div class="flex gap-3 mb-4">
      <select v-model="filtroTipo" @change="carregarMovimentos" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        <option value="">Todos os tipos</option>
        <option value="entrada">Entradas</option>
        <option value="saida">Saídas</option>
      </select>

      <input
        v-model="filtroDataInicio"
        type="date"
        @change="carregarMovimentos"
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm"
      />
      <input
        v-model="filtroDataFim"
        type="date"
        @change="carregarMovimentos"
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm"
      />
    </div>

    <!-- Carregando -->
    <div v-if="carregando" class="text-center py-12 text-gray-500">
      Carregando movimentos...
    </div>

    <!-- Sem resultados -->
    <div v-else-if="movimentos.length === 0" class="text-center py-12 text-gray-500">
      Nenhum movimento encontrado.
    </div>

    <!-- Tabela de movimentos -->
    <div v-else class="bg-white rounded-xl shadow overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Tipo</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Produto</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Quantidade</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Motivo/Fornecedor</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Usuário</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Data</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="movimento in movimentos"
            :key="movimento.id"
            class="border-b hover:bg-gray-50"
          >
            <!-- Badge de tipo com cor -->
            <td class="px-4 py-3">
              <span
                :class="movimento.tipo === 'entrada'
                  ? 'bg-green-100 text-green-700'
                  : 'bg-red-100 text-red-700'"
                class="px-2 py-1 rounded-full text-xs font-medium capitalize"
              >
                {{ movimento.tipo === 'entrada' ? '⬆️ Entrada' : '⬇️ Saída' }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-800">
              {{ movimento.item_lote?.produto?.nome || '—' }}
            </td>
            <td class="px-4 py-3 text-sm font-semibold"
              :class="movimento.tipo === 'entrada' ? 'text-green-600' : 'text-red-600'"
            >
              {{ movimento.tipo === 'entrada' ? '+' : '-' }}{{ movimento.quantidade }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
              {{ movimento.motivo || movimento.fornecedor || '—' }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
              {{ movimento.usuario?.nome || 'Sistema' }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
              {{ formatarData(movimento.data_movimento) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal de Entrada -->
    <ModalMovimento
      v-if="modalAberto"
      :tipo="tipoModal"
      @fechar="fecharModal"
      @salvo="aoSalvar"
    />

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'
import ModalMovimento from '@/componentes/movimentos/ModalMovimento.vue'

const autenticacao = useAutenticacaoStore()

const movimentos     = ref([])
const carregando     = ref(false)
const filtroTipo     = ref('')
const filtroDataInicio = ref('')
const filtroDataFim    = ref('')
const modalAberto    = ref(false)
const tipoModal      = ref('entrada')  // 'entrada' ou 'saida'

async function carregarMovimentos() {
  carregando.value = true
  try {
    const resposta = await api.get('/movimentos', {
      params: {
        tipo:        filtroTipo.value      || undefined,
        data_inicio: filtroDataInicio.value || undefined,
        data_fim:    filtroDataFim.value    || undefined,
      },
    })
    movimentos.value = resposta.data.data || resposta.data
  } catch {
    alert('Não foi possível carregar os movimentos.')
  } finally {
    carregando.value = false
  }
}

function abrirModalEntrada() {
  tipoModal.value  = 'entrada'
  modalAberto.value = true
}

function abrirModalSaida() {
  tipoModal.value  = 'saida'
  modalAberto.value = true
}

function fecharModal() {
  modalAberto.value = false
}

function aoSalvar() {
  fecharModal()
  carregarMovimentos()
}

function formatarData(dataString) {
  if (!dataString) return '—'
  const data = new Date(dataString)
  return data.toLocaleString('pt-BR')
}

onMounted(carregarMovimentos)
</script>
