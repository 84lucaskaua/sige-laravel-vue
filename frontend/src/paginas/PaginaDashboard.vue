<template>
  <div class="p-6">

    <!-- Título da página -->
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Dashboard</h1>

    <!-- Indicador de carregamento -->
    <div v-if="carregando" class="text-center py-12 text-gray-500">
      Carregando dados...
    </div>

    <div v-else>

      <!-- ===== CARDS DE RESUMO ===== -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

        <CardResumo
          titulo="Total de Produtos"
          :valor="resumo.totalProdutos"
          icone="📦"
          cor="blue"
        />
        <CardResumo
          titulo="Lotes Ativos"
          :valor="resumo.totalLotes"
          icone="🗂️"
          cor="green"
        />
        <CardResumo
          titulo="Estoque Crítico"
          :valor="resumo.estoqueCritico"
          icone="⚠️"
          cor="yellow"
        />
        <CardResumo
          titulo="Vencendo em 30 dias"
          :valor="resumo.vencendoEm30Dias"
          icone="📅"
          cor="red"
        />

      </div>

      <!-- ===== MOVIMENTOS RECENTES ===== -->
      <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Movimentos Recentes</h2>

        <!-- Sem movimentos -->
        <p v-if="movimentosRecentes.length === 0" class="text-gray-500 text-sm">
          Nenhum movimento registrado ainda.
        </p>

        <!-- Lista de movimentos -->
        <ul v-else class="divide-y">
          <li
            v-for="movimento in movimentosRecentes"
            :key="movimento.id"
            class="py-3 flex items-center justify-between"
          >
            <div class="flex items-center gap-3">
              <!-- Ícone verde para entrada, vermelho para saída -->
              <span :class="movimento.tipo === 'entrada' ? 'text-green-500' : 'text-red-500'" class="text-lg">
                {{ movimento.tipo === 'entrada' ? '⬆️' : '⬇️' }}
              </span>
              <div>
                <p class="text-sm font-medium text-gray-800">
                  {{ movimento.item_lote?.produto?.nome || '—' }}
                </p>
                <p class="text-xs text-gray-500">
                  Por {{ movimento.usuario?.nome || 'Sistema' }} •
                  {{ formatarData(movimento.data_movimento) }}
                </p>
              </div>
            </div>

            <!-- Quantidade com cor por tipo -->
            <span :class="movimento.tipo === 'entrada' ? 'text-green-600' : 'text-red-600'" class="font-semibold text-sm">
              {{ movimento.tipo === 'entrada' ? '+' : '-' }}{{ movimento.quantidade }}
            </span>
          </li>
        </ul>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/servicos/api'
import CardResumo from '@/componentes/ui/CardResumo.vue'

// ---- Dados da página ----
const carregando = ref(false)

// Números do resumo para os cards
const resumo = ref({
  totalProdutos:     0,
  totalLotes:        0,
  estoqueCritico:    0,
  vencendoEm30Dias:  0,
})

// Lista dos últimos movimentos
const movimentosRecentes = ref([])

/**
 * Carrega os dados do dashboard do backend
 */
async function carregarDashboard() {
  carregando.value = true

  try {
    const resposta = await api.get('/dashboard')
    resumo.value             = resposta.data.resumo
    movimentosRecentes.value = resposta.data.movimentosRecentes

  } catch (erro) {
    console.error('Erro ao carregar dashboard:', erro)

  } finally {
    carregando.value = false
  }
}

/**
 * Formata uma data para o padrão brasileiro
 * Ex: "2024-03-15T10:30:00" → "15/03/2024 às 10:30"
 */
function formatarData(dataString) {
  if (!dataString) return '—'

  const data = new Date(dataString)

  const dia  = String(data.getDate()).padStart(2, '0')
  const mes  = String(data.getMonth() + 1).padStart(2, '0')
  const ano  = data.getFullYear()
  const hora = String(data.getHours()).padStart(2, '0')
  const min  = String(data.getMinutes()).padStart(2, '0')

  return `${dia}/${mes}/${ano} às ${hora}:${min}`
}

// Carrega os dados ao abrir a página
onMounted(carregarDashboard)
</script>
