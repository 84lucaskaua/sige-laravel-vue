<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Relatórios</h1>

    <!-- Abas de relatório -->
    <div class="flex gap-2 mb-6 border-b">
      <button
        v-for="aba in abas"
        :key="aba.id"
        @click="abaAtiva = aba.id"
        class="px-4 py-2 text-sm font-medium border-b-2 transition"
        :class="abaAtiva === aba.id
          ? 'border-blue-600 text-blue-600'
          : 'border-transparent text-gray-500 hover:text-gray-700'"
      >
        {{ aba.icone }} {{ aba.nome }}
      </button>
    </div>

    <!-- ===== ABA: ESTOQUE ATUAL ===== -->
    <div v-if="abaAtiva === 'estoque'">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-700">Posição atual do estoque</h2>
        <button @click="carregarEstoque" class="text-blue-600 text-sm hover:underline">
          🔄 Atualizar
        </button>
      </div>

      <div v-if="carregando" class="text-center py-8 text-gray-500">Carregando...</div>

      <div v-else class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-600 font-medium">Produto</th>
              <th class="text-left px-4 py-3 text-gray-600 font-medium">Categoria</th>
              <th class="text-left px-4 py-3 text-gray-600 font-medium">Qtd. Total</th>
              <th class="text-left px-4 py-3 text-gray-600 font-medium">Estoque Mín.</th>
              <th class="text-left px-4 py-3 text-gray-600 font-medium">Situação</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="linha in relatorioEstoque"
              :key="linha.produto_id"
              class="border-b hover:bg-gray-50"
            >
              <td class="px-4 py-3 font-medium text-gray-800">{{ linha.nome }}</td>
              <td class="px-4 py-3 text-gray-600">{{ linha.categoria || '—' }}</td>
              <td class="px-4 py-3 font-semibold" :class="linha.quantidade_total === 0 ? 'text-red-600' : 'text-gray-800'">
                {{ linha.quantidade_total }} {{ linha.unidade }}
              </td>
              <td class="px-4 py-3 text-gray-600">{{ linha.estoque_minimo }}</td>
              <td class="px-4 py-3">
                <span
                  :class="{
                    'bg-red-100 text-red-700':    linha.quantidade_total === 0,
                    'bg-yellow-100 text-yellow-700': linha.quantidade_total > 0 && linha.quantidade_total <= linha.estoque_minimo,
                    'bg-green-100 text-green-700':  linha.quantidade_total > linha.estoque_minimo,
                  }"
                  class="px-2 py-1 rounded-full text-xs font-medium"
                >
                  {{
                    linha.quantidade_total === 0
                      ? '🔴 Zerado'
                      : linha.quantidade_total <= linha.estoque_minimo
                        ? '🟡 Crítico'
                        : '🟢 Normal'
                  }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ===== ABA: VENCIMENTOS ===== -->
    <div v-if="abaAtiva === 'vencimentos'">
      <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-700 mb-1">Produtos vencidos ou próximos do vencimento</h2>
        <p class="text-sm text-gray-500">Mostrando itens vencidos e os que vencem nos próximos 30 dias.</p>
      </div>

      <div v-if="carregando" class="text-center py-8 text-gray-500">Carregando...</div>

      <div v-else-if="relatorioVencimentos.length === 0" class="text-center py-8 text-green-600">
        ✅ Nenhum produto vencido ou próximo do vencimento.
      </div>

      <div v-else class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-600 font-medium">Produto</th>
              <th class="text-left px-4 py-3 text-gray-600 font-medium">Lote</th>
              <th class="text-left px-4 py-3 text-gray-600 font-medium">Quantidade</th>
              <th class="text-left px-4 py-3 text-gray-600 font-medium">Validade</th>
              <th class="text-left px-4 py-3 text-gray-600 font-medium">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in relatorioVencimentos" :key="item.id" class="border-b hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-800">{{ item.produto?.nome }}</td>
              <td class="px-4 py-3 text-gray-600">{{ item.lote?.numero }}</td>
              <td class="px-4 py-3 text-gray-800">{{ item.quantidade }} {{ item.produto?.unidade }}</td>
              <td class="px-4 py-3 text-gray-800">{{ formatarData(item.validade) }}</td>
              <td class="px-4 py-3">
                <span
                  :class="estaVencido(item.validade)
                    ? 'bg-red-100 text-red-700'
                    : 'bg-yellow-100 text-yellow-700'"
                  class="px-2 py-1 rounded-full text-xs font-medium"
                >
                  {{ estaVencido(item.validade) ? '🔴 Vencido' : '🟡 Vence em breve' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ===== ABA: LOG DE AUDITORIA ===== -->
    <div v-if="abaAtiva === 'auditoria'">
      <h2 class="text-lg font-semibold text-gray-700 mb-4">Registro de ações no sistema</h2>

      <div v-if="carregando" class="text-center py-8 text-gray-500">Carregando...</div>

      <div v-else class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-600 font-medium">Usuário</th>
              <th class="text-left px-4 py-3 text-gray-600 font-medium">Ação</th>
              <th class="text-left px-4 py-3 text-gray-600 font-medium">Detalhes</th>
              <th class="text-left px-4 py-3 text-gray-600 font-medium">Data/Hora</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logsAuditoria" :key="log.id" class="border-b hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-800">{{ log.usuario?.nome || 'Sistema' }}</td>
              <td class="px-4 py-3">
                <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs font-mono">
                  {{ log.acao }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-600 text-xs">
                {{ resumirDetalhes(log.detalhes) }}
              </td>
              <td class="px-4 py-3 text-gray-500 text-xs">
                {{ formatarDataHora(log.criado_em) }}
              </td>
            </tr>
          </tbody>
        </table>

        <p v-if="logsAuditoria.length === 0" class="text-center py-8 text-gray-400">
          Nenhum log registrado ainda.
        </p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import api from '@/servicos/api'
import { formatarData, formatarDataHora, estaVencido } from '@/utils/date'

// Abas disponíveis no relatório
const abas = [
  { id: 'estoque',     nome: 'Estoque Atual',   icone: '📦' },
  { id: 'vencimentos', nome: 'Vencimentos',      icone: '📅' },
  { id: 'auditoria',  nome: 'Log de Auditoria', icone: '📋' },
]

const abaAtiva = ref('estoque')
const carregando = ref(false)

const relatorioEstoque    = ref([])
const relatorioVencimentos = ref([])
const logsAuditoria       = ref([])

/**
 * Carrega os dados conforme a aba ativa
 */
async function carregarDadosDaAba(aba) {
  carregando.value = true
  try {
    if (aba === 'estoque') {
      await carregarEstoque()
    } else if (aba === 'vencimentos') {
      await carregarVencimentos()
    } else if (aba === 'auditoria') {
      await carregarLogs()
    }
  } finally {
    carregando.value = false
  }
}

async function carregarEstoque() {
  const resposta = await api.get('/relatorios/estoque')
  relatorioEstoque.value = resposta.data
}

async function carregarVencimentos() {
  const resposta = await api.get('/relatorios/vencimentos')
  relatorioVencimentos.value = resposta.data
}

async function carregarLogs() {
  const resposta = await api.get('/relatorios/auditoria')
  logsAuditoria.value = resposta.data
}

/**
 * Quando o usuário muda de aba, carrega os dados daquela aba
 */
watch(abaAtiva, (novaAba) => {
  carregarDadosDaAba(novaAba)
})

function resumirDetalhes(detalhes) {
  if (!detalhes) return '—'
  return Object.entries(detalhes)
    .map(([chave, valor]) => `${chave}: ${valor}`)
    .join(' | ')
}

// Carrega o estoque ao abrir a página
onMounted(() => carregarDadosDaAba('estoque'))
</script>
