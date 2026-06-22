<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50" @click.self="$emit('fechar')">
    <div class="bg-slate-900 border border-slate-700 rounded-xl p-6 w-full max-w-lg">

      <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-bold text-white">
          {{ ehEdicao ? 'Editar Lote' : 'Novo Lote' }}
        </h2>
        <button @click="$emit('fechar')" class="text-slate-400 hover:text-white">
          <X :size="20" />
        </button>
      </div>

      <form @submit.prevent="salvar">

        <div class="mb-4">
          <label class="block text-sm font-medium text-slate-300 mb-1">Número do Lote *</label>
          <input
            v-model="formulario.numero"
            type="text"
            required
            class="campo"
            placeholder="Ex: LOT-2024-001"
          />
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-slate-300 mb-1">Data de Entrada *</label>
          <input v-model="formulario.data_entrada" type="date" required class="campo" />
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium text-slate-300 mb-1">Descrição</label>
          <input v-model="formulario.descricao" type="text" class="campo" placeholder="Ex: Compra mensal de janeiro" />
        </div>

        <div v-if="erro" class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded text-red-400 text-sm">
          {{ erro }}
        </div>

        <div class="flex justify-end gap-3">
          <button type="button" @click="$emit('fechar')" class="px-4 py-2 border border-slate-600 text-slate-300 rounded-lg hover:bg-slate-800 transition">
            Cancelar
          </button>
          <button type="submit" :disabled="salvando" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition">
            {{ salvando ? 'Salvando...' : 'Salvar Lote' }}
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { X } from 'lucide-vue-next'
import api from '@/servicos/api'

const props = defineProps({
  lote: { type: Object, default: null },
})
const emit = defineEmits(['fechar', 'salvo'])

const ehEdicao = computed(() => !!props.lote)
const salvando = ref(false)
const erro     = ref('')

const formulario = ref({
  numero:       props.lote?.numero_lote  || '',
  data_entrada: props.lote?.data_entrada || new Date().toISOString().split('T')[0],
  descricao:    props.lote?.descricao    || '',
})

async function salvar() {
  erro.value     = ''
  salvando.value = true
  try {
    if (ehEdicao.value) {
      await api.put(`/lotes/${props.lote.id_lote}`, formulario.value)
    } else {
      await api.post('/lotes', formulario.value)
    }
    emit('salvo')
  } catch (erroHttp) {
    const errosValidacao = erroHttp.response?.data?.errors
    if (errosValidacao) {
      erro.value = Object.values(errosValidacao).flat().join('. ')
    } else {
      erro.value = erroHttp.response?.data?.message || 'Erro ao salvar lote.'
    }
  } finally {
    salvando.value = false
  }
}
</script>

<style scoped>
.campo {
  width: 100%;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  color: #f1f5f9;
  outline: none;
}
.campo:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
}
option {
  background: #1e293b;
}
</style>