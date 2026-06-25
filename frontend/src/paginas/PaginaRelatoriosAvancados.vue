<template>
  <div class="p-6 min-h-screen bg-black text-white">

    <!-- Cabeçalho -->
    <div class="flex items-start justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-white">Relatórios Avançados</h1>
        <p class="text-sm text-slate-400">Análises detalhadas de perdas e classificação ABC</p>
      </div>
      <div class="flex gap-2">
        <div class="relative">
          <button @click="dropdownPeriodoAberto = !dropdownPeriodoAberto"
            class="flex items-center gap-2 bg-slate-800 border border-slate-700 text-white text-sm px-4 py-2 rounded-lg">
            <Calendar :size="16" /> {{ periodoLabel }} <ChevronDown :size="16" />
          </button>
          <div v-if="dropdownPeriodoAberto" class="absolute right-0 z-10 mt-1 w-48 bg-slate-800 border border-slate-700 rounded-lg shadow-lg overflow-hidden">
            <div v-for="p in opcoesPeriodo" :key="p.dias" @click="selecionarPeriodo(p)"
              class="px-3 py-2 text-sm cursor-pointer transition"
              :class="periodo.dias === p.dias ? 'bg-blue-600 text-white' : 'text-slate-200 hover:bg-slate-700'">
              {{ p.label }}
            </div>
          </div>
        </div>
        <button @click="exportar"
          class="flex items-center gap-2 bg-slate-800 border border-slate-700 text-white text-sm px-4 py-2 rounded-lg hover:bg-slate-700 transition">
          <Download :size="16" /> Exportar
        </button>
      </div>
    </div>

    <!-- Abas -->
    <div class="flex gap-0 mb-6 border-b border-slate-800">
      <button @click="aba = 'perdas'"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium border-b-2 transition"
        :class="aba === 'perdas' ? 'border-blue-500 text-blue-400' : 'border-transparent text-slate-400 hover:text-white'">
        <TrendingDown :size="16" /> Relatório de Perdas
      </button>
      <button @click="aba = 'abc'"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium border-b-2 transition"
        :class="aba === 'abc' ? 'border-blue-500 text-blue-400' : 'border-transparent text-slate-400 hover:text-white'">
        <PieChart :size="16" /> Análise ABC
      </button>
    </div>

    <!-- ===== ABA PERDAS ===== -->
    <div v-if="aba === 'perdas'">
      <div v-if="carregando" class="text-center py-12 text-slate-500">Carregando...</div>
      <template v-else>
        <!-- Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="rounded-xl bg-slate-900 border border-slate-800 p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-red-900/50 flex items-center justify-center">
              <AlertCircle class="text-red-400" :size="20" />
            </div>
            <div>
              <p class="text-slate-400 text-xs">Total de Perdas</p>
              <p class="text-2xl font-bold text-white">{{ dadosPerdas.resumo?.total ?? 0 }}</p>
            </div>
          </div>
          <div class="rounded-xl bg-slate-900 border border-slate-800 p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-orange-900/50 flex items-center justify-center">
              <Package class="text-orange-400" :size="20" />
            </div>
            <div>
              <p class="text-slate-400 text-xs">Unidades Perdidas</p>
              <p class="text-2xl font-bold text-white">{{ dadosPerdas.resumo?.unidades ?? 0 }}</p>
            </div>
          </div>
          <div class="rounded-xl bg-slate-900 border border-slate-800 p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-blue-900/50 flex items-center justify-center">
              <FileText class="text-blue-400" :size="20" />
            </div>
            <div>
              <p class="text-slate-400 text-xs">Tipos de Perda</p>
              <p class="text-2xl font-bold text-white">{{ dadosPerdas.resumo?.tipos ?? 0 }}</p>
            </div>
          </div>
        </div>

        <!-- Perdas por motivo -->
        <div class="rounded-xl bg-slate-900 border border-slate-800 p-5 mb-6">
          <h2 class="font-semibold text-white mb-4">Perdas por Motivo</h2>
          <div v-if="!dadosPerdas.porMotivo?.length" class="text-center py-8 text-slate-500">
            Nenhuma perda registrada no período
          </div>
          <div v-else class="space-y-3">
            <div v-for="m in dadosPerdas.porMotivo" :key="m.motivo" class="flex items-center gap-4">
              <span class="text-slate-300 text-sm w-48 truncate">{{ m.motivo }}</span>
              <div class="flex-1 bg-slate-800 rounded-full h-2">
                <div class="bg-red-500 h-2 rounded-full"
                  :style="{ width: totalUnidadesPerdas > 0 ? (m.total / totalUnidadesPerdas * 100) + '%' : '0%' }">
                </div>
              </div>
              <span class="text-slate-400 text-sm w-24 text-right">{{ m.total }} unid.</span>
              <span class="text-slate-500 text-xs w-16 text-right">{{ m.ocorrencias }}x</span>
            </div>
          </div>
        </div>

        <!-- Tabela detalhada -->
        <div class="rounded-xl bg-slate-900 border border-slate-800">
          <div class="px-5 py-4 border-b border-slate-800">
            <h2 class="font-semibold text-white">Detalhamento de Perdas</h2>
          </div>
          <table class="w-full text-sm">
            <thead>
              <tr class="text-slate-400 border-b border-slate-800">
                <th class="text-left px-4 py-3 font-medium">Data</th>
                <th class="text-left px-4 py-3 font-medium">Produto</th>
                <th class="text-left px-4 py-3 font-medium">Motivo</th>
                <th class="text-right px-4 py-3 font-medium">Quantidade</th>
                <th class="text-left px-4 py-3 font-medium">Responsável</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
              <tr v-if="!dadosPerdas.perdas?.length">
                <td colspan="5" class="text-center py-8 text-slate-500">Nenhuma perda registrada no período</td>
              </tr>
              <tr v-for="p in dadosPerdas.perdas" :key="p.id" class="hover:bg-slate-800/50 transition">
                <td class="px-4 py-3 text-slate-300 whitespace-nowrap">{{ formatarDataHora(p.data) }}</td>
                <td class="px-4 py-3 text-white">{{ p.produto }}</td>
                <td class="px-4 py-3 text-slate-400">{{ p.motivo }}</td>
                <td class="px-4 py-3 text-right text-red-400 font-semibold">-{{ p.quantidade }}</td>
                <td class="px-4 py-3 text-slate-400">{{ p.usuario }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- ===== ABA ABC ===== -->
    <div v-if="aba === 'abc'">
      <div v-if="carregando" class="text-center py-12 text-slate-500">Carregando...</div>
      <template v-else>
        <!-- Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
            <p class="text-slate-400 text-xs mb-1">Total Produtos</p>
            <p class="text-2xl font-bold text-white">{{ dadosAbc.resumo?.total ?? 0 }}</p>
          </div>
          <div class="rounded-xl bg-slate-900 border border-green-800 p-5">
            <p class="text-green-400 text-xs mb-1">Classe A (80%)</p>
            <p class="text-2xl font-bold text-white">{{ dadosAbc.resumo?.A ?? 0 }}</p>
          </div>
          <div class="rounded-xl bg-slate-900 border border-orange-800 p-5">
            <p class="text-orange-400 text-xs mb-1">Classe B (15%)</p>
            <p class="text-2xl font-bold text-white">{{ dadosAbc.resumo?.B ?? 0 }}</p>
          </div>
          <div class="rounded-xl bg-slate-900 border border-red-800 p-5">
            <p class="text-red-400 text-xs mb-1">Classe C (5%)</p>
            <p class="text-2xl font-bold text-white">{{ dadosAbc.resumo?.C ?? 0 }}</p>
          </div>
        </div>

        <!-- Gráfico de pizza simples em SVG -->
        <div class="rounded-xl bg-slate-900 border border-slate-800 p-5 mb-6">
          <h2 class="font-semibold text-white mb-4">Distribuição por Classe ABC</h2>
          <div class="flex items-center justify-center gap-12">
            <svg viewBox="0 0 200 200" class="w-48 h-48">
              <circle cx="100" cy="100" r="80" fill="#1e293b" />
              <template v-if="dadosAbc.resumo?.total > 0">
                <!-- Fatias calculadas -->
                <path v-for="(fatia, i) in fatiasPizza" :key="i"
                  :d="fatia.d" :fill="fatia.cor" />
              </template>
              <circle cx="100" cy="100" r="45" fill="#0f172a" />
            </svg>
            <div class="space-y-2">
              <div class="flex items-center gap-2 text-sm">
                <span class="w-3 h-3 rounded-sm bg-green-500 inline-block"></span>
                <span class="text-slate-300">Classe A: {{ dadosAbc.resumo?.A ?? 0 }} ({{ pct('A') }}%)</span>
              </div>
              <div class="flex items-center gap-2 text-sm">
                <span class="w-3 h-3 rounded-sm bg-orange-400 inline-block"></span>
                <span class="text-slate-300">Classe B: {{ dadosAbc.resumo?.B ?? 0 }} ({{ pct('B') }}%)</span>
              </div>
              <div class="flex items-center gap-2 text-sm">
                <span class="w-3 h-3 rounded-sm bg-red-500 inline-block"></span>
                <span class="text-slate-300">Classe C: {{ dadosAbc.resumo?.C ?? 0 }} ({{ pct('C') }}%)</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabela ABC -->
        <div class="rounded-xl bg-slate-900 border border-slate-800 mb-6">
          <div class="px-5 py-4 border-b border-slate-800">
            <h2 class="font-semibold text-white">Classificação ABC de Produtos</h2>
          </div>
          <table class="w-full text-sm">
            <thead>
              <tr class="text-slate-400 border-b border-slate-800">
                <th class="text-left px-4 py-3 font-medium">Classe</th>
                <th class="text-left px-4 py-3 font-medium">Produto</th>
                <th class="text-left px-4 py-3 font-medium">SKU</th>
                <th class="text-right px-4 py-3 font-medium">Movimento Total</th>
                <th class="text-right px-4 py-3 font-medium">% do Total</th>
                <th class="text-right px-4 py-3 font-medium">% Acumulado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
              <tr v-if="!dadosAbc.itens?.length">
                <td colspan="6" class="text-center py-8 text-slate-500">Nenhum dado disponível</td>
              </tr>
              <tr v-for="item in dadosAbc.itens" :key="item.id_item" class="hover:bg-slate-800/50 transition">
                <td class="px-4 py-3">
                  <span class="w-6 h-6 rounded flex items-center justify-center text-white text-xs font-bold inline-flex"
                    :class="item.classe === 'A' ? 'bg-green-600' : item.classe === 'B' ? 'bg-orange-500' : 'bg-red-600'">
                    {{ item.classe }}
                  </span>
                </td>
                <td class="px-4 py-3 text-white font-medium">{{ item.nome }}</td>
                <td class="px-4 py-3 text-slate-400">{{ item.sku }}</td>
                <td class="px-4 py-3 text-right text-white">{{ item.movimento }}</td>
                <td class="px-4 py-3 text-right text-slate-300">{{ item.percentual }}%</td>
                <td class="px-4 py-3 text-right text-blue-400">{{ item.acumulado }}%</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Legenda ABC -->
        <div class="rounded-xl bg-blue-950/40 border border-blue-800 p-5">
          <h3 class="text-white font-semibold mb-3">Sobre a Análise ABC</h3>
          <p class="text-green-400 text-sm mb-1"><strong>Classe A (80%):</strong> Produtos mais importantes, representam 80% do valor total de movimentação. Merecem atenção especial no controle de estoque.</p>
          <p class="text-orange-400 text-sm mb-1"><strong>Classe B (15%):</strong> Produtos de importância intermediária, representam 15% do valor total. Controle moderado.</p>
          <p class="text-red-400 text-sm"><strong>Classe C (5%):</strong> Produtos de menor importância, representam apenas 5% do valor total. Controle simplificado.</p>
        </div>
      </template>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import api from '@/servicos/api'
import { Calendar, ChevronDown, Download, TrendingDown, PieChart, AlertCircle, Package, FileText } from 'lucide-vue-next'
import * as XLSX from 'xlsx'

const aba = ref('perdas')
const carregando = ref(false)
const dropdownPeriodoAberto = ref(false)

const opcoesPeriodo = [
  { label: 'Últimos 7 dias',  dias: 7  },
  { label: 'Últimos 30 dias', dias: 30 },
  { label: 'Últimos 90 dias', dias: 90 },
  { label: 'Último ano',      dias: 365 },
]
const periodo = ref(opcoesPeriodo[1])
const periodoLabel = computed(() => periodo.value.label)

const dadosPerdas = ref({ perdas: [], porMotivo: [], resumo: {} })
const dadosAbc    = ref({ itens: [], resumo: {} })

const totalUnidadesPerdas = computed(() =>
  dadosPerdas.value.porMotivo?.reduce((s, m) => s + m.total, 0) ?? 0
)

function selecionarPeriodo(p) {
  periodo.value = p
  dropdownPeriodoAberto.value = false
}

async function carregarDados() {
  carregando.value = true
  try {
    if (aba.value === 'perdas') {
      const { data } = await api.get('/relatorios-avancados/perdas', { params: { dias: periodo.value.dias } })
      dadosPerdas.value = data
    } else {
      const { data } = await api.get('/relatorios-avancados/abc')
      dadosAbc.value = data
    }
  } catch (e) {
    console.error(e)
  } finally {
    carregando.value = false
  }
}

watch([aba, periodo], carregarDados)
onMounted(carregarDados)

// Gráfico de pizza SVG
const fatiasPizza = computed(() => {
  const total = dadosAbc.value.resumo?.total ?? 0
  if (!total) return []
  const a = dadosAbc.value.resumo.A / total
  const b = dadosAbc.value.resumo.B / total
  const c = dadosAbc.value.resumo.C / total
  return calcularFatias([
    { pct: a, cor: '#22c55e' },
    { pct: b, cor: '#f97316' },
    { pct: c, cor: '#ef4444' },
  ])
})

function calcularFatias(dados) {
  const cx = 100, cy = 100, r = 80
  let angulo = -Math.PI / 2

  // Ignora fatias com 0% (evita paths degenerados)
  const visiveis = dados.filter(d => d.pct > 0)

  return visiveis.map(({ pct, cor }) => {
    // Caso especial: fatia ocupa 100% do círculo.
    // Um arco SVG não fecha uma volta completa num único comando A
    // (início == fim vira arco de raio zero e não desenha nada),
    // então nesse caso desenhamos o círculo em duas metades.
    if (pct >= 1) {
      return {
        d: `M${cx - r},${cy} A${r},${r} 0 1,1 ${cx + r},${cy} A${r},${r} 0 1,1 ${cx - r},${cy} Z`,
        cor
      }
    }

    const inicio = angulo
    angulo += pct * 2 * Math.PI
    const fim = angulo
    const x1 = cx + r * Math.cos(inicio)
    const y1 = cy + r * Math.sin(inicio)
    const x2 = cx + r * Math.cos(fim)
    const y2 = cy + r * Math.sin(fim)
    const large = pct > 0.5 ? 1 : 0
    return { d: `M${cx},${cy} L${x1},${y1} A${r},${r} 0 ${large},1 ${x2},${y2} Z`, cor }
  })
}

function pct(classe) {
  const total = dadosAbc.value.resumo?.total ?? 0
  if (!total) return 0
  return Math.round((dadosAbc.value.resumo[classe] / total) * 100)
}

function formatarDataHora(dataISO) {
  if (!dataISO) return '—'
  const d = new Date(dataISO)
  return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}, ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`
}

function exportar() {
  if (aba.value === 'perdas') {
    const ws = XLSX.utils.aoa_to_sheet([
      ['Data', 'Produto', 'Motivo', 'Quantidade', 'Responsável'],
      ...(dadosPerdas.value.perdas ?? []).map(p => [
        formatarDataHora(p.data), p.produto, p.motivo, p.quantidade, p.usuario
      ])
    ])
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Perdas')
    XLSX.writeFile(wb, `perdas-${periodo.value.dias}dias.xlsx`)
  } else {
    const ws = XLSX.utils.aoa_to_sheet([
      ['Classe', 'Produto', 'SKU', 'Movimento Total', '% do Total', '% Acumulado'],
      ...(dadosAbc.value.itens ?? []).map(i => [
        i.classe, i.nome, i.sku, i.movimento, i.percentual + '%', i.acumulado + '%'
      ])
    ])
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'ABC')
    XLSX.writeFile(wb, 'analise-abc.xlsx')
  }
}
</script>