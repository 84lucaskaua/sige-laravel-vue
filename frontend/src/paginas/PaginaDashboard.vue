<template>
  <div class="p-6 min-h-screen bg-black text-white">

    <div class="mb-8">
      <h1 class="text-3xl font-bold text-white">Dashboard</h1>
      <p class="text-slate-400 mt-1">Visão geral do sistema de gerenciamento de estoque</p>
    </div>

    <div v-if="carregando" class="text-center py-12 text-slate-400">
      Carregando dados...
    </div>

    <div v-else class="space-y-6">

      <!-- CARDS -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

        <div class="rounded-xl p-6 bg-slate-900 border-2 border-blue-700 cursor-pointer hover:bg-blue-900/20 transition flex flex-col gap-4" @click="$router.push('/lotes')">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-400 text-sm">Total de Lotes</p>
              <p class="text-4xl font-bold text-white mt-2">{{ resumo.totalLotes }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-600/20 rounded-lg flex items-center justify-center">
              <PackagePlus class="text-blue-500" :size="24" />
            </div>
          </div>
          <p class="text-xs text-blue-400">Clique para ver todos os lotes</p>
        </div>

        <div class="rounded-xl p-6 bg-slate-900 border-2 border-cyan-700 cursor-pointer hover:bg-cyan-900/20 transition flex flex-col gap-4" @click="$router.push('/produtos')">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-400 text-sm">Total de Itens</p>
              <p class="text-4xl font-bold text-white mt-2">{{ resumo.totalProdutos }}</p>
            </div>
            <div class="w-12 h-12 bg-cyan-600/20 rounded-lg flex items-center justify-center">
              <Package class="text-cyan-500" :size="24" />
            </div>
          </div>
          <p class="text-xs text-cyan-400">Clique para ver todos os produtos</p>
        </div>

        <div class="rounded-xl p-6 bg-slate-900 border-2 border-amber-700 cursor-pointer hover:bg-amber-900/20 transition flex flex-col gap-4" @click="$router.push('/lotes?filtro=vencendo')">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-400 text-sm">Vencendo em 30 dias</p>
              <p class="text-4xl font-bold text-white mt-2">{{ resumo.vencendoEm30Dias }}</p>
            </div>
            <div class="w-12 h-12 bg-amber-600/20 rounded-lg flex items-center justify-center">
              <AlertTriangle class="text-amber-500" :size="24" />
            </div>
          </div>
          <p class="text-xs text-amber-400">Clique para ver produtos vencendo</p>
        </div>

        <div class="rounded-xl p-6 bg-slate-900 border-2 border-red-700 cursor-pointer hover:bg-red-900/20 transition flex flex-col gap-4" @click="$router.push('/produtos?filtro=critico')">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-400 text-sm">Estoque Baixo</p>
              <p class="text-4xl font-bold text-white mt-2">{{ resumo.estoqueCritico }}</p>
            </div>
            <div class="w-12 h-12 bg-red-600/20 rounded-lg flex items-center justify-center">
              <TrendingDown class="text-red-500" :size="24" />
            </div>
          </div>
          <p class="text-xs text-red-400">Clique para ver estoque baixo</p>
        </div>

      </div>

      <!-- ALERTAS + GRÁFICO DE LINHA -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="rounded-xl bg-slate-900 border border-slate-800 p-6">
          <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <AlertTriangle class="text-red-500" :size="20" />
            Alertas Críticos
          </h2>
          <div class="space-y-3">
            <div v-if="resumo.vencendoEm30Dias > 0" class="bg-red-900/20 border border-red-900 rounded-lg p-4">
              <p class="text-red-400 font-medium">{{ resumo.vencendoEm30Dias }} lote(s) vencendo em 30 dias</p>
              <p class="text-red-300 text-sm mt-1">Verificar validade e priorizar saída (FEFO)</p>
            </div>
            <div v-if="resumo.estoqueCritico > 0" class="bg-orange-900/20 border border-orange-900 rounded-lg p-4">
              <p class="text-orange-400 font-medium">{{ resumo.estoqueCritico }} produto(s) com estoque baixo</p>
              <p class="text-orange-300 text-sm mt-1">Solicitar reposição de estoque</p>
            </div>
            <div v-if="resumo.vencendoEm30Dias === 0 && resumo.estoqueCritico === 0" class="bg-green-900/20 border border-green-900 rounded-lg p-4">
              <p class="text-green-400 font-medium">✓ Nenhum alerta crítico</p>
              <p class="text-green-300 text-sm mt-1">Tudo está funcionando perfeitamente</p>
            </div>
          </div>
        </div>

        <div class="rounded-xl bg-slate-900 border border-slate-800 p-6">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-blue-900 rounded-lg">
              <TrendingUp class="text-blue-400" :size="24" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-white">Evolução do Estoque</h3>
              <p class="text-sm text-slate-400">Últimos 30 dias</p>
            </div>
          </div>
          <canvas ref="graficoLinha" height="220"></canvas>
        </div>

      </div>

      <!-- GRÁFICO DE PIZZA + MOVIMENTOS -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="rounded-xl bg-slate-900 border border-slate-800 p-6">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-purple-900 rounded-lg">
              <PieChart class="text-purple-400" :size="24" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-white">Distribuição por Categoria</h3>
              <p class="text-sm text-slate-400">{{ resumo.totalCategorias || 0 }} categorias</p>
            </div>
          </div>
          <div v-if="semDadosPizza" class="flex items-center justify-center h-64 text-slate-500">
            Nenhum dado disponível
          </div>
          <canvas v-else ref="graficoPizza" height="300"></canvas>
        </div>

        <div class="rounded-xl bg-slate-900 border border-slate-800 p-6">
          <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <History class="text-blue-500" :size="20" />
            Movimentos Recentes
          </h2>
          <div class="space-y-3">
            <p v-if="movimentosRecentes.length === 0" class="text-slate-500 text-center py-8">
              Nenhum movimento registrado ainda.
            </p>
            <div
              v-for="mov in movimentosRecentes"
              :key="mov.id"
              class="bg-slate-800 rounded-lg p-4 flex items-center justify-between"
            >
              <div class="flex items-center gap-3">
                <div :class="mov.tipo === 'entrada' ? 'bg-green-900/40' : 'bg-red-900/40'" class="w-8 h-8 rounded-lg flex items-center justify-center">
                  <TrendingUp v-if="mov.tipo === 'entrada'" class="text-green-400" :size="16" />
                  <TrendingDown v-else class="text-red-400" :size="16" />
                </div>
                <div>
                  <p class="text-white font-medium text-sm">{{ mov.item_lote?.produto?.nome || '—' }}</p>
                  <p class="text-slate-400 text-xs">Por {{ mov.usuario?.nome || 'Sistema' }} • {{ formatarData(mov.data_movimento) }}</p>
                </div>
              </div>
              <span :class="mov.tipo === 'entrada' ? 'text-green-400' : 'text-red-400'" class="font-bold text-sm">
                {{ mov.tipo === 'entrada' ? '+' : '-' }}{{ mov.quantidade }}
              </span>
            </div>
          </div>
        </div>

      </div>

      <!-- TOP 10 PRODUTOS -->
      <div class="rounded-xl bg-slate-900 border border-slate-800 p-6">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-2 bg-green-900 rounded-lg">
            <TrendingUp class="text-green-400" :size="24" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-white">Top 10 Produtos</h3>
            <p class="text-sm text-slate-400">Maiores estoques</p>
          </div>
        </div>

        <div v-if="topProdutos.length === 0" class="flex items-center justify-center h-40 text-slate-500">
          Nenhum dado disponível
        </div>

        <table v-else class="w-full text-sm">
          <thead>
            <tr class="text-slate-400 border-b border-slate-800">
              <th class="text-left pb-3 font-medium">#</th>
              <th class="text-left pb-3 font-medium">Produto</th>
              <th class="text-left pb-3 font-medium">Categoria</th>
              <th class="text-right pb-3 font-medium">Estoque</th>
              <th class="text-right pb-3 font-medium">Mín.</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            <tr
              v-for="(produto, index) in topProdutos"
              :key="produto.id_produto"
              class="hover:bg-slate-800/50 transition"
            >
              <td class="py-3 text-slate-500">{{ index + 1 }}</td>
              <td class="py-3 text-white font-medium">{{ produto.nome }}</td>
              <td class="py-3 text-slate-400">{{ produto.categoria?.nome || '—' }}</td>
              <td class="py-3 text-right">
                <span :class="produto.estoque_atual <= produto.estoque_minimo ? 'text-red-400' : 'text-green-400'" class="font-bold">
                  {{ produto.estoque_atual }}
                </span>
              </td>
              <td class="py-3 text-right text-slate-400">{{ produto.estoque_minimo }}</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { PackagePlus, Package, AlertTriangle, TrendingDown, TrendingUp, PieChart, History } from 'lucide-vue-next'
import api from '@/servicos/api'
import Chart from 'chart.js/auto'

const carregando         = ref(true)
const graficoLinha       = ref(null)
const graficoPizza       = ref(null)
const movimentosRecentes = ref([])
const topProdutos        = ref([])
const semDadosPizza      = ref(false)

const resumo = ref({
  totalProdutos:    0,
  totalLotes:       0,
  estoqueCritico:   0,
  vencendoEm30Dias: 0,
  totalCategorias:  0,
})

let chartLinha = null
let chartPizza = null

function formatarData(data) {
  if (!data) return '—'
  return new Date(data).toLocaleDateString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

async function carregarDashboard() {
  carregando.value = true
  try {
    const resposta = await api.get('/dashboard')
    resumo.value             = resposta.data.resumo
    movimentosRecentes.value = resposta.data.movimentosRecentes || []
    topProdutos.value        = resposta.data.topProdutos || []

    await nextTick()
    montarGraficoLinha(resposta.data.evolucaoEstoque || [])
    montarGraficoPizza(resposta.data.distribuicaoCategorias || [])

  } catch (erro) {
    console.error('Erro ao carregar dashboard:', erro)
    await nextTick()
    montarGraficoLinha([])
    montarGraficoPizza([])
  } finally {
    carregando.value = false
  }
}

function montarGraficoLinha(dados) {
  if (chartLinha) chartLinha.destroy()
  if (!graficoLinha.value) return

  chartLinha = new Chart(graficoLinha.value, {
    type: 'line',
    data: {
      labels: dados.map(d => d.label),
      datasets: [
        { label: 'Estoque Total', data: dados.map(d => d.estoqueTotal || 0), borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.08)', tension: 0.3, pointRadius: 2 },
        { label: 'Entradas',     data: dados.map(d => d.entradas || 0),     borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.08)', tension: 0.3, pointRadius: 2 },
        { label: 'Saídas',       data: dados.map(d => d.saidas || 0),       borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.08)',   tension: 0.3, pointRadius: 2 },
      ],
    },
    options: {
      responsive: true,
      animation: false,
      plugins: { legend: { labels: { color: '#94a3b8', font: { size: 11 } } } },
      scales: {
        x: { ticks: { color: '#64748b', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' } },
        y: { ticks: { color: '#64748b', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true },
      },
    },
  })
}

function montarGraficoPizza(dados) {
  if (chartPizza) chartPizza.destroy()

  if (!dados || dados.length === 0) {
    semDadosPizza.value = true
    return
  }

  semDadosPizza.value = false
  if (!graficoPizza.value) return

  const cores = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#14b8a6']

  chartPizza = new Chart(graficoPizza.value, {
    type: 'pie',
    data: {
      labels: dados.map(d => d.categoria),
      datasets: [{
        data: dados.map(d => d.percentual || d.quantidade),
        backgroundColor: cores.slice(0, dados.length),
        borderColor: '#0f172a',
        borderWidth: 2,
      }],
    },
    options: {
      responsive: true,
      animation: false,
      plugins: {
        legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 11 }, padding: 12, boxWidth: 12 } },
        tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed.toFixed(1)}%` } },
      },
    },
  })
}

onMounted(carregarDashboard)
</script>