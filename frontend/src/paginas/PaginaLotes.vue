<template>
  <div class="p-6 min-h-screen bg-black text-white">

    <!-- Cabeçalho -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-white">Lotes</h1>
        <p class="text-sm text-slate-400">Gerenciamento de lotes por tabs</p>
      </div>
      <button
        v-if="autenticacao.podeCadastrar"
        @click="iniciarCriacaoLote"
        class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-medium"
      >
        <Plus :size="18" />
        Novo Lote
      </button>
    </div>

    <!-- Carregando -->
    <div v-if="carregando" class="text-center py-12 text-slate-400">
      Carregando lotes...
    </div>

    <!-- Sem lotes -->
    <div v-else-if="lotes.length === 0" class="rounded-xl bg-slate-900 border border-slate-800 p-16 text-center">
      <PackageMinus class="mx-auto mb-4 text-slate-600" :size="48" />
      <h2 class="text-xl font-bold text-white mb-2">Nenhum lote cadastrado</h2>
      <p class="text-slate-400 mb-6">Crie seu primeiro lote para começar a gerenciar os itens.</p>
      <button
        @click="iniciarCriacaoLote"
        class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition font-medium flex items-center justify-center gap-2"
      >
        <Plus :size="18" />
        Criar Primeiro Lote
      </button>
    </div>

    <!-- TABS DE LOTES -->
    <div v-else class="rounded-xl bg-slate-900 border border-slate-800">

      <!-- Abas -->
      <div class="flex items-center gap-1 px-4 pt-4 border-b border-slate-800 overflow-x-auto">
        <button
          v-for="lote in lotes"
          :key="lote.id_lote"
          @click="tabAtiva = lote.id_lote"
          :class="tabAtiva === lote.id_lote
            ? 'bg-slate-700 text-white border-b-2 border-blue-500'
            : 'text-slate-400 hover:text-white hover:bg-slate-800'"
          class="px-4 py-2 rounded-t-lg text-sm font-medium transition whitespace-nowrap"
        >
          {{ lote.numero_lote }}
        </button>
      </div>

      <!-- Conteúdo da tab ativa -->
      <div v-if="loteAtivo" class="p-6">

        <!-- Cabeçalho do lote -->
        <div class="flex justify-between items-start mb-6">
          <div>
            <h2 class="text-xl font-bold text-white">{{ loteAtivo.numero_lote }}</h2>
            <div class="flex items-center gap-4 mt-1 text-slate-400 text-sm">
              <span class="flex items-center gap-1">
                <Calendar :size="14" />
                Criado em: {{ formatarData(loteAtivo.data_entrada) }}
              </span>
              <span class="flex items-center gap-1">
                <Package :size="14" />
                {{ loteAtivo.itens?.length || 0 }} itens
              </span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="iniciarAdicaoItem"
              class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium"
            >
              <Plus :size="16" />
              Adicionar Item
            </button>
            <button
              @click="iniciarExclusaoLote"
              class="flex items-center gap-2 border border-red-700 text-red-400 px-4 py-2 rounded-lg hover:bg-red-900/20 transition text-sm font-medium"
            >
              <Trash2 :size="16" />
              Excluir Lote
            </button>
          </div>
        </div>

        <!-- Sem itens -->
        <div v-if="!loteAtivo.itens || loteAtivo.itens.length === 0" class="text-center py-16">
          <Package class="mx-auto mb-3 text-slate-600" :size="40" />
          <p class="text-slate-500">Nenhum item neste lote</p>
        </div>

        <!-- Tabela de itens -->
        <table v-else class="w-full text-sm">
          <thead>
            <tr class="text-slate-400 border-b border-slate-800">
              <th class="text-left pb-3 font-medium">SKU</th>
              <th class="text-left pb-3 font-medium">Nome</th>
              <th class="text-left pb-3 font-medium">Qtd</th>
              <th class="text-left pb-3 font-medium">Validade</th>
              <th class="text-left pb-3 font-medium">Fornecedor</th>
              <th class="text-left pb-3 font-medium">Localização</th>
              <th class="text-left pb-3 font-medium">Status</th>
              <th class="text-left pb-3 font-medium">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            <tr v-for="item in loteAtivo.itens" :key="item.id_item" class="hover:bg-slate-800/50 transition">

              <td class="py-3 text-slate-400">{{ item.sku || '—' }}</td>
              <td class="py-3 text-white font-medium">{{ item.nome || '—' }}</td>

              <td class="py-3">
                <span :class="item.quantidade === 0 ? 'text-red-400 font-bold' : item.quantidade <= item.estoque_minimo ? 'text-yellow-400 font-semibold' : 'text-green-400 font-semibold'">
                  {{ item.quantidade }} {{ item.unidade_medida }}
                </span>
              </td>

              <td class="py-3 text-slate-300">
                <span v-if="item.data_validade">{{ formatarData(item.data_validade) }}</span>
                <span v-else class="text-slate-500">—</span>
              </td>

              <td class="py-3 text-slate-400">{{ item.fornecedor || '—' }}</td>
              <td class="py-3 text-slate-400">{{ item.localizacao || '—' }}</td>

              <td class="py-3">
                <div class="flex items-center gap-1 flex-wrap">
                  <span v-if="item.data_validade && estaVencido(item.data_validade)"
                    class="px-2 py-0.5 rounded text-xs font-bold bg-red-600 text-white">
                    Vencido
                  </span>
                  <span v-else-if="item.data_validade && proximoDoVencimento(item.data_validade)"
                    class="px-2 py-0.5 rounded text-xs font-bold bg-yellow-600 text-white">
                    Vencendo
                  </span>
                  <span v-if="item.quantidade === 0 || item.quantidade <= item.estoque_minimo"
                    class="px-2 py-0.5 rounded text-xs font-bold bg-orange-700 text-white">
                    Crítico
                  </span>
                  <span v-if="item.prioridade_abc"
                    :class="{
                      'bg-red-900/40 text-red-400 border border-red-800':         item.prioridade_abc === 'A',
                      'bg-yellow-900/40 text-yellow-400 border border-yellow-800': item.prioridade_abc === 'B',
                      'bg-green-900/40 text-green-400 border border-green-800':    item.prioridade_abc === 'C',
                    }"
                    class="px-2 py-0.5 rounded-full text-xs font-bold">
                    {{ item.prioridade_abc }}
                  </span>
                </div>
              </td>

              <td class="py-3">
                <div class="flex items-center gap-3">
                  <button @click="itemSelecionado = item; modalEditarAberto = true"
                    class="text-blue-400 hover:text-blue-300 transition" title="Editar">
                    <Pencil :size="16" />
                  </button>
                  <button @click="itemSelecionado = item; modalBaixaAberto = true"
                    class="text-yellow-400 hover:text-yellow-300 transition" title="Baixa de estoque">
                    <PackageOpen :size="16" />
                  </button>
                  <button class="text-green-400 hover:text-green-300 transition" title="Adicionar estoque">
                    <PackagePlus :size="16" />
                  </button>
                  <button class="text-red-400 hover:text-red-300 transition" title="Excluir">
                    <Trash2 :size="16" />
                  </button>
                </div>
              </td>

            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ===== MODAL ETAPA 1: CONFIRMAÇÃO ===== -->
    <div v-if="etapa === 1" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
      <div class="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-start mb-5">
          <div class="flex items-center gap-2">
            <Shield class="text-blue-400" :size="20" />
            <div>
              <h2 class="text-white font-bold">Confirmação de Segurança</h2>
              <p class="text-slate-400 text-xs">Esta ação requer confirmação em 2 etapas</p>
            </div>
          </div>
          <button @click="fecharTudo" class="text-slate-400 hover:text-white">
            <X :size="18" />
          </button>
        </div>

        <div class="bg-blue-900/30 border border-blue-700 rounded-lg p-4 mb-3">
          <div class="flex items-center gap-2 mb-1">
            <Shield class="text-blue-400" :size="16" />
            <span class="text-white font-medium">{{ acaoAtual === 'criar' ? 'Criar Novo Lote' : acaoAtual === 'item' ? 'Adicionar Item' : 'Excluir Lote' }}</span>
          </div>
          <p class="text-slate-300 text-sm">
            {{ acaoAtual === 'criar'
              ? 'Você está criando um novo lote no sistema. Um número sequencial será gerado automaticamente.'
              : acaoAtual === 'item'
                ? 'Você está adicionando um item ao lote ' + loteAtivo?.numero_lote + '.'
                : 'Você está excluindo o lote ' + loteAtivo?.numero_lote + '. Esta ação não pode ser desfeita.' }}
          </p>
        </div>

        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 mb-6">
          <div class="flex items-center gap-2 mb-1">
            <Lock :size="16" class="text-slate-400" />
            <span class="text-white font-medium text-sm">Etapa 1 de 2: Confirmação</span>
          </div>
          <p class="text-slate-400 text-sm">Confirme para prosseguir para a etapa de verificação PIN.</p>
        </div>

        <div class="flex gap-3">
          <button @click="fecharTudo" class="flex-1 py-2.5 rounded-lg border border-slate-600 text-slate-300 hover:bg-slate-800 transition">
            Cancelar
          </button>
          <button @click="etapa = 2" class="flex-1 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition font-medium">
            Confirmar e Prosseguir
          </button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL ETAPA 2: PIN ===== -->
    <div v-if="etapa === 2" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
      <div class="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-start mb-5">
          <div class="flex items-center gap-2">
            <Lock class="text-blue-400" :size="20" />
            <div>
              <h2 class="text-white font-bold">Verificação de PIN</h2>
              <p class="text-slate-400 text-xs">Etapa 2 de 2: Digite o PIN de segurança</p>
            </div>
          </div>
          <button @click="fecharTudo" class="text-slate-400 hover:text-white">
            <X :size="18" />
          </button>
        </div>

        <div class="mb-6">
          <label class="block text-sm text-slate-400 mb-2">PIN de Segurança</label>
          <input
            v-model="pinDigitado"
            type="password"
            maxlength="4"
            placeholder="••••"
            class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-3 text-center text-2xl tracking-widest outline-none focus:border-blue-500 transition"
            @keyup.enter="verificarPin"
          />
          <p v-if="erroPin" class="text-red-400 text-sm mt-2 text-center">{{ erroPin }}</p>
        </div>

        <div class="flex gap-3">
          <button @click="etapa = 1; pinDigitado = ''; erroPin = ''" class="flex-1 py-2.5 rounded-lg border border-slate-600 text-slate-300 hover:bg-slate-800 transition">
            Voltar
          </button>
          <button @click="verificarPin" class="flex-1 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition font-medium">
            Confirmar PIN
          </button>
        </div>
      </div>
    </div>

    <!-- Modal criar/editar lote -->
    <ModalLote
      v-if="modalAberto"
      :lote="loteSelecionado"
      @fechar="fecharModal"
      @salvo="aoSalvar"
    />

    <!-- Modal adicionar item -->
    <ModalAdicionarItem
      v-if="modalItemAberto"
      :lote="loteAtivo"
      @fechar="modalItemAberto = false"
      @salvo="modalItemAberto = false; carregarLotes()"
    />

    <!-- Modal editar item -->
    <ModalEditarItem
      v-if="modalEditarAberto"
      :item="itemSelecionado"
      @fechar="modalEditarAberto = false"
      @salvo="modalEditarAberto = false; carregarLotes()"
    />

    <!-- Modal baixa de estoque -->
    <ModalBaixaEstoque
      v-if="modalBaixaAberto"
      :item="itemSelecionado"
      @fechar="modalBaixaAberto = false"
      @salvo="modalBaixaAberto = false; carregarLotes()"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Plus, Shield, Lock, X, PackageMinus, Package, Trash2, Calendar, Pencil, PackageOpen, PackagePlus } from 'lucide-vue-next'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'
import ModalLote from '@/componentes/ui/ModalLote.vue'
import ModalAdicionarItem from '@/componentes/ui/ModalAdicionarItem.vue'
import ModalEditarItem from '@/componentes/ui/ModalEditarItem.vue'
import ModalBaixaEstoque from '@/componentes/ui/ModalBaixaEstoque.vue'
import { formatarData, estaVencido, proximoDoVencimento } from '@/utils/date'

const autenticacao      = useAutenticacaoStore()
const lotes             = ref([])
const carregando        = ref(false)
const modalAberto       = ref(false)
const modalItemAberto   = ref(false)
const modalEditarAberto = ref(false)
const modalBaixaAberto  = ref(false)
const loteSelecionado   = ref(null)
const itemSelecionado   = ref(null)
const tabAtiva          = ref(null)

const loteAtivo = computed(() => lotes.value.find(l => l.id_lote === tabAtiva.value) || null)

const etapa       = ref(0)
const pinDigitado = ref('')
const erroPin     = ref('')
const acaoAtual   = ref('')
const PIN_CORRETO = '8401'

function iniciarCriacaoLote() {
  acaoAtual.value   = 'criar'
  etapa.value       = 1
  pinDigitado.value = ''
  erroPin.value     = ''
}

function iniciarAdicaoItem() {
  acaoAtual.value   = 'item'
  etapa.value       = 1
  pinDigitado.value = ''
  erroPin.value     = ''
}

function iniciarExclusaoLote() {
  acaoAtual.value   = 'excluir'
  etapa.value       = 1
  pinDigitado.value = ''
  erroPin.value     = ''
}

function verificarPin() {
  if (pinDigitado.value === PIN_CORRETO) {
    etapa.value = 0
    if (acaoAtual.value === 'criar') {
      loteSelecionado.value = null
      modalAberto.value     = true
    } else if (acaoAtual.value === 'item') {
      modalItemAberto.value = true
    } else if (acaoAtual.value === 'excluir') {
      excluirLote()
    }
  } else {
    erroPin.value     = 'PIN incorreto. Tente novamente.'
    pinDigitado.value = ''
  }
}

function fecharTudo() {
  etapa.value       = 0
  pinDigitado.value = ''
  erroPin.value     = ''
  acaoAtual.value   = ''
}

async function excluirLote() {
  try {
    await api.delete(`/lotes/${loteAtivo.value.id_lote}`)
    await carregarLotes()
  } catch {
    alert('Erro ao excluir lote.')
  }
}

function fecharModal() {
  modalAberto.value     = false
  loteSelecionado.value = null
}

async function aoSalvar() {
  fecharModal()
  await carregarLotes()
}

async function carregarLotes() {
  carregando.value = true
  try {
    const resposta = await api.get('/lotes')
    lotes.value = resposta.data
    if (lotes.value.length > 0 && !tabAtiva.value) {
      tabAtiva.value = lotes.value[lotes.value.length - 1].id_lote
    }
  } catch {
    alert('Não foi possível carregar os lotes.')
  } finally {
    carregando.value = false
  }
}

onMounted(carregarLotes)
</script>