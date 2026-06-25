<template>
  <div class="p-6 min-h-screen bg-black text-white">

    <!-- Cabeçalho -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-white">Relatórios</h1>
      <p class="text-sm text-slate-400">Filtros, visualização e exportação de dados</p>
    </div>

    <!-- Filtros -->
    <div class="rounded-xl bg-slate-900 border border-slate-800 p-5 mb-6">
      <div class="flex items-center gap-2 mb-4">
        <Filter :size="16" class="text-blue-400" />
        <span class="font-semibold text-white">Filtros</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <!-- Lote -->
        <div>
          <label class="block text-sm font-medium text-white mb-2">Lote</label>
          <div class="relative">
            <button @click="dropdownLoteAberto = !dropdownLoteAberto"
              class="w-full flex items-center justify-between bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white">
              {{ filtros.lote }} <ChevronDown :size="16" class="text-slate-400" />
            </button>
            <div v-if="dropdownLoteAberto" class="absolute z-10 mt-1 w-full bg-slate-800 border border-slate-700 rounded-lg shadow-lg overflow-hidden max-h-56 overflow-y-auto">
              <div v-for="opcao in opcoesLote" :key="opcao" @click="filtros.lote = opcao; dropdownLoteAberto = false"
                class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer transition"
                :class="filtros.lote === opcao ? 'bg-blue-600 text-white' : 'text-slate-200 hover:bg-slate-700'">
                {{ opcao }} <Check v-if="filtros.lote === opcao" :size="16" />
              </div>
            </div>
          </div>
        </div>

        <!-- Vencimento -->
        <div>
          <label class="block text-sm font-medium text-white mb-2">Vencimento em até</label>
          <div class="relative">
            <button @click="dropdownVencAberto = !dropdownVencAberto"
              class="w-full flex items-center justify-between bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white">
              {{ filtros.vencimento }} <ChevronDown :size="16" class="text-slate-400" />
            </button>
            <div v-if="dropdownVencAberto" class="absolute z-10 mt-1 w-full bg-slate-800 border border-slate-700 rounded-lg shadow-lg overflow-hidden">
              <div v-for="opcao in opcoesVencimento" :key="opcao" @click="filtros.vencimento = opcao; dropdownVencAberto = false"
                class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer transition"
                :class="filtros.vencimento === opcao ? 'bg-blue-600 text-white' : 'text-slate-200 hover:bg-slate-700'">
                {{ opcao }} <Check v-if="filtros.vencimento === opcao" :size="16" />
              </div>
            </div>
          </div>
        </div>

        <!-- Busca -->
        <div>
          <label class="block text-sm font-medium text-white mb-2">Buscar</label>
          <div class="relative">
            <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" />
            <input v-model="filtros.busca" type="text" placeholder="SKU, produto, fornecedor..."
              class="w-full bg-slate-800 border border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 outline-none focus:border-blue-500 transition" />
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-white mb-2">Data Inicial</label>
          <input v-model="filtros.dataInicial" type="date"
            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-blue-500 transition [color-scheme:dark]" />
        </div>
        <div>
          <label class="block text-sm font-medium text-white mb-2">Data Final</label>
          <input v-model="filtros.dataFinal" type="date"
            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-blue-500 transition [color-scheme:dark]" />
        </div>
      </div>

      <div class="flex items-center justify-between">
        <button @click="limparFiltros"
          class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
          Limpar Filtros
        </button>
        <div class="flex gap-2">
          <button @click="exportarCSV" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            <Download :size="16" /> Exportar CSV
          </button>
          <button @click="exportarExcel" class="flex items-center gap-2 bg-green-700 hover:bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            <FileSpreadsheet :size="16" /> Exportar Excel
          </button>
          <button @click="exportarPDF" class="flex items-center gap-2 bg-red-700 hover:bg-red-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            <FileText :size="16" /> Exportar PDF
          </button>
        </div>
      </div>
    </div>

    <!-- Cards de resumo -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
        <p class="text-slate-400 text-sm mb-2">Itens Filtrados</p>
        <p class="text-3xl font-bold text-white">{{ itensFiltrados.length }}</p>
      </div>
      <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
        <p class="text-slate-400 text-sm mb-2">Itens Vencendo</p>
        <p class="text-3xl font-bold text-yellow-400">{{ totalVencendo }}</p>
      </div>
      <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
        <p class="text-slate-400 text-sm mb-2">Itens Vencidos</p>
        <p class="text-3xl font-bold text-red-400">{{ totalVencidos }}</p>
      </div>
    </div>

    <!-- Tabela -->
    <div class="rounded-xl bg-slate-900 border border-slate-800">
      <div class="px-5 py-4 border-b border-slate-800">
        <h2 class="font-semibold text-white">Resultados ({{ itensFiltrados.length }} itens)</h2>
      </div>

      <div v-if="carregando" class="text-center py-12 text-slate-500">Carregando...</div>

      <div v-else-if="itensFiltrados.length === 0" class="text-center py-12 text-slate-500">
        Nenhum item encontrado.
      </div>

      <table v-else class="w-full text-sm">
        <thead>
          <tr class="text-slate-400 border-b border-slate-800">
            <th class="text-left px-4 py-3 font-medium">Lote</th>
            <th class="text-left px-4 py-3 font-medium">SKU</th>
            <th class="text-left px-4 py-3 font-medium">Produto</th>
            <th class="text-left px-4 py-3 font-medium">Qtd</th>
            <th class="text-left px-4 py-3 font-medium">Validade</th>
            <th class="text-left px-4 py-3 font-medium">Fornecedor</th>
            <th class="text-left px-4 py-3 font-medium">Localização</th>
            <th class="text-left px-4 py-3 font-medium">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <tr v-for="item in itensFiltrados" :key="item.id_item" class="hover:bg-slate-800/50 transition">
            <td class="px-4 py-3">
              <span class="bg-blue-600 text-white text-xs font-bold px-2 py-0.5 rounded">
                {{ item.lote?.numero_lote ?? '—' }}
              </span>
            </td>
            <td class="px-4 py-3 text-slate-400">{{ item.sku }}</td>
            <td class="px-4 py-3 text-white font-medium">{{ item.nome }}</td>
            <td class="px-4 py-3 text-white">{{ item.quantidade }}</td>
            <td class="px-4 py-3 text-slate-300">{{ item.data_validade ? formatarData(item.data_validade) : '—' }}</td>
            <td class="px-4 py-3 text-slate-400">{{ item.fornecedor || '—' }}</td>
            <td class="px-4 py-3 text-slate-400">{{ item.localizacao || '—' }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded text-xs font-bold"
                :class="statusClasse(item)">
                {{ statusTexto(item) }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/servicos/api'
import { formatarData } from '@/utils/date'
import { Filter, Search, ChevronDown, Check, Download, FileSpreadsheet, FileText } from 'lucide-vue-next'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

const carregando = ref(false)
const itens      = ref([])
const dropdownLoteAberto = ref(false)
const dropdownVencAberto  = ref(false)

const opcoesVencimento = ['Todos', 'Vencidos', 'Vencendo em 7 dias', 'Vencendo em 30 dias', 'Vencendo em 90 dias']
const opcoesLote = ref(['Todos os lotes'])

const filtros = ref({
  busca: '',
  lote: 'Todos os lotes',
  vencimento: 'Todos',
  dataInicial: '',
  dataFinal: '',
})

onMounted(async () => {
  carregando.value = true
  try {
    const { data } = await api.get('/relatorios/itens')
    itens.value = data
    const lotes = [...new Set(data.map(i => i.lote?.numero_lote).filter(Boolean))]
    opcoesLote.value = ['Todos os lotes', ...lotes]
  } finally {
    carregando.value = false
  }
})

const itensFiltrados = computed(() => {
  const hoje = new Date()
  return itens.value.filter(item => {
    const buscaOk = !filtros.value.busca ||
      item.nome?.toLowerCase().includes(filtros.value.busca.toLowerCase()) ||
      item.sku?.toLowerCase().includes(filtros.value.busca.toLowerCase()) ||
      item.fornecedor?.toLowerCase().includes(filtros.value.busca.toLowerCase())

    const loteOk = filtros.value.lote === 'Todos os lotes' ||
      item.lote?.numero_lote === filtros.value.lote

    const validade = item.data_validade ? new Date(item.data_validade) : null
    let vencOk = true
    if (filtros.value.vencimento === 'Vencidos') {
      vencOk = validade && validade < hoje
    } else if (filtros.value.vencimento === 'Vencendo em 7 dias') {
      const limite = new Date(); limite.setDate(hoje.getDate() + 7)
      vencOk = validade && validade >= hoje && validade <= limite
    } else if (filtros.value.vencimento === 'Vencendo em 30 dias') {
      const limite = new Date(); limite.setDate(hoje.getDate() + 30)
      vencOk = validade && validade >= hoje && validade <= limite
    } else if (filtros.value.vencimento === 'Vencendo em 90 dias') {
      const limite = new Date(); limite.setDate(hoje.getDate() + 90)
      vencOk = validade && validade >= hoje && validade <= limite
    }

    const dataItem = item.lote?.data_entrada
    const dataInicialOk = !filtros.value.dataInicial || (dataItem && dataItem >= filtros.value.dataInicial)
    const dataFinalOk   = !filtros.value.dataFinal   || (dataItem && dataItem <= filtros.value.dataFinal)

    return buscaOk && loteOk && vencOk && dataInicialOk && dataFinalOk
  })
})

const totalVencidos  = computed(() => {
  const hoje = new Date()
  return itensFiltrados.value.filter(i => i.data_validade && new Date(i.data_validade) < hoje).length
})

const totalVencendo = computed(() => {
  const hoje  = new Date()
  const limit = new Date(); limit.setDate(hoje.getDate() + 30)
  return itensFiltrados.value.filter(i => {
    if (!i.data_validade) return false
    const v = new Date(i.data_validade)
    return v >= hoje && v <= limit
  }).length
})

function diasParaVencer(dataValidade) {
  if (!dataValidade) return null
  const hoje = new Date()
  const val  = new Date(dataValidade)
  return Math.ceil((val - hoje) / (1000 * 60 * 60 * 24))
}

function statusTexto(item) {
  if (!item.data_validade) return 'OK'
  const dias = diasParaVencer(item.data_validade)
  if (dias < 0)   return 'Vencido'
  if (dias <= 30) return `Vencendo (${dias}d)`
  return 'OK'
}

function statusClasse(item) {
  if (!item.data_validade) return 'bg-green-700 text-white'
  const dias = diasParaVencer(item.data_validade)
  if (dias < 0)   return 'bg-red-600 text-white'
  if (dias <= 30) return 'bg-orange-500 text-white'
  return 'bg-green-700 text-white'
}

function limparFiltros() {
  filtros.value = { busca: '', lote: 'Todos os lotes', vencimento: 'Todos', dataInicial: '', dataFinal: '' }
}

// Exportação
const CABECALHOS = ['Lote', 'SKU', 'Produto', 'Quantidade', 'Validade', 'Fornecedor', 'Localização', 'Status']

function linhas() {
  return itensFiltrados.value.map(i => [
    i.lote?.numero_lote ?? '—',
    i.sku ?? '—',
    i.nome,
    String(i.quantidade),
    i.data_validade ? formatarData(i.data_validade) : '—',
    i.fornecedor ?? '—',
    i.localizacao ?? '—',
    statusTexto(i),
  ])
}

function nomeArquivo(ext) {
  const h = new Date()
  return `relatorio-${h.getFullYear()}-${String(h.getMonth()+1).padStart(2,'0')}-${String(h.getDate()).padStart(2,'0')}.${ext}`
}

function baixar(blob, nome) {
  const url  = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url; link.download = nome
  document.body.appendChild(link); link.click()
  document.body.removeChild(link); URL.revokeObjectURL(url)
}

function exportarCSV() {
  const csv = [CABECALHOS, ...linhas()]
    .map(l => l.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n')
  baixar(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }), nomeArquivo('csv'))
}

function exportarExcel() {
  const ws = XLSX.utils.aoa_to_sheet([CABECALHOS, ...linhas()])
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Relatório')
  XLSX.writeFile(wb, nomeArquivo('xlsx'))
}

function exportarPDF() {
  const doc = new jsPDF({ orientation: 'landscape' })
  doc.setFontSize(16)
  doc.text('Relatório de Estoque - SIGE', 14, 15)
  autoTable(doc, {
    head: [CABECALHOS], body: linhas(), startY: 22, theme: 'striped',
    headStyles: { fillColor: [58, 110, 165], textColor: [255,255,255], fontStyle: 'bold' },
    styles: { fontSize: 9, cellPadding: 3 },
    alternateRowStyles: { fillColor: [240,240,240] }
  })
  doc.save(nomeArquivo('pdf'))
}
</script>