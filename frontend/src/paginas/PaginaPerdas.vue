<template>
  <div class="p-6">

    <!-- Cabeçalho -->
    <h1 class="text-2xl font-bold text-white mb-1">Registro de Perdas</h1>
    <p class="text-slate-400 text-sm mb-6">Controle de perdas por vencimento, quebra, furto e outros motivos</p>

    <!-- Estatísticas -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex items-center gap-4">
        <div class="w-10 h-10 bg-red-900/40 rounded-lg flex items-center justify-center">
          <AlertTriangle class="text-red-400" :size="20" />
        </div>
        <div>
          <p class="text-slate-400 text-xs">Total de Perdas</p>
          <p class="text-white text-2xl font-bold">{{ estatisticas.total }}</p>
        </div>
      </div>
      <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex items-center gap-4">
        <div class="w-10 h-10 bg-orange-900/40 rounded-lg flex items-center justify-center">
          <Trash2 class="text-orange-400" :size="20" />
        </div>
        <div>
          <p class="text-slate-400 text-xs">Unidades Perdidas</p>
          <p class="text-white text-2xl font-bold">{{ estatisticas.unidades }}</p>
        </div>
      </div>
      <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex items-center gap-4">
        <div class="w-10 h-10 bg-blue-900/40 rounded-lg flex items-center justify-center">
          <Calendar class="text-blue-400" :size="20" />
        </div>
        <div>
          <p class="text-slate-400 text-xs">Este Mês</p>
          <p class="text-white text-2xl font-bold">{{ estatisticas.esteMes }}</p>
        </div>
      </div>
    </div>

    <!-- Registrar Nova Perda -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 mb-6">
      <h2 class="text-white font-bold mb-4">Registrar Nova Perda</h2>

      <div v-if="carregandoItens" class="text-slate-400 text-sm text-center py-4">
        Carregando itens...
      </div>

      <div v-else class="space-y-2">
        <div
          v-for="item in itens"
          :key="item.id_item"
          class="flex items-center justify-between bg-slate-800 border border-slate-700 rounded-lg px-4 py-3"
        >
          <div>
            <p class="text-white font-semibold text-sm">{{ item.nome }}</p>
            <p class="text-slate-400 text-xs mt-0.5">
              SKU: {{ item.sku || '—' }}
              &nbsp;&nbsp;Estoque: {{ item.quantidade }} {{ item.unidade_medida }}
            </p>
          </div>
          <button
            @click="abrirModal(item)"
            class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-lg transition font-medium"
          >
            <Trash2 :size="14" />
            Registrar Perda
          </button>
        </div>
      </div>
    </div>

    <!-- Perdas Recentes -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
      <h2 class="text-white font-bold mb-4">Perdas Recentes</h2>

      <div v-if="carregandoPerdas" class="text-slate-400 text-sm text-center py-4">
        Carregando...
      </div>

      <div v-else-if="perdas.length === 0" class="text-slate-500 text-sm text-center py-8">
        Nenhuma perda registrada
      </div>

      <table v-else class="w-full text-sm">
        <thead>
          <tr class="text-slate-400 border-b border-slate-800 text-left">
            <th class="pb-3 font-medium">Produto</th>
            <th class="pb-3 font-medium">Quantidade</th>
            <th class="pb-3 font-medium">Motivo</th>
            <th class="pb-3 font-medium">Data</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <tr v-for="perda in perdas" :key="perda.id_movimentacao" class="hover:bg-slate-800/50 transition">
            <td class="py-3 text-white font-medium">{{ perda.item?.nome || '—' }}</td>
            <td class="py-3 text-red-400 font-bold">-{{ perda.quantidade }}</td>
            <td class="py-3 text-slate-300">{{ perda.observacao || '—' }}</td>
            <td class="py-3 text-slate-400">{{ formatarData(perda.data_movimentacao) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="modalAberto" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">

      <!-- ETAPA 0: Formulário -->
      <div v-if="etapa === 0" class="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-5">
          <h2 class="text-white font-bold">Registrar Perda</h2>
          <button @click="fecharModal" class="text-slate-400 hover:text-white"><X :size="18" /></button>
        </div>

        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 mb-4">
          <p class="text-slate-400 text-xs mb-1">Produto</p>
          <p class="text-white font-bold">{{ itemSelecionado?.nome }}</p>
          <p class="text-slate-400 text-xs mt-2">Estoque disponível</p>
          <p class="text-white font-bold text-xl">{{ itemSelecionado?.quantidade }} {{ itemSelecionado?.unidade_medida }}</p>
        </div>

        <div class="mb-4">
          <label class="block text-sm text-slate-300 font-medium mb-1">Quantidade *</label>
          <input
            v-model.number="form.quantidade"
            type="number"
            min="1"
            :max="itemSelecionado?.quantidade"
            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-red-500"
            placeholder="Ex: 5"
          />
        </div>

        <div class="mb-6">
          <label class="block text-sm text-slate-300 font-medium mb-1">Motivo *</label>
          <select
            v-model="form.motivo"
            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-red-500"
          >
            <option value="">Selecione um motivo</option>
            <option value="Vencimento">Vencimento</option>
            <option value="Quebra">Quebra</option>
            <option value="Furto">Furto</option>
            <option value="Avaria">Avaria</option>
            <option value="Uso interno">Uso interno</option>
            <option value="Outro">Outro</option>
          </select>
        </div>

        <div v-if="erro" class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded text-red-400 text-sm">{{ erro }}</div>

        <div class="flex gap-3">
          <button @click="fecharModal" class="flex-1 py-2.5 rounded-lg border border-slate-600 text-slate-300 hover:bg-slate-800 transition text-sm">Cancelar</button>
          <button
            @click="abrirConfirmacao"
            :disabled="!form.quantidade || !form.motivo"
            class="flex-1 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-40 text-white font-medium transition text-sm"
          >
            Confirmar Perda
          </button>
        </div>
      </div>

      <!-- ETAPA 1: Confirmação -->
      <div v-else-if="etapa === 1" class="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-start mb-5">
          <div class="flex items-center gap-2">
            <Shield class="text-red-400" :size="20" />
            <div>
              <h2 class="text-white font-bold">Confirmação de Segurança</h2>
              <p class="text-slate-400 text-xs">Esta ação requer confirmação em 2 etapas</p>
            </div>
          </div>
          <button @click="fecharModal" class="text-slate-400 hover:text-white"><X :size="18" /></button>
        </div>

        <div class="bg-red-900/30 border border-red-700 rounded-lg p-4 mb-3">
          <div class="flex items-center gap-2 mb-1">
            <AlertTriangle class="text-red-400" :size="16" />
            <span class="text-white font-medium text-sm">Registrar Perda</span>
          </div>
          <p class="text-slate-300 text-sm">
            Confirmar perda de <strong class="text-white">{{ form.quantidade }} {{ itemSelecionado?.unidade_medida }}</strong>
            de "<strong class="text-white">{{ itemSelecionado?.nome }}</strong>". Esta ação reduz o estoque e não pode ser desfeita.
          </p>
        </div>

        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 mb-6">
          <div class="flex items-center gap-2 mb-1">
            <Lock :size="15" class="text-slate-400" />
            <span class="text-white font-medium text-sm">Etapa 1 de 2: Confirmação</span>
          </div>
          <p class="text-slate-400 text-sm">Você está prestes a realizar uma ação que afeta o sistema. Confirme para prosseguir para a etapa de verificação PIN.</p>
        </div>

        <div class="flex gap-3">
          <button @click="etapa = 0" class="flex-1 py-2.5 rounded-lg border border-slate-600 text-slate-300 hover:bg-slate-800 transition text-sm">Voltar</button>
          <button @click="etapa = 2" class="flex-1 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium transition text-sm">Confirmar e Prosseguir</button>
        </div>
      </div>

      <!-- ETAPA 2: PIN -->
      <div v-else class="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-start mb-5">
          <div class="flex items-center gap-2">
            <Lock class="text-blue-400" :size="20" />
            <div>
              <h2 class="text-white font-bold">Verificação de PIN</h2>
              <p class="text-slate-400 text-xs">Etapa 2 de 2: Digite o PIN de segurança</p>
            </div>
          </div>
          <button @click="fecharModal" class="text-slate-400 hover:text-white"><X :size="18" /></button>
        </div>

        <div class="mb-6">
          <label class="block text-sm text-slate-400 mb-2">PIN de Segurança</label>
          <input
            v-model="pinDigitado"
            type="password"
            maxlength="4"
            inputmode="numeric"
            placeholder="• • • •"
            class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-3 text-center text-2xl tracking-[0.6em] outline-none focus:border-blue-500 transition placeholder-slate-600"
            @input="pinDigitado = pinDigitado.replace(/\D/g, '')"
            @keyup.enter="confirmarPerda"
          />
          <p v-if="erroPin" class="text-red-400 text-sm mt-2 text-center">{{ erroPin }}</p>
        </div>

        <div class="flex gap-3">
          <button @click="etapa = 1; pinDigitado = ''; erroPin = ''" class="flex-1 py-2.5 rounded-lg border border-slate-600 text-slate-300 hover:bg-slate-800 transition text-sm">Voltar</button>
          <button
            @click="confirmarPerda"
            :disabled="pinDigitado.length < 4 || salvando"
            class="flex-1 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white font-medium transition text-sm"
          >
            {{ salvando ? 'Confirmando...' : 'Confirmar PIN' }}
          </button>
        </div>
      </div>

    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { AlertTriangle, Trash2, Calendar, X, Shield, Lock } from 'lucide-vue-next'
import api from '@/servicos/api'

const itens            = ref([])
const perdas           = ref([])
const carregandoItens  = ref(false)
const carregandoPerdas = ref(false)
const modalAberto      = ref(false)
const itemSelecionado  = ref(null)
const salvando         = ref(false)
const erro             = ref('')

const etapa       = ref(0)
const pinDigitado = ref('')
const erroPin     = ref('')
const tentativas  = ref(0)
const PIN_CORRETO = '8401'

const estatisticas = ref({ total: 0, unidades: 0, esteMes: 0 })
const form = ref({ quantidade: null, motivo: '' })

const formatarData = (data) => {
  if (!data) return '—'
  return new Date(data).toLocaleDateString('pt-BR')
}

function abrirModal(item) {
  itemSelecionado.value = item
  form.value = { quantidade: null, motivo: '' }
  erro.value = ''
  etapa.value = 0
  pinDigitado.value = ''
  erroPin.value = ''
  tentativas.value = 0
  modalAberto.value = true
}

function abrirConfirmacao() {
  if (!form.value.quantidade || !form.value.motivo) return
  pinDigitado.value = ''
  erroPin.value = ''
  tentativas.value = 0
  etapa.value = 1
}

async function confirmarPerda() {
  if (pinDigitado.value !== PIN_CORRETO) {
    tentativas.value++
    erroPin.value = tentativas.value >= 3
      ? 'PIN incorreto. Acesso bloqueado.'
      : 'PIN incorreto. Tente novamente.'
    pinDigitado.value = ''
    return
  }

  salvando.value = true
  erro.value = ''
  try {
    await api.post('/perdas', {
      id_item:    itemSelecionado.value.id_item,
      quantidade: form.value.quantidade,
      motivo:     form.value.motivo,
    })
    fecharModal()
    await Promise.all([carregarItens(), carregarPerdas(), carregarEstatisticas()])
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao registrar perda.'
    etapa.value = 0
  } finally {
    salvando.value = false
  }
}

function fecharModal() {
  modalAberto.value     = false
  itemSelecionado.value = null
  etapa.value           = 0
  pinDigitado.value     = ''
  erroPin.value         = ''
  tentativas.value      = 0
}

async function carregarItens() {
  carregandoItens.value = true
  try {
    const { data } = await api.get('/produtos')
    itens.value = data
  } finally {
    carregandoItens.value = false
  }
}

async function carregarPerdas() {
  carregandoPerdas.value = true
  try {
    const { data } = await api.get('/perdas')
    perdas.value = data
  } finally {
    carregandoPerdas.value = false
  }
}

async function carregarEstatisticas() {
  try {
    const { data } = await api.get('/perdas/estatisticas')
    estatisticas.value = data
  } catch {}
}

onMounted(() => {
  carregarItens()
  carregarPerdas()
  carregarEstatisticas()
})
</script>