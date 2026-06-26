<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-white">Importação e Exportação</h1>
      <p class="text-gray-400 text-sm mt-1">Gerencie importação de dados e exportação de relatórios</p>
    </div>

    <!-- Cards de Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="card in statsCards" :key="card.label"
        class="bg-[#1e1e2e] border border-gray-700 rounded-lg p-4 flex items-center gap-4">
        <div :class="card.bg" class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-xl">
          <i :class="card.icon"></i>
        </div>
        <div>
          <p class="text-gray-400 text-sm">{{ card.label }}</p>
          <p class="text-white text-2xl font-bold">{{ stats[card.key] ?? 0 }}</p>
        </div>
      </div>
    </div>

    <!-- Exportar -->
    <div class="bg-[#1a1a2e] border border-gray-700 rounded-xl p-6">
      <h2 class="text-green-400 font-semibold text-lg mb-4 flex items-center gap-2">
        <i class="fas fa-download"></i> Exportar Dados
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Backup JSON -->
        <div class="bg-[#1e1e2e] border border-gray-700 rounded-lg p-4 space-y-3">
          <div>
            <p class="text-white font-semibold">Backup Completo</p>
            <p class="text-gray-400 text-sm">Exporta todos os dados do sistema em formato JSON</p>
          </div>
          <button @click="exportar('backup')" :disabled="loadingExport.backup"
            class="w-full bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white py-2 rounded-lg flex items-center justify-center gap-2 transition">
            <i :class="loadingExport.backup ? 'fas fa-spinner fa-spin' : 'fas fa-database'"></i>
            Exportar Tudo
          </button>
        </div>

        <!-- Produtos CSV -->
        <div class="bg-[#1e1e2e] border border-gray-700 rounded-lg p-4 space-y-3">
          <div>
            <p class="text-white font-semibold">Produtos (CSV)</p>
            <p class="text-gray-400 text-sm">Exporta cadastro de produtos com estoque atual</p>
          </div>
          <button @click="exportar('produtos-csv')" :disabled="loadingExport.produtos"
            class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white py-2 rounded-lg flex items-center justify-center gap-2 transition">
            <i :class="loadingExport.produtos ? 'fas fa-spinner fa-spin' : 'fas fa-file-csv'"></i>
            Exportar CSV
          </button>
        </div>

        <!-- Movimentações CSV -->
        <div class="bg-[#1e1e2e] border border-gray-700 rounded-lg p-4 space-y-3">
          <div>
            <p class="text-white font-semibold">Movimentações (CSV)</p>
            <p class="text-gray-400 text-sm">Exporta histórico de entradas e saídas</p>
          </div>
          <button @click="exportar('movimentacoes-csv')" :disabled="loadingExport.movimentacoes"
            class="w-full bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white py-2 rounded-lg flex items-center justify-center gap-2 transition">
            <i :class="loadingExport.movimentacoes ? 'fas fa-spinner fa-spin' : 'fas fa-sync'"></i>
            Exportar CSV
          </button>
        </div>
      </div>
    </div>

    <!-- Importar -->
    <div class="bg-[#1a1a2e] border border-gray-700 rounded-xl p-6">
      <h2 class="text-blue-400 font-semibold text-lg mb-4 flex items-center gap-2">
        <i class="fas fa-upload"></i> Importar Dados
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <!-- Importar Excel -->
        <div class="bg-[#1e1e2e] border border-gray-700 rounded-lg p-4 space-y-3">
          <div>
            <p class="text-white font-semibold">Planilha Excel (.xlsx)</p>
            <p class="text-gray-400 text-sm">
              Importa produtos do almoxarifado. Mesmo produto com validade diferente gera novo lote.
            </p>
          </div>
          <div class="space-y-2">
            <label class="text-gray-400 text-xs">Selecione o arquivo Excel</label>
            <label class="flex items-center gap-2 bg-[#2a2a3e] border border-gray-600 rounded-lg px-3 py-2 cursor-pointer hover:border-blue-500 transition">
              <i class="fas fa-folder-open text-gray-400"></i>
              <span class="text-gray-300 text-sm truncate">
                {{ arquivoExcel ? arquivoExcel.name : 'Nenhum arquivo escolhido' }}
              </span>
              <input type="file" accept=".xlsx,.xls" class="hidden" @change="onArquivoExcel" />
            </label>

            <!-- Preview das colunas esperadas -->
            <div class="bg-[#0f1a2e] border border-blue-900 rounded-lg px-3 py-2 text-xs text-blue-300 space-y-1">
              <p class="font-semibold text-blue-400">Colunas lidas da planilha:</p>
              <p>CÓDIGO · DESCRIÇÃO · UNIDADE · SALDO · VALIDADE</p>
            </div>

            <button @click="importarExcel"
              :disabled="!arquivoExcel || loadingImport.excel"
              class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white py-2 rounded-lg flex items-center justify-center gap-2 transition">
              <i :class="loadingImport.excel ? 'fas fa-spinner fa-spin' : 'fas fa-file-excel'"></i>
              {{ loadingImport.excel ? 'Importando...' : 'Importar Planilha' }}
            </button>
          </div>
        </div>

        <!-- Restaurar Backup -->
        <div class="bg-[#1e1e2e] border border-gray-700 rounded-lg p-4 space-y-3">
          <div>
            <p class="text-white font-semibold">Restaurar Backup</p>
            <p class="text-gray-400 text-sm">Restaura backup completo do sistema (JSON)</p>
          </div>
          <div class="space-y-2">
            <label class="text-gray-400 text-xs">Selecione o arquivo JSON</label>
            <label class="flex items-center gap-2 bg-[#2a2a3e] border border-gray-600 rounded-lg px-3 py-2 cursor-pointer hover:border-red-500 transition">
              <i class="fas fa-folder-open text-gray-400"></i>
              <span class="text-gray-300 text-sm truncate">
                {{ arquivoJSON ? arquivoJSON.name : 'Nenhum arquivo escolhido' }}
              </span>
              <input type="file" accept=".json" class="hidden" @change="onArquivoJSON" />
            </label>
            <div class="bg-red-900/40 border border-red-700 rounded-lg px-3 py-2 flex items-center gap-2 text-red-400 text-xs">
              <i class="fas fa-triangle-exclamation"></i>
              Substitui todos os dados atuais
            </div>
            <button @click="confirmarRestaurar"
              :disabled="!arquivoJSON || loadingImport.backup"
              class="w-full bg-red-700 hover:bg-red-800 disabled:opacity-40 text-white py-2 rounded-lg flex items-center justify-center gap-2 transition">
              <i :class="loadingImport.backup ? 'fas fa-spinner fa-spin' : 'fas fa-undo'"></i>
              {{ loadingImport.backup ? 'Restaurando...' : 'Restaurar' }}
            </button>
          </div>
        </div>

        <!-- Info formato -->
        <div class="bg-[#1a2744] border border-blue-800 rounded-lg p-4 space-y-3">
          <p class="text-blue-300 font-semibold flex items-center gap-2">
            <i class="fas fa-circle-info"></i> Como funciona a importação
          </p>
          <ul class="text-blue-200 text-xs space-y-2">
            <li>✅ Lê a planilha do almoxarifado diretamente</li>
            <li>✅ Ignora linhas de agrupamento (LETRA A, LETRA B...)</li>
            <li>✅ Converte validade serial do Excel automaticamente</li>
            <li>✅ Produtos sem código recebem SKU gerado pelo nome</li>
            <li>✅ <strong>Mesmo produto + validade diferente = novo lote</strong></li>
            <li>✅ Produto já existente tem estoque incrementado</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Resultado da importação -->
    <div v-if="resultadoImport" class="bg-[#1a2e1a] border border-green-700 rounded-xl p-5 space-y-2">
      <p class="text-green-400 font-semibold flex items-center gap-2">
        <i class="fas fa-check-circle"></i> {{ resultadoImport.message }}
      </p>
      <div class="grid grid-cols-3 gap-4 mt-2">
        <div class="text-center">
          <p class="text-2xl font-bold text-white">{{ resultadoImport.produtos_novos }}</p>
          <p class="text-gray-400 text-xs">Produtos criados</p>
        </div>
        <div class="text-center">
          <p class="text-2xl font-bold text-white">{{ resultadoImport.lotes_criados }}</p>
          <p class="text-gray-400 text-xs">Lotes criados</p>
        </div>
        <div class="text-center">
          <p class="text-2xl font-bold text-white">{{ resultadoImport.ignorados }}</p>
          <p class="text-gray-400 text-xs">Linhas ignoradas</p>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <transition name="fade">
      <div v-if="toast.show"
        :class="['fixed bottom-6 right-6 z-50 px-5 py-3 rounded-lg shadow-lg text-white flex items-center gap-3 text-sm',
          toast.type === 'success' ? 'bg-green-700' : 'bg-red-700']">
        <i :class="toast.type === 'success' ? 'fas fa-check-circle' : 'fas fa-circle-xmark'"></i>
        {{ toast.message }}
      </div>
    </transition>

    <!-- Modal confirmação restaurar -->
    <div v-if="modalRestaurar" class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center">
      <div class="bg-[#1e1e2e] border border-red-700 rounded-xl p-6 max-w-sm w-full mx-4 space-y-4">
        <p class="text-white font-semibold text-lg">⚠️ Atenção!</p>
        <p class="text-gray-300 text-sm">
          Esta ação irá <strong class="text-red-400">apagar todos os dados atuais</strong> e substituir pelo backup selecionado. Essa ação não pode ser desfeita.
        </p>
        <div class="flex gap-3">
          <button @click="modalRestaurar = false"
            class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg transition">
            Cancelar
          </button>
          <button @click="restaurarBackup"
            class="flex-1 bg-red-700 hover:bg-red-800 text-white py-2 rounded-lg transition">
            Confirmar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/servicos/api'

const stats          = ref({ lotes: 0, produtos: 0, movimentacoes: 0, itens_estoque: 0 })
const arquivoExcel   = ref(null)
const arquivoJSON    = ref(null)
const modalRestaurar = ref(false)
const resultadoImport = ref(null)

const loadingExport = ref({ backup: false, produtos: false, movimentacoes: false })
const loadingImport = ref({ excel: false, backup: false })
const toast         = ref({ show: false, message: '', type: 'success' })

const statsCards = [
  { label: 'Lotes',         key: 'lotes',         icon: 'fas fa-database',  bg: 'bg-blue-600'   },
  { label: 'Produtos',      key: 'produtos',       icon: 'fas fa-box',       bg: 'bg-green-600'  },
  { label: 'Movimentações', key: 'movimentacoes',  icon: 'fas fa-sync',      bg: 'bg-purple-600' },
  { label: 'Itens Estoque', key: 'itens_estoque',  icon: 'fas fa-warehouse', bg: 'bg-orange-600' },
]

function showToast(message, type = 'success') {
  toast.value = { show: true, message, type }
  setTimeout(() => (toast.value.show = false), 4000)
}

async function carregarStats() {
  try {
    const { data } = await api.get('/importacao-exportacao/stats')
    stats.value = data
  } catch {
    console.error('Erro ao carregar stats')
  }
}

async function exportar(tipo) {
  const chave = tipo === 'backup' ? 'backup' : tipo === 'produtos-csv' ? 'produtos' : 'movimentacoes'
  loadingExport.value[chave] = true
  try {
    const response = await api.get(`/importacao-exportacao/exportar/${tipo}`, { responseType: 'blob' })
    const ext  = tipo === 'backup' ? 'json' : 'csv'
    const nome = `${tipo}_${new Date().toISOString().slice(0, 10)}.${ext}`
    const url  = URL.createObjectURL(new Blob([response.data]))
    const a    = document.createElement('a')
    a.href = url; a.download = nome; a.click()
    URL.revokeObjectURL(url)
    showToast('Exportação concluída!')
  } catch {
    showToast('Erro ao exportar.', 'error')
  } finally {
    loadingExport.value[chave] = false
  }
}

function onArquivoExcel(e) {
  const file = e.target.files[0]
  console.log('Arquivo selecionado:', file)
  arquivoExcel.value = file
}
function onArquivoJSON(e)  { arquivoJSON.value  = e.target.files[0] }

async function importarExcel() {
  if (!arquivoExcel.value) return
  loadingImport.value.excel = true
  resultadoImport.value = null
  const form = new FormData()
  form.append('arquivo', arquivoExcel.value)
  try {
    const { data } = await api.post('/importacao-exportacao/importar/excel', form, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    resultadoImport.value = data
    showToast(data.message)
    arquivoExcel.value = null
    carregarStats()
  } catch (err) {
    console.log('ERRO:', JSON.stringify(err.response?.data))
    showToast(err.response?.data?.message ?? 'Erro ao importar planilha.', 'error')
  } finally {
    loadingImport.value.excel = false
  }
}

function confirmarRestaurar() {
  if (!arquivoJSON.value) return
  modalRestaurar.value = true
}

async function restaurarBackup() {
  modalRestaurar.value = false
  loadingImport.value.backup = true
  const form = new FormData()
  form.append('arquivo', arquivoJSON.value)
  try {
    const { data } = await api.post('/importacao-exportacao/restaurar/backup', form)
    showToast(data.message)
    arquivoJSON.value = null
    carregarStats()
  } catch (err) {
    showToast(err.response?.data?.message ?? 'Erro ao restaurar backup.', 'error')
  } finally {
    loadingImport.value.backup = false
  }
}

onMounted(carregarStats)
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>