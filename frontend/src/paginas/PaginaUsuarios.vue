<template>
  <div class="p-6 min-h-screen bg-black text-white">

    <!-- Cabeçalho -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-white">Gerenciamento de Usuários</h1>
        <p class="text-sm text-slate-400">Cadastre e gerencie usuários do sistema</p>
      </div>
      <button
        @click="abrirModalNovoUsuario"
        class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-medium"
      >
        <Plus :size="18" />
        Novo Usuário
      </button>
    </div>

    <!-- Busca -->
    <div class="relative mb-6">
      <Search :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" />
      <input
        v-model="busca"
        type="text"
        placeholder="Buscar por nome ou email..."
        class="w-full bg-slate-900 border border-slate-800 rounded-lg pl-10 pr-4 py-2.5 text-white placeholder-slate-500 outline-none focus:border-blue-500 transition"
      />
    </div>

    <!-- Carregando -->
    <div v-if="carregando" class="text-center py-12 text-slate-400">
      Carregando usuários...
    </div>

    <!-- Sem usuários -->
    <div v-else-if="usuariosFiltrados.length === 0" class="rounded-xl bg-slate-900 border border-slate-800 p-16 text-center">
      <UserX class="mx-auto mb-4 text-slate-600" :size="48" />
      <h2 class="text-xl font-bold text-white mb-2">Nenhum usuário encontrado</h2>
      <p class="text-slate-400">
        {{ busca ? 'Tente buscar por outro nome ou email.' : 'Crie o primeiro usuário do sistema.' }}
      </p>
    </div>

    <!-- Tabela de usuários -->
    <div v-else class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden mb-6">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-slate-400 border-b border-slate-800">
            <th class="text-left px-6 py-4 font-medium">Nome</th>
            <th class="text-left px-6 py-4 font-medium">Email</th>
            <th class="text-left px-6 py-4 font-medium">Tipo</th>
            <th class="text-left px-6 py-4 font-medium">Data de Cadastro</th>
            <th class="text-right px-6 py-4 font-medium">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <tr v-for="usuario in usuariosFiltrados" :key="usuario.id" class="hover:bg-slate-800/50 transition">
            <td class="px-6 py-4 text-white font-medium">{{ usuario.name }}</td>
            <td class="px-6 py-4 text-slate-400">{{ usuario.email }}</td>
            <td class="px-6 py-4">
              <span :class="corDoPerfil[usuario.perfil]" class="px-3 py-1 rounded-md text-xs font-semibold">
                {{ nomeDoPerfil[usuario.perfil] || usuario.perfil }}
              </span>
            </td>
            <td class="px-6 py-4 text-slate-400">{{ formatarData(usuario.created_at) }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center justify-end gap-2">
                <button
                  @click="abrirModalEdicao(usuario)"
                  class="p-2 rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-white transition"
                  title="Editar"
                >
                  <Pencil :size="16" />
                </button>
                <button
                  @click="excluirUsuario(usuario)"
                  :disabled="usuario.id === autenticacao.usuario?.id"
                  class="p-2 rounded-lg border border-red-800 bg-red-900/30 text-red-400 hover:bg-red-900/50 transition disabled:opacity-30 disabled:cursor-not-allowed"
                  title="Excluir"
                >
                  <Trash2 :size="16" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Cards de totais -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
        <p class="text-sm text-slate-400 mb-2">Total de Usuários</p>
        <p class="text-3xl font-bold text-white">{{ usuarios.length }}</p>
      </div>
      <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
        <p class="text-sm text-slate-400 mb-2">Administradores</p>
        <p class="text-3xl font-bold text-blue-500">{{ totalAdministradores }}</p>
      </div>
    </div>

    <!-- Modal -->
    <ModalUsuario
      v-if="modalAberto"
      :usuario="usuarioSelecionado"
      @fechar="fecharModal"
      @salvo="aoSalvar"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Plus, Search, UserX, Pencil, Trash2 } from 'lucide-vue-next'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'
import ModalUsuario from '@/componentes/ui/ModalUsuario.vue'
import { formatarData } from '@/utils/date'

const autenticacao = useAutenticacaoStore()

const usuarios           = ref([])
const carregando         = ref(false)
const busca              = ref('')
const modalAberto        = ref(false)
const usuarioSelecionado = ref(null)

const corDoPerfil = {
  root:         'bg-blue-600 text-white',
  operador:     'bg-slate-700 text-slate-200',
  visualizador: 'bg-slate-700 text-slate-200',
}

const nomeDoPerfil = {
  root:         'Administrador',
  operador:     'Operador',
  visualizador: 'Visualizador',
}

const usuariosFiltrados = computed(() => {
  const termo = busca.value.trim().toLowerCase()
  if (!termo) return usuarios.value
  return usuarios.value.filter(u =>
    u.name?.toLowerCase().includes(termo) ||
    u.email?.toLowerCase().includes(termo)
  )
})

const totalAdministradores = computed(() =>
  usuarios.value.filter(u => u.perfil === 'root').length
)

async function carregarUsuarios() {
  carregando.value = true
  try {
    const resposta = await api.get('/usuarios')
    usuarios.value = resposta.data
  } catch {
    alert('Erro ao carregar usuários.')
  } finally {
    carregando.value = false
  }
}

function abrirModalNovoUsuario() {
  usuarioSelecionado.value = null
  modalAberto.value = true
}

function abrirModalEdicao(usuario) {
  usuarioSelecionado.value = usuario
  modalAberto.value = true
}

function fecharModal() {
  modalAberto.value = false
  usuarioSelecionado.value = null
}

async function aoSalvar() {
  fecharModal()
  await carregarUsuarios()
}

async function excluirUsuario(usuario) {
  if (!confirm(`Excluir o usuário "${usuario.name}"? Esta ação não pode ser desfeita.`)) return
  try {
    await api.delete(`/usuarios/${usuario.id}`)
    await carregarUsuarios()
  } catch (erro) {
    alert(erro.response?.data?.mensagem || erro.response?.data?.message || 'Erro ao excluir usuário.')
  }
}

onMounted(carregarUsuarios)
</script>