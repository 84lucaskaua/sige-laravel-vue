<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50" @click.self="$emit('fechar')">
    <div class="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-md p-6">

      <!-- Cabeçalho -->
      <div class="flex justify-between items-start mb-5">
        <div class="flex items-center gap-2">
          <Trash2 class="text-red-400" :size="20" />
          <h2 class="text-white font-bold">Confirmar Exclusão</h2>
        </div>
        <button @click="$emit('fechar')" class="text-slate-400 hover:text-white">
          <X :size="18" />
        </button>
      </div>

      <!-- Aviso -->
      <div class="bg-red-900/20 border border-red-800 rounded-lg p-4 mb-5">
        <p class="text-slate-300 text-sm">
          Tem certeza que deseja excluir o item
          <strong class="text-white">"{{ item.nome }}"</strong>?
          Esta ação não pode ser desfeita.
        </p>
      </div>

      <!-- Double check -->
      <div class="mb-6">
        <label class="block text-sm text-slate-400 mb-2">
          Digite <strong class="text-red-400">Confirmar Exclusão</strong> para prosseguir
        </label>
        <input
          v-model="confirmacaoTexto"
          type="text"
          placeholder="Confirmar Exclusão"
          class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-slate-100 outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition"
          @paste.prevent
        />
      </div>

      <div v-if="erro" class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded text-red-400 text-sm">
        {{ erro }}
      </div>

      <div class="flex justify-end gap-3">
        <button @click="$emit('fechar')"
          class="px-4 py-2 border border-slate-600 text-slate-300 rounded-lg hover:bg-slate-800 transition text-sm">
          Cancelar
        </button>
        <button
          @click="confirmar"
          :disabled="confirmacaoTexto !== 'Confirmar Exclusão' || excluindo"
          class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed transition text-sm font-medium"
        >
          {{ excluindo ? 'Excluindo...' : 'Excluir' }}
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { X, Trash2 } from 'lucide-vue-next'
import api from '@/servicos/api'

const props = defineProps({
  item: { type: Object, required: true },
})
const emit = defineEmits(['fechar', 'salvo'])

const confirmacaoTexto = ref('')
const excluindo        = ref(false)
const erro             = ref('')

async function confirmar() {
  if (confirmacaoTexto.value !== 'Confirmar Exclusão') return

  excluindo.value = true
  erro.value      = ''
  try {
    await api.delete(`/itens/${props.item.id_item}`)
    emit('salvo')
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao excluir item.'
  } finally {
    excluindo.value = false
  }
}
</script>