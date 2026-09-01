<template>
  <div class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-6 w-full max-w-lg">

      <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Transferir Item</h2>
        <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="emit('fechar')">
          <X :size="20" />
        </button>
      </div>

      <div class="mb-4 p-3 bg-slate-100 dark:bg-slate-800 rounded-lg text-sm">
        <p class="text-slate-900 dark:text-white font-medium">{{ item?.produto?.nome || '—' }}</p>
        <p class="text-slate-500 dark:text-slate-400">
          Disponível no lote atual: {{ item?.quantidade }} {{ item?.unidade_medida }}
        </p>
      </div>

      <form @submit.prevent="salvar">

        <div class="mb-4">
          <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Lote de Destino *</label>
          <select v-model="formulario.id_lote_destino" required class="campo">
            <option value="" disabled>Selecione um lote</option>
            <option
              v-for="lote in lotesDisponiveis"
              :key="lote.id_lote"
              :value="lote.id_lote"
            >
              {{ lote.numero_lote }}
            </option>
          </select>
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Quantidade *</label>
          <input
            v-model.number="formulario.quantidade"
            type="number"
            required
            min="1"
            :max="item?.quantidade"
            class="campo"
          />
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
            Máximo: {{ item?.quantidade }} {{ item?.unidade_medida }}
          </p>
        </div>

        <div v-if="erro" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded text-red-600 dark:text-red-400 text-sm">
          {{ erro }}
        </div>

        <div class="flex justify-end gap-3">
          <button type="button" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition" @click="emit('fechar')">
            Cancelar
          </button>
          <button type="submit" :disabled="salvando" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition">
            {{ salvando ? 'Transferindo...' : 'Transferir' }}
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
  item:               { type: Object, required: true },
  lotes:              { type: Array, required: true },
  loteDestinoInicial: { type: [Number, String], default: null },
})
const emit = defineEmits(['fechar', 'salvo'])

const salvando = ref(false)
const erro     = ref('')

const lotesDisponiveis = computed(() =>
  props.lotes.filter(l => l.id_lote !== props.item.id_lote)
)

const formulario = ref({
  id_lote_destino: props.loteDestinoInicial ?? '',
  quantidade:      props.item.quantidade === 1 ? 1 : null,
})

async function salvar() {
  erro.value     = ''
  salvando.value = true
  try {
    await api.patch(`/itens/${props.item.id_item}/transferir`, {
      id_lote_destino: formulario.value.id_lote_destino,
      quantidade:      formulario.value.quantidade,
    })
    emit('salvo')
  } catch (erroHttp) {
    const errosValidacao = erroHttp.response?.data?.errors
    if (errosValidacao) {
      erro.value = Object.values(errosValidacao).flat().join('. ')
    } else {
      erro.value = erroHttp.response?.data?.message || 'Erro ao transferir item.'
    }
  } finally {
    salvando.value = false
  }
}
</script>

<style scoped>
.campo {
  width: 100%;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.campo {
  background-color: rgb(241 245 249);
  border: 1px solid rgb(203 213 225);
  color: rgb(15 23 42);
}
.dark .campo {
  background-color: rgb(30 41 59);
  border-color: rgb(71 85 105);
  color: rgb(255 255 255);
}
.campo::placeholder {
  color: rgb(148 163 184);
}
.dark .campo::placeholder {
  color: rgb(100 116 139);
}
.campo:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
}
</style>