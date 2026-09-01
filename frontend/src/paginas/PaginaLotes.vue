<template>
  <div class="p-6 min-h-screen bg-white dark:bg-black text-slate-900 dark:text-white">

    <!-- Cabeçalho -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Lotes</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Gerenciamento de lotes por tabs</p>
      </div>
      <button
        v-if="autenticacao.podeCadastrar"
        class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-medium"
        @click="iniciarCriacaoLote"
      >
        <Plus :size="18" />
        Novo Lote
      </button>
    </div>

    <!-- Carregando -->
    <div v-if="carregando" class="text-center py-12 text-slate-500 dark:text-slate-400">
      Carregando lotes...
    </div>

    <!-- Sem lotes -->
    <div v-else-if="lotes.length === 0" class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-16 text-center">
      <PackageMinus class="mx-auto mb-4 text-slate-400 dark:text-slate-600" :size="48" />
      <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Nenhum lote cadastrado</h2>
      <p class="text-slate-500 dark:text-slate-400 mb-6">Crie seu primeiro lote para começar a gerenciar os itens.</p>
      <button
        class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition font-medium flex items-center justify-center gap-2"
        @click="iniciarCriacaoLote"
      >
        <Plus :size="18" />
        Criar Primeiro Lote
      </button>
    </div>

    <!-- TABS DE LOTES -->
    <div v-else class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800">

      <!-- Abas -->
      <div class="flex items-center gap-1 px-4 pt-4 border-b border-slate-200 dark:border-slate-800 overflow-x-auto">
        <button
          v-for="lote in lotes"
          :key="lote.id_lote"
          :class="tabAtiva === lote.id_lote
            ? 'bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-white border-b-2 border-blue-500'
            : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
          class="px-4 py-2 rounded-t-lg text-sm font-medium transition whitespace-nowrap flex items-center gap-1.5"
          @click="aoClicarTab(lote.id_lote)"
          @dragover.prevent
          @drop="aoSoltarNaTab(lote.id_lote)"
        >
          {{ lote.numero_lote }}
        </button>
      </div>

      <!-- Conteúdo da tab ativa -->
      <div v-if="loteAtivo" class="p-6">

        <!-- Cabeçalho do lote -->
        <div class="flex justify-between items-start mb-6">
          <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ loteAtivo.numero_lote }}</h2>
            <div class="flex items-center gap-4 mt-1 text-slate-500 dark:text-slate-400 text-sm">
              <span class="flex items-center gap-1">
                <Calendar :size="14" />
                Criado em: {{ formatarData(loteAtivo.data_entrada) }}
              </span>
              <span class="flex items-center gap-1">
                <Package :size="14" />
                {{ formatNumero(loteAtivo.itens?.length || 0) }} itens
              </span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button
              class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium"
              @click="iniciarAdicaoItem"
            >
              <Plus :size="16" />
              Adicionar Item
            </button>
            <button
              class="flex items-center gap-2 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 px-4 py-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition text-sm font-medium"
              @click="iniciarExclusaoLote"
            >
              <Trash2 :size="16" />
              Excluir Lote
            </button>
          </div>
        </div>

           <!-- Sem itens -->
        <div v-if="!loteAtivo.itens || loteAtivo.itens.length === 0" class="text-center py-16">
          <Package class="mx-auto mb-3 text-slate-400 dark:text-slate-600" :size="40" />
          <p class="text-slate-500 dark:text-slate-500">Nenhum item neste lote</p>
        </div>

        <!-- Tabela de itens (arrastável com o mouse) -->
        <div
          v-else
          ref="tabelaRef"
          class="overflow-x-auto cursor-grab select-none"
          @mousedown="aoIniciar"
          @mousemove="aoMover"
          @mouseup="aoSoltar"
          @mouseleave="aoSoltar"
        
       
>
  <table class="w-full text-sm">
    <thead>
      <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
        <th class="text-left pb-3 font-medium w-6"></th>
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
    <draggable
      :list="itensPaginados"
      tag="tbody"
      item-key="id_item"
      handle=".drag-handle"
      animation="200"
      class="divide-y divide-slate-200 dark:divide-slate-800"
      @start="aoIniciarArraste"
      @end="salvarOrdemItens"
    >
      <template #item="{ element: item }">
        <tr
          class="hover:bg-slate-100 dark:hover:bg-slate-800/50 transition cursor-pointer"
          @click="itemSelecionado = item; modalDetalhesAberto = true"
        >
          <td class="py-3" @click.stop>
            <span class="drag-handle cursor-grab text-slate-400 dark:text-slate-600 hover:text-slate-600 dark:hover:text-slate-400 select-none">⠿</span>
          </td>
          <td class="py-3 text-slate-500 dark:text-slate-400">{{ item.produto?.sku || '—' }}</td>
          <td class="py-3 text-slate-900 dark:text-white font-medium">{{ item.produto?.nome || '—' }}</td>

          <td class="py-3">
            <span :class="item.quantidade === 0 ? 'text-red-600 dark:text-red-400 font-bold' : item.quantidade <= (item.produto?.estoque_minimo ?? 0) ? 'text-yellow-600 dark:text-yellow-400 font-semibold' : 'text-green-600 dark:text-green-400 font-semibold'">
              {{ formatNumero(item.quantidade) }} {{ item.unidade_medida }}
            </span>
          </td>

          <td class="py-3 text-slate-600 dark:text-slate-300">
            <span v-if="item.data_validade">{{ formatarData(item.data_validade) }}</span>
            <span v-else class="text-slate-400 dark:text-slate-500">—</span>
          </td>

          <td class="py-3 text-slate-500 dark:text-slate-400">{{ item.produto?.fornecedor?.nome || '—' }}</td>
          <td class="py-3 text-slate-500 dark:text-slate-400">{{ item.localizacao || '—' }}</td>

          <td class="py-3">
            <div class="flex items-center gap-1 flex-wrap">
              <span v-if="item.data_validade && estaVencido(item.data_validade)" class="px-2 py-0.5 rounded text-xs font-bold bg-red-600 text-white">
                Vencido
              </span>
              <span v-else-if="item.data_validade && proximoDoVencimento(item.data_validade)" class="px-2 py-0.5 rounded text-xs font-bold bg-yellow-600 text-white">
                Vencendo
              </span>
              <span v-else class="px-2 py-0.5 rounded text-xs font-bold bg-green-700 text-white">
                OK
              </span>

              <span v-if="item.quantidade === 0 || item.quantidade <= (item.produto?.estoque_minimo ?? 0)" class="px-2 py-0.5 rounded text-xs font-bold bg-orange-700 text-white">
                Crítico
              </span>
              <span v-else class="px-2 py-0.5 rounded text-xs font-bold bg-green-700 text-white">
                OK
              </span>
            </div>
          </td>

          <td class="py-3" @click.stop>
            <div class="flex items-center gap-3">
              <button class="text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 transition" title="Editar" @click="itemSelecionado = item; modalEditarAberto = true">
                <Pencil :size="16" />
              </button>
              <button class="text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 transition" title="Transferir" @click="iniciarTransferenciaManual(item)">
                <ArrowRightLeft :size="16" />
              </button>
              <button class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-500 dark:hover:text-yellow-300 transition" title="Baixa de estoque" @click="itemSelecionado = item; modalBaixaAberto = true">
                <PackageOpen :size="16" />
              </button>
              <button class="text-green-600 dark:text-green-400 hover:text-green-500 dark:hover:text-green-300 transition" title="Entrada de estoque" @click="itemSelecionado = item; modalEntradaAberto = true">
                <PackagePlus :size="16" />
              </button>
              <button class="text-red-600 dark:text-red-400 hover:text-red-500 dark:hover:text-red-300 transition" title="Excluir" @click="itemSelecionado = item; modalExcluirAberto = true">
                <Trash2 :size="16" />
              </button>
            </div>
          </td>
        </tr>
      </template>
    </draggable>
  </table>
</div>
        <!-- Controles de paginação -->
        <div v-if="totalPaginas > 1" class="flex items-center justify-between mt-4 px-1">
          <p class="text-sm text-slate-500 dark:text-slate-400">
            Página {{ paginaAtual }} de {{ totalPaginas }} — {{ formatNumero(loteAtivo.itens.length) }} itens
          </p>

          <div class="flex items-center gap-1">
            <button
              class="p-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition"
              :disabled="paginaAtual === 1"
              @click="irParaPagina(paginaAtual - 1)"
            >
              <ChevronLeft :size="16" />
            </button>

            <button
              v-for="pagina in totalPaginas"
              :key="pagina"
              :class="pagina === paginaAtual
                ? 'bg-blue-600 text-white'
                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
              class="w-9 h-9 rounded-lg text-sm font-medium transition"
              @click="irParaPagina(pagina)"
            >
              {{ pagina }}
            </button>

            <button
              class="p-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition"
              :disabled="paginaAtual === totalPaginas"
              @click="irParaPagina(paginaAtual + 1)"
            >
              <ChevronRight :size="16" />
            </button>
          </div>

          <select
            v-model="itensPorPagina"
            class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg px-2 py-1.5 text-sm outline-none"
            @change="paginaAtual = 1"
          >
            <option :value="10">10 por página</option>
            <option :value="25">25 por página</option>
            <option :value="50">50 por página</option>
          </select>
        </div>

      </div>
    </div>

    <!-- ===== MODAL CONFIRMAÇÃO EXCLUSÃO DE LOTE ===== -->
    <div v-if="modalExcluirLoteAberto" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-start mb-5">
          <div class="flex items-center gap-2">
            <Shield class="text-red-600 dark:text-red-400" :size="20" />
            <div>
              <h2 class="text-slate-900 dark:text-white font-bold">Excluir Lote</h2>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Esta ação não pode ser desfeita</p>
            </div>
          </div>
          <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="modalExcluirLoteAberto = false">
            <X :size="18" />
          </button>
        </div>

        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
          <p class="text-slate-600 dark:text-slate-300 text-sm">
            Você está excluindo o lote <strong class="text-slate-900 dark:text-white">{{ loteAtivo?.numero_lote }}</strong>. Esta ação não pode ser desfeita.
          </p>
        </div>

        <div class="flex gap-3">
          <button class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition" @click="modalExcluirLoteAberto = false">
            Cancelar
          </button>
          <button class="flex-1 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700 transition font-medium" @click="excluirLote">
            Confirmar Exclusão
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

    <!-- Modal transferir item -->
    <ModalTransferirItem
      v-if="modalTransferirAberto"
      :item="itemParaTransferir"
      :lotes="lotes"
      :lote-destino-inicial="loteDestinoPreSelecionado"
      @fechar="modalTransferirAberto = false"
      @salvo="modalTransferirAberto = false; carregarLotes()"
    />

    <!-- Modal baixa de estoque -->
    <ModalBaixaEstoque
      v-if="modalBaixaAberto"
      :item="itemSelecionado"
      @fechar="modalBaixaAberto = false"
      @salvo="modalBaixaAberto = false; carregarLotes()"
    />

    <!-- Modal entrada de estoque -->
    <ModalEntradaEstoque
      v-if="modalEntradaAberto"
      :item="itemSelecionado"
      @fechar="modalEntradaAberto = false"
      @salvo="modalEntradaAberto = false; carregarLotes()"
    />

    <!-- Modal excluir item -->
    <ModalExcluirItem
      v-if="modalExcluirAberto"
      :item="itemSelecionado"
      @fechar="modalExcluirAberto = false"
      @salvo="modalExcluirAberto = false; carregarLotes()"
    />

    <!-- Modal detalhes produto -->
    <ModalDetalhesProduto
      v-if="modalDetalhesAberto"
      :item="itemSelecionado"
      :lote-numero="loteAtivo?.numero_lote"
      @fechar="modalDetalhesAberto = false"
      @editar="modalDetalhesAberto = false; itemSelecionado = $event; modalEditarAberto = true"
      @baixa="modalDetalhesAberto = false; itemSelecionado = $event; modalBaixaAberto = true"
      @entrada="modalDetalhesAberto = false; itemSelecionado = $event; modalEntradaAberto = true"
      @excluir="modalDetalhesAberto = false; itemSelecionado = $event; modalExcluirAberto = true"
    />

  </div>
</template>

<script setup>
import draggable from 'vuedraggable'
import { ref, computed, onMounted, watch } from 'vue'
import { Plus, Shield, X, PackageMinus, Package, Trash2, Calendar, Pencil, PackageOpen, PackagePlus, ChevronLeft, ChevronRight, ArrowRightLeft } from 'lucide-vue-next'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'
import { useNotificacao } from '@/composables/useNotificacao'
import ModalLote            from '@/componentes/ui/ModalLote.vue'
import ModalAdicionarItem   from '@/componentes/ui/ModalAdicionarItem.vue'
import ModalEditarItem      from '@/componentes/ui/ModalEditarItem.vue'
import ModalTransferirItem  from '@/componentes/ui/ModalTransferirItem.vue'
import ModalExcluirItem     from '@/componentes/ui/ModalExcluirItem.vue'
import ModalDetalhesProduto from '@/componentes/ui/ModalDetalhesProduto.vue'
import ModalBaixaEstoque    from '@/componentes/ui/ModalBaixaEstoque.vue'
import ModalEntradaEstoque  from '@/componentes/ui/ModalEntradaEstoque.vue'
import { formatarData, estaVencido, proximoDoVencimento } from '@/utils/date'
import { useArrastarParaRolar } from '@/composables/useArrastarParaRolar'

const { elementoRef: tabelaRef, aoIniciar, aoMover, aoSoltar } = useArrastarParaRolar()
const autenticacao        = useAutenticacaoStore()
const { sucesso, erro }   = useNotificacao()
const lotes               = ref([])
const carregando          = ref(false)
const modalAberto         = ref(false)
const modalItemAberto     = ref(false)
const modalEditarAberto   = ref(false)
const modalDetalhesAberto = ref(false)
const modalBaixaAberto    = ref(false)
const modalEntradaAberto  = ref(false)
const modalExcluirAberto  = ref(false)
const loteSelecionado     = ref(null)
const itemSelecionado     = ref(null)
const tabAtiva            = ref(null)

// ===== Transferência de item entre lotes =====
const modalTransferirAberto     = ref(false)
const itemArrastado             = ref(null)
const itemParaTransferir        = ref(null)
const loteDestinoPreSelecionado = ref(null)

// Formata números com separador de milhar no padrão brasileiro (ex: 17050 -> 17.050)
function formatNumero(valor) {
  return Number(valor ?? 0).toLocaleString('pt-BR')
}

const loteAtivo = computed(() => lotes.value.find(l => l.id_lote === tabAtiva.value) || null)

// ===== Paginação de itens =====
const itensPorPagina = ref(10)
const paginaAtual = ref(1)

const totalPaginas = computed(() => {
  if (!loteAtivo.value?.itens) return 1
  return Math.max(1, Math.ceil(loteAtivo.value.itens.length / itensPorPagina.value))
})

const itensPaginados = computed(() => {
  if (!loteAtivo.value?.itens) return []
  const inicio = (paginaAtual.value - 1) * itensPorPagina.value
  return loteAtivo.value.itens.slice(inicio, inicio + itensPorPagina.value)
})

function irParaPagina(pagina) {
  if (pagina < 1 || pagina > totalPaginas.value) return
  paginaAtual.value = pagina
}

// reseta a página quando troca de lote
watch(tabAtiva, () => {
  paginaAtual.value = 1
})

// ===== Troca de tab (sem PIN) =====
function aoClicarTab(idLote) {
  tabAtiva.value = idLote
}

// ===== Ações (criar lote / adicionar item) =====
function iniciarCriacaoLote() {
  loteSelecionado.value = null
  modalAberto.value = true
}

function iniciarAdicaoItem() {
  modalItemAberto.value = true
}

// ===== Transferência de item entre lotes =====
function aoIniciarArraste(evt) {
  itemArrastado.value = itensPaginados.value[evt.oldIndex]
}

function aoSoltarNaTab(idLoteDestino) {
  if (!itemArrastado.value || idLoteDestino === tabAtiva.value) return
  itemParaTransferir.value = itemArrastado.value
  loteDestinoPreSelecionado.value = idLoteDestino
  modalTransferirAberto.value = true
  itemArrastado.value = null
}

function iniciarTransferenciaManual(item) {
  itemParaTransferir.value = item
  loteDestinoPreSelecionado.value = null
  modalTransferirAberto.value = true
}

// ===== Exclusão de lote (confirmação simples) =====
const modalExcluirLoteAberto = ref(false)

function iniciarExclusaoLote() {
  modalExcluirLoteAberto.value = true
}

async function excluirLote() {
  try {
    await api.delete(`/lotes/${loteAtivo.value.id_lote}`)
    tabAtiva.value = null
    modalExcluirLoteAberto.value = false
    sucesso('Lote excluído com sucesso.')
    await carregarLotes()
  } catch {
    erro('Erro ao excluir lote.')
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
async function salvarOrdemItens() {
  if (!loteAtivo.value?.itens) return

  const itens = itensPaginados.value.map((item, index) => ({
    id_item: item.id_item,
    ordem:   index,
  }))

  try {
    await api.patch(`/lotes/${loteAtivo.value.id_lote}/itens/reordenar`, { itens })
  } catch {
    erro('Não foi possível salvar a nova ordem dos itens.')
    await carregarLotes()
  }
}
async function carregarLotes() {
  carregando.value = true
  try {
    const resposta = await api.get('/lotes')
    lotes.value = resposta.data
    if (lotes.value.length > 0 && !tabAtiva.value) {
      tabAtiva.value = lotes.value[0].id_lote
    }
  } catch {
    erro('Não foi possível carregar os lotes.')
  } finally {
    carregando.value = false
  }
}

onMounted(carregarLotes)
</script>