<template>
  <div class="p-6">

    <!-- Cabeçalho -->
    <div class="flex justify-between items-center mb-1">
      <h1 class="text-2xl font-bold text-white">Produtos</h1>
      <button
        v-if="autenticacao.podeCadastrar"
        @click="abrirModalNovoProduto"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm"
      >
        + Novo Produto
      </button>
    </div>
    <p class="text-slate-400 text-sm mb-6">Visão geral de todos os produtos e seus lotes</p>

    <!-- Filtros -->
    <div class="bg-slate-800 rounded-xl p-4 mb-6 flex flex-col gap-3">
      <input
        v-model="termoDeBusca"
        type="text"
        placeholder="🔍 Buscar por nome ou SKU..."
        class="w-full bg-slate-700 text-white placeholder-slate-400 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500"
        @input="buscarComAtraso"
      />
      <button
        @click="toggleEstoqueBaixo"
        :class="[
          'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors w-fit',
          filtroBaixo
            ? 'bg-orange-500 text-white'
            : 'bg-slate-700 text-slate-300 hover:bg-slate-600'
        ]"
      >
        ⬇ Estoque Baixo
      </button>
    </div>

    <!-- Carregando -->
    <div v-if="carregando" class="text-center py-12 text-slate-400">
      Carregando produtos...
    </div>

    <!-- Vazio -->
    <div v-else-if="produtosFiltrados.length === 0" class="text-center py-12 text-slate-400">
      Nenhum produto encontrado.
    </div>

    <!-- Tabela -->
    <div v-else class="bg-slate-800 rounded-xl overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-slate-700 text-slate-400 text-left">
            <th class="px-4 py-3">SKU</th>
            <th class="px-4 py-3">Nome do Produto</th>
            <th class="px-4 py-3">Quantidade Total</th>
            <th class="px-4 py-3">Próxima Validade</th>
            <th class="px-4 py-3">Lotes</th>
            <th class="px-4 py-3">Status Validade</th>
            <th class="px-4 py-3">Status Estoque</th>
            <th class="px-4 py-3">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in produtosFiltrados"
            :key="item.id_item"
            class="border-b border-slate-700 hover:bg-slate-700/50 transition-colors"
          >
            <td class="px-4 py-3 text-slate-300 font-mono text-xs">{{ item.sku ?? '—' }}</td>
            <td class="px-4 py-3 text-white font-medium">{{ item.nome }}</td>
            <td class="px-4 py-3" :class="estoqueBaixo(item) ? 'text-orange-400 font-bold' : 'text-white'">
              {{ item.quantidade }} {{ item.unidade_medida }}
            </td>
            <td class="px-4 py-3 text-slate-300">{{ formatarData(item.data_validade) }}</td>
            <td class="px-4 py-3">
              <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded font-medium">
                {{ item.lote?.numero_lote ?? 'Lote ' + item.id_lote }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span :class="badgeValidade(item)">
                {{ labelValidade(item) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span :class="estoqueBaixo(item)
                ? 'bg-orange-500/20 text-orange-400 px-2 py-1 rounded text-xs font-semibold'
                : 'bg-green-500/20 text-green-400 px-2 py-1 rounded text-xs font-semibold'"
              >
                {{ estoqueBaixo(item) ? '↓ Baixo' : 'OK' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <button
                v-if="autenticacao.podeCadastrar"
                @click="abrirModalEdicao(item)"
                class="text-blue-400 hover:text-blue-300 text-xs mr-3 transition-colors"
              >
                Editar
              </button>
              <button
                v-if="autenticacao.ehAdmin"
                @click="desativarProduto(item)"
                class="text-red-400 hover:text-red-300 transition-colors"
                title="Excluir"
              >
                🗑
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Rodapé -->
    <div class="grid grid-cols-3 gap-4 mt-6">
      <div class="bg-slate-800 rounded-xl p-4">
        <p class="text-slate-400 text-xs mb-1">Total de Produtos Únicos</p>
        <p class="text-white text-2xl font-bold">{{ produtos.length }}</p>
      </div>
      <div class="bg-slate-800 rounded-xl p-4">
        <p class="text-slate-400 text-xs mb-1">Produtos Vencendo</p>
        <p class="text-orange-400 text-2xl font-bold">{{ totalVencendo }}</p>
      </div>
      <div class="bg-slate-800 rounded-xl p-4">
        <p class="text-slate-400 text-xs mb-1">Produtos Vencidos</p>
        <p class="text-red-400 text-2xl font-bold">{{ totalVencidos }}</p>
      </div>
    </div>

    <!-- Modal -->
    <ModalProduto
      v-if="modalAberto"
      :produto="produtoSelecionado"
      @fechar="fecharModal"
      @salvo="aoSalvarProduto"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'
import ModalProduto from '@/componentes/produtos/ModalProduto.vue'

const autenticacao       = useAutenticacaoStore()
const produtos           = ref([])
const carregando         = ref(false)
const termoDeBusca       = ref('')
const filtroBaixo        = ref(false)
const modalAberto        = ref(false)
const produtoSelecionado = ref(null)

let temporizadorBusca = null

const estoqueBaixo = (item) => item.quantidade <= item.estoque_minimo

const diasParaVencer = (data) => {
  if (!data) return null
  const diff = new Date(data) - new Date()
  return Math.ceil(diff / (1000 * 60 * 60 * 24))
}

const labelValidade = (item) => {
  const dias = diasParaVencer(item.data_validade)
  if (dias === null) return '—'
  if (dias < 0) return 'Vencido'
  if (dias <= 30) return `${dias}d`
  return 'OK'
}

const badgeValidade = (item) => {
  const dias = diasParaVencer(item.data_validade)
  const base = 'px-2 py-1 rounded text-xs font-semibold'
  if (dias === null) return `${base} text-slate-400`
  if (dias < 0)     return `${base} bg-red-500/20 text-red-400`
  if (dias <= 30)   return `${base} bg-orange-500/20 text-orange-400`
  return `${base} bg-green-500/20 text-green-400`
}

const formatarData = (data) => {
  if (!data) return '—'
  const dias = diasParaVencer(data)
  const dataFmt = new Date(data).toLocaleDateString('pt-BR')
  return dias !== null ? `${dataFmt} (${dias}d)` : dataFmt
}

const produtosFiltrados = computed(() => {
  return produtos.value.filter(item => {
    const termo = termoDeBusca.value.toLowerCase()
    const buscaOk = !termo ||
      item.nome?.toLowerCase().includes(termo) ||
      item.sku?.toLowerCase().includes(termo)
    const baixoOk = !filtroBaixo.value || estoqueBaixo(item)
    return buscaOk && baixoOk
  })
})

const totalVencendo = computed(() =>
  produtos.value.filter(i => {
    const dias = diasParaVencer(i.data_validade)
    return dias !== null && dias >= 0 && dias <= 30
  }).length
)

const totalVencidos = computed(() =>
  produtos.value.filter(i => {
    const dias = diasParaVencer(i.data_validade)
    return dias !== null && dias < 0
  }).length
)

const toggleEstoqueBaixo = () => { filtroBaixo.value = !filtroBaixo.value }

async function carregarProdutos() {
  carregando.value = true
  try {
    const { data } = await api.get('/produtos')
    produtos.value = data
  } catch {
    alert('Não foi possível carregar os produtos.')
  } finally {
    carregando.value = false
  }
}

function buscarComAtraso() {
  clearTimeout(temporizadorBusca)
  temporizadorBusca = setTimeout(carregarProdutos, 400)
}

function abrirModalNovoProduto() {
  produtoSelecionado.value = null
  modalAberto.value = true
}

function abrirModalEdicao(produto) {
  produtoSelecionado.value = produto
  modalAberto.value = true
}

function fecharModal() {
  modalAberto.value = false
  produtoSelecionado.value = null
}

function aoSalvarProduto() {
  fecharModal()
  carregarProdutos()
}

async function desativarProduto(item) {
  if (!confirm(`Tem certeza que deseja remover "${item.nome}"?`)) return
  try {
    await api.delete(`/produtos/${item.id_item}`)
    produtos.value = produtos.value.filter(i => i.id_item !== item.id_item)
  } catch {
    alert('Não foi possível remover o produto.')
  }
}

onMounted(carregarProdutos)
</script>