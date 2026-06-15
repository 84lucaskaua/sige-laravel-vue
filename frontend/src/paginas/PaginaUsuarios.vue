<template>
  <div class="p-6">

    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Usuários</h1>
      <button @click="abrirModalNovoUsuario" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
        + Novo Usuário
      </button>
    </div>

    <!-- Carregando -->
    <div v-if="carregando" class="text-center py-12 text-gray-500">Carregando...</div>

    <!-- Sem usuários -->
    <div v-else-if="usuarios.length === 0" class="text-center py-12 text-gray-500">
      Nenhum usuário encontrado.
    </div>

    <!-- Tabela de usuários -->
    <div v-else class="bg-white rounded-xl shadow overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Nome</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Email</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Perfil</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Cadastrado em</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="usuario in usuarios" :key="usuario.id" class="border-b hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ usuario.nome }}</td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ usuario.email }}</td>
            <td class="px-4 py-3">
              <!-- Badge colorido por perfil -->
              <span :class="corDoPerfil[usuario.perfil]" class="px-2 py-1 rounded-full text-xs font-medium capitalize">
                {{ usuario.perfil }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
              {{ formatarData(usuario.created_at) }}
            </td>
            <td class="px-4 py-3 flex gap-3">
              <button @click="abrirModalEdicao(usuario)" class="text-blue-600 hover:text-blue-800 text-sm">Editar</button>
              <button
                @click="desativarUsuario(usuario)"
                :disabled="usuario.id === autenticacao.usuario?.id"
                class="text-red-500 hover:text-red-700 text-sm disabled:opacity-30 disabled:cursor-not-allowed"
              >
                Remover
              </button>
            </td>
          </tr>
        </tbody>
      </table>
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
import { ref, onMounted } from 'vue'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'
import ModalUsuario from '@/componentes/ui/ModalUsuario.vue'
import { formatarData } from '@/utils/date'

const autenticacao = useAutenticacaoStore()
const usuarios          = ref([])
const carregando        = ref(false)
const modalAberto       = ref(false)
const usuarioSelecionado = ref(null)

// Cores diferentes para cada perfil
const corDoPerfil = {
  admin:         'bg-purple-100 text-purple-700',
  operador:      'bg-blue-100 text-blue-700',
  visualizador:  'bg-gray-100 text-gray-600',
}

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

function aoSalvar() {
  fecharModal()
  carregarUsuarios()
}

async function desativarUsuario(usuario) {
  if (!confirm(`Remover o usuário "${usuario.nome}"?`)) return
  try {
    await api.delete(`/usuarios/${usuario.id}`)
    carregarUsuarios()
  } catch (erro) {
    alert(erro.response?.data?.mensagem || 'Erro ao remover usuário.')
  }
}

onMounted(carregarUsuarios)
</script>
