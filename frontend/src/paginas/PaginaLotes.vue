<template>
  <div class="p-6">

    <!-- Cabeçalho -->
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Lotes</h1>

      <button
        v-if="autenticacao.podeCadastrar"
        @click="abrirModalNovoLote"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
      >
        + Novo Lote
      </button>
    </div>

    <!-- Carregando -->
    <div v-if="carregando" class="text-center py-12 text-gray-500">
      Carregando lotes...
    </div>

    <!-- Sem lotes -->
    <div v-else-if="lotes.length === 0" class="text-center py-12 text-gray-500">
      Nenhum lote cadastrado ainda.
    </div>

    <!-- Lista de lotes em cards -->
    <div v-else class="grid gap-4">
      <div
        v-for="lote in lotes"
        :key="lote.id"
        class="bg-white rounded-xl shadow p-5"
      >
        <!-- Cabeçalho do card do lote -->
        <div class="flex justify-between items-start mb-4">
          <div>
            <h2 class="text-lg font-bold text-gray-800">{{ lote.numero }}</h2>
            <p class="text-sm text-gray-500">
              Entrada: {{ formatarData(lote.data_entrada) }}
            </p>
            <p v-if="lote.descricao" class="text-sm text-gray-600 mt-1">
              {{ lote.descricao }}
            </p>
          </div>

          <!-- Botão para expandir/recolher os itens do lote -->
          <button
            @click="alternarLote(lote.id)"
            class="text-blue-600 text-sm hover:underline"
          >
            {{ lotesAbertos.includes(lote.id) ? 'Recolher ▲' : 'Ver itens ▼' }}
          </button>
        </div>

        <!-- Itens do lote (aparece só quando expandido) -->
        <div v-if="lotesAbertos.includes(lote.id)">

          <!-- Sem itens neste lote -->
          <p v-if="!lote.itens || lote.itens.length === 0" class="text-sm text-gray-400 italic">
            Este lote não possui itens.
          </p>

          <!-- Tabela de itens -->
          <table v-else class="w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left px-3 py-2 text-gray-600 font-medium">Produto</th>
                <th class="text-left px-3 py-2 text-gray-600 font-medium">Qtd. Disponível</th>
                <th class="text-left px-3 py-2 text-gray-600 font-medium">Validade</th>
                <th class="text-left px-3 py-2 text-gray-600 font-medium">Localização</th>
                <th class="text-left px-3 py-2 text-gray-600 font-medium">Prioridade</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in lote.itens"
                :key="item.id"
                class="border-t"
                :class="item.quantidade === 0 ? 'opacity-50' : ''"
              >
                <td class="px-3 py-2 text-gray-800">{{ item.produto?.nome || '—' }}</td>
                <td class="px-3 py-2">
                  <!-- Destaca em vermelho quando está zerado -->
                  <span
                    :class="item.quantidade === 0
                      ? 'text-red-600 font-bold'
                      : item.quantidade <= item.estoque_minimo
                        ? 'text-yellow-600 font-semibold'
                        : 'text-gray-800'"
                  >
                    {{ item.quantidade }} {{ item.produto?.unidade || '' }}
                  </span>
                </td>
                <td class="px-3 py-2">
                  <!-- Alerta visual para itens vencidos ou perto do vencimento -->
                  <span
                    v-if="item.validade"
                    :class="estaVencido(item.validade)
                      ? 'text-red-600 font-semibold'
                      : proximoDoVencimento(item.validade)
                        ? 'text-yellow-600'
                        : 'text-gray-600'"
                  >
                    {{ formatarData(item.validade) }}
                    {{ estaVencido(item.validade) ? '⚠️ Vencido' : '' }}
                    {{ !estaVencido(item.validade) && proximoDoVencimento(item.validade) ? '⏰' : '' }}
                  </span>
                  <span v-else class="text-gray-400">Sem validade</span>
                </td>
                <td class="px-3 py-2 text-gray-600">{{ item.localizacao || '—' }}</td>
                <td class="px-3 py-2">
                  <span
                    v-if="item.prioridade"
                    :class="{
                      'bg-red-100 text-red-700':    item.prioridade === 'A',
                      'bg-yellow-100 text-yellow-700': item.prioridade === 'B',
                      'bg-green-100 text-green-700': item.prioridade === 'C',
                    }"
                    class="px-2 py-0.5 rounded-full text-xs font-bold"
                  >
                    {{ item.prioridade }}
                  </span>
                  <span v-else class="text-gray-400">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal de novo lote -->
    <ModalLote
      v-if="modalAberto"
      :lote="loteSelecionado"
      @fechar="fecharModal"
      @salvo="aoSalvar"
    />

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'
import ModalLote from '@/componentes/ui/ModalLote.vue'
import { formatarData, estaVencido, proximoDoVencimento } from '@/utils/date'

const autenticacao = useAutenticacaoStore()

const lotes          = ref([])
const carregando     = ref(false)
const modalAberto    = ref(false)
const loteSelecionado = ref(null)

// Guarda quais lotes estão expandidos (mostrando seus itens)
const lotesAbertos = ref([])

/**
 * Carrega todos os lotes com seus itens do backend
 */
async function carregarLotes() {
  carregando.value = true
  try {
    const resposta = await api.get('/lotes')
    lotes.value = resposta.data
  } catch {
    alert('Não foi possível carregar os lotes.')
  } finally {
    carregando.value = false
  }
}

/**
 * Abre ou fecha a lista de itens de um lote
 */
function alternarLote(idDoLote) {
  const posicao = lotesAbertos.value.indexOf(idDoLote)

  if (posicao === -1) {
    // Lote ainda não está aberto — adiciona na lista
    lotesAbertos.value.push(idDoLote)
  } else {
    // Lote já está aberto — remove da lista para fechar
    lotesAbertos.value.splice(posicao, 1)
  }
}

function abrirModalNovoLote() {
  loteSelecionado.value = null
  modalAberto.value = true
}

function fecharModal() {
  modalAberto.value = false
  loteSelecionado.value = null
}

function aoSalvar() {
  fecharModal()
  carregarLotes()
}

/**
 * Formata uma data para o padrão brasileiro (dd/mm/aaaa)
 */
onMounted(carregarLotes)
</script>
