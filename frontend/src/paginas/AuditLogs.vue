<template>
  <div :class="['p-6 min-h-screen', temaClaro ? 'bg-gray-100 text-gray-900' : 'bg-[#0f1117] text-white']">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold">Logs de Auditoria</h1>
      <p :class="['text-sm', temaClaro ? 'text-gray-500' : 'text-gray-400']">Histórico completo de todas as ações realizadas no sistema</p>
    </div>

    <!-- Cards de Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div :class="['rounded-lg p-4 flex items-center gap-4 border', temaClaro ? 'bg-white border-gray-200' : 'bg-[#1a1d27] border-gray-800']">
        <div class="bg-blue-600 p-3 rounded-lg">
          <ShieldCheckIcon :size="22" class="text-white" />
        </div>
        <div>
          <p :class="['text-sm', temaClaro ? 'text-gray-500' : 'text-gray-400']">Total de Logs</p>
          <p class="text-2xl font-bold">{{ stats.total }}</p>
        </div>
      </div>
      <div :class="['rounded-lg p-4 flex items-center gap-4 border', temaClaro ? 'bg-white border-gray-200' : 'bg-[#1a1d27] border-gray-800']">
        <div class="bg-green-600 p-3 rounded-lg">
          <CalendarIcon :size="22" class="text-white" />
        </div>
        <div>
          <p :class="['text-sm', temaClaro ? 'text-gray-500' : 'text-gray-400']">Últimas 24h</p>
          <p class="text-2xl font-bold">{{ stats.last_24h }}</p>
        </div>
      </div>
      <div :class="['rounded-lg p-4 flex items-center gap-4 border', temaClaro ? 'bg-white border-gray-200' : 'bg-[#1a1d27] border-gray-800']">
        <div class="bg-purple-600 p-3 rounded-lg">
          <UserIcon :size="22" class="text-white" />
        </div>
        <div>
          <p :class="['text-sm', temaClaro ? 'text-gray-500' : 'text-gray-400']">Usuários Ativos</p>
          <p class="text-2xl font-bold">{{ stats.active_users }}</p>
        </div>
      </div>
      <div :class="['rounded-lg p-4 flex items-center gap-4 border', temaClaro ? 'bg-white border-gray-200' : 'bg-[#1a1d27] border-gray-800']">
        <div class="bg-orange-600 p-3 rounded-lg">
          <FileTextIcon :size="22" class="text-white" />
        </div>
        <div>
          <p :class="['text-sm', temaClaro ? 'text-gray-500' : 'text-gray-400']">Ação + Comum</p>
          <p class="text-xl font-bold text-orange-400">{{ stats.most_common ?? '-' }}</p>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div :class="['rounded-lg p-4 mb-4 border flex flex-wrap gap-3 items-center', temaClaro ? 'bg-white border-gray-200' : 'bg-[#1a1d27] border-gray-800']">
      <div class="relative flex-1 min-w-[200px]">
        <SearchIcon :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input
          v-model="search"
          @input="fetchLogs()"
          type="text"
          placeholder="Buscar por usuário, ação, descrição..."
          :class="['w-full border rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-blue-500', temaClaro ? 'bg-gray-50 border-gray-300 text-gray-900 placeholder-gray-400' : 'bg-[#0f1117] border-gray-700 text-white placeholder-gray-500']"
        />
      </div>
      <select
        v-model="period"
        @change="fetchLogs()"
        :class="['border rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500', temaClaro ? 'bg-gray-50 border-gray-300 text-gray-900' : 'bg-[#0f1117] border-gray-700 text-white']"
      >
        <option value="7d">Últimos 7 dias</option>
        <option value="30d">Últimos 30 dias</option>
        <option value="90d">Últimos 90 dias</option>
      </select>
      <select
        v-model="actionFilter"
        @change="fetchLogs()"
        :class="['border rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500', temaClaro ? 'bg-gray-50 border-gray-300 text-gray-900' : 'bg-[#0f1117] border-gray-700 text-white']"
      >
        <option value="all">Todas Ações</option>
        <option value="Login">Login</option>
        <option value="Logout">Logout</option>
        <option value="Criação">Criação</option>
        <option value="Edição">Edição</option>
        <option value="Exclusão">Exclusão</option>
      </select>
      <button
        @click="exportCSV"
        :class="['flex items-center gap-2 border rounded-lg px-4 py-2 text-sm transition', temaClaro ? 'bg-gray-50 border-gray-300 text-gray-700 hover:border-blue-500' : 'bg-[#0f1117] border-gray-700 text-white hover:border-blue-500']"
      >
        <DownloadIcon :size="16" />
        Exportar
      </button>
    </div>

    <!-- Tabela -->
    <div :class="['rounded-lg border', temaClaro ? 'bg-white border-gray-200' : 'bg-[#1a1d27] border-gray-800']">
      <table class="w-full text-sm">
        <thead>
          <tr :class="['border-b', temaClaro ? 'border-gray-200' : 'border-gray-800']">
            <th class="text-left px-6 py-4 text-blue-500 font-semibold">Data/Hora</th>
            <th class="text-left px-6 py-4 text-blue-500 font-semibold">Usuário</th>
            <th class="text-left px-6 py-4 text-blue-500 font-semibold">Ação</th>
            <th class="text-left px-6 py-4 text-blue-500 font-semibold">Descrição</th>
            <th class="text-right px-6 py-4 text-blue-500 font-semibold">IP</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" :class="['text-center py-10', temaClaro ? 'text-gray-400' : 'text-gray-500']">Carregando...</td>
          </tr>
          <tr v-else-if="logs.length === 0">
            <td colspan="5" :class="['text-center py-10', temaClaro ? 'text-gray-400' : 'text-gray-500']">Nenhum log encontrado.</td>
          </tr>
          <tr
            v-for="log in logs"
            :key="log.id"
            :class="['border-b transition', temaClaro ? 'border-gray-100 hover:bg-gray-50' : 'border-gray-800 hover:bg-[#22263a]']"
          >
            <td class="px-6 py-4">
              <p class="font-bold">{{ formatDate(log.created_at) }}</p>
              <p :class="['text-xs', temaClaro ? 'text-gray-400' : 'text-gray-500']">{{ formatTime(log.created_at) }}</p>
            </td>
            <td class="px-6 py-4">
              <p class="font-bold">{{ log.user?.name ?? 'Sistema' }}</p>
              <p :class="['text-xs', temaClaro ? 'text-gray-400' : 'text-gray-500']">{{ log.user?.email ?? '-' }}</p>
            </td>
            <td class="px-6 py-4">
              <span :class="['flex items-center gap-2 w-fit px-3 py-1 rounded-md text-xs font-semibold', actionClass(log.action)]">
                {{ log.action }}
              </span>
            </td>
            <td :class="['px-6 py-4', temaClaro ? 'text-gray-600' : 'text-gray-300']">{{ log.description }}</td>
            <td :class="['px-6 py-4 text-right', temaClaro ? 'text-gray-400' : 'text-gray-400']">{{ log.ip_address }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Paginação -->
      <div :class="['flex justify-between items-center px-6 py-4 border-t text-sm', temaClaro ? 'border-gray-200 text-gray-500' : 'border-gray-800 text-gray-400']">
        <span>Página {{ pagination.current_page }} de {{ pagination.last_page }}</span>
        <div class="flex gap-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            :class="['px-3 py-1 rounded border transition disabled:opacity-30', temaClaro ? 'border-gray-300 hover:border-blue-500' : 'border-gray-700 hover:border-blue-500']"
          >Anterior</button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            :class="['px-3 py-1 rounded border transition disabled:opacity-30', temaClaro ? 'border-gray-300 hover:border-blue-500' : 'border-gray-700 hover:border-blue-500']"
          >Próximo</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ShieldCheck as ShieldCheckIcon, Calendar as CalendarIcon, User as UserIcon, FileText as FileTextIcon, Search as SearchIcon, Download as DownloadIcon } from 'lucide-vue-next'
import { storeToRefs } from 'pinia'
import { useTemaStore } from '@/servicos/tema.store'
import api from '@/servicos/api'

const temaStore       = useTemaStore()
const { temaClaro }   = storeToRefs(temaStore)

const logs        = ref([])
const stats       = ref({ total: 0, last_24h: 0, active_users: 0, most_common: null })
const loading     = ref(false)
const search      = ref('')
const period      = ref('30d')
const actionFilter= ref('all')
const pagination  = ref({ current_page: 1, last_page: 1 })

async function fetchLogs(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/audit-logs', {
      params: {
        search: search.value,
        period: period.value,
        action: actionFilter.value,
        page,
      }
    })
    logs.value  = data.logs.data
    stats.value = data.stats
    pagination.value = {
      current_page: data.logs.current_page,
      last_page:    data.logs.last_page,
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function changePage(page) {
  if (page < 1 || page > pagination.value.last_page) return
  fetchLogs(page)
}

function formatDate(dt) {
  return new Date(dt).toLocaleDateString('pt-BR')
}

function formatTime(dt) {
  return new Date(dt).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

function actionClass(action) {
  const map = {
    'Login':    'bg-blue-900 text-blue-300',
    'Logout':   'bg-gray-700 text-gray-300',
    'Criação':  'bg-green-900 text-green-300',
    'Edição':   'bg-yellow-900 text-yellow-300',
    'Exclusão': 'bg-red-900 text-red-300',
  }
  return map[action] ?? 'bg-gray-700 text-gray-300'
}

async function exportCSV() {
  const { data } = await api.get('/audit-logs/export', {
    params: { search: search.value, period: period.value, action: actionFilter.value },
    responseType: 'blob',
  })
  const url = URL.createObjectURL(new Blob([data]))
  const a   = document.createElement('a')
  a.href    = url
  a.download= 'audit-logs.csv'
  a.click()
  URL.revokeObjectURL(url)
}

onMounted(() => fetchLogs())
</script>