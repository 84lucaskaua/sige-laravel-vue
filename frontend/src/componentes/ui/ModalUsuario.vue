<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('fechar')">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">

      <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-bold text-gray-800">
          {{ ehEdicao ? 'Editar Usuário' : 'Novo Usuário' }}
        </h2>
        <button @click="$emit('fechar')" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
      </div>

      <form @submit.prevent="salvar">

        <!-- Nome -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Nome completo *</label>
          <input v-model="formulario.nome" type="text" required class="campo-texto" />
        </div>

        <!-- Email -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
          <input v-model="formulario.email" type="email" required class="campo-texto" />
        </div>

        <!-- Perfil -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Perfil de acesso *</label>
          <select v-model="formulario.perfil" required class="campo-texto">
            <option value="visualizador">Visualizador — só leitura</option>
            <option value="operador">Operador — pode movimentar estoque</option>
            <option value="admin">Admin — acesso total</option>
          </select>
        </div>

        <!-- Senha (obrigatória na criação, opcional na edição) -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Senha {{ ehEdicao ? '(deixe em branco para não alterar)' : '*' }}
          </label>
          <input
            v-model="formulario.senha"
            type="password"
            :required="!ehEdicao"
            class="campo-texto"
            placeholder="Mínimo 8 caracteres"
          />
        </div>

        <!-- Confirmação de senha (só aparece se digitou uma nova senha) -->
        <div v-if="formulario.senha" class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar senha *</label>
          <input v-model="formulario.senha_confirmation" type="password" required class="campo-texto" />
        </div>

        <!-- Erro -->
        <div v-if="erro" class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-600 text-sm">
          {{ erro }}
        </div>

        <div class="flex justify-end gap-3">
          <button type="button" @click="$emit('fechar')" class="px-4 py-2 text-gray-600 border rounded-lg hover:bg-gray-50">
            Cancelar
          </button>
          <button type="submit" :disabled="salvando" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ salvando ? 'Salvando...' : 'Salvar' }}
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import api from '@/servicos/api'

const props = defineProps({
  usuario: { type: Object, default: null },
})
const emit = defineEmits(['fechar', 'salvo'])

const ehEdicao = computed(() => !!props.usuario)
const salvando = ref(false)
const erro     = ref('')

const formulario = ref({
  nome:                 props.usuario?.nome   || '',
  email:                props.usuario?.email  || '',
  perfil:               props.usuario?.perfil || 'visualizador',
  senha:                '',
  senha_confirmation:   '',
})

async function salvar() {
  erro.value    = ''
  salvando.value = true

  try {
    if (ehEdicao.value) {
      await api.put(`/usuarios/${props.usuario.id}`, formulario.value)
    } else {
      await api.post('/usuarios', formulario.value)
    }
    emit('salvo')
  } catch (erroHttp) {
    const errosValidacao = erroHttp.response?.data?.errors
    if (errosValidacao) {
      erro.value = Object.values(errosValidacao).flat().join('. ')
    } else {
      erro.value = erroHttp.response?.data?.message || 'Erro ao salvar usuário.'
    }
  } finally {
    salvando.value = false
  }
}
</script>

<style scoped>
.campo-texto {
  width: 100%;
  border: 1px solid #D1D5DB;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
}
.campo-texto:focus {
  border-color: #3B82F6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
}
</style>
