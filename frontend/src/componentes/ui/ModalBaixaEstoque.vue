<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50" @click.self="$emit('fechar')">
    <div class="bg-slate-900 border border-slate-700 rounded-xl p-6 w-full max-w-md">

      <div class="flex justify-between items-center mb-6">
        <div>
          <h2 class="text-lg font-bold text-white">Baixa de Estoque</h2>
          <p class="text-slate-400 text-xs mt-0.5">Registre a saída de produtos do estoque.</p>
        </div>
        <button @click="$emit('fechar')" class="text-slate-400 hover:text-white">
          <X :size="20" />
        </button>
      </div>

      <!-- Info do produto -->
      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 mb-6">
        <p class="text-slate-400 text-xs mb-1">Produto</p>
        <p class="text-white font-bold text-base">{{ item.nome }}</p>
        <p class="text-slate-400 text-xs mt-2">Estoque disponível</p>
        <p class="text-white font-bold text-xl">{{ item.quantidade }}</p>
      </div>

      <form @submit.prevent="confirmar">

        <div class="mb-4">
          <label class="label">Quantidade para baixa *</label>
          <input
            v-model.number="form.quantidade"
            type="number"
            min="1"
            :max="item.quantidade"
            required
            class="campo"
            placeholder="Ex: 10"
          />
        </div>

        <div class="mb-6">
          <label class="label">Motivo (opcional)</label>
          <textarea
            v-model="form.motivo"
            rows="3"
            class="campo"
            placeholder="Ex: Venda, Perda, Uso interno..."
          />
        </div>

        <div v-if="erro" class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded text-red-400 text-sm">
          {{ erro }}
        </div>

        <div class="flex justify-end gap-3">
          <button type="button" @click="$emit('fechar')"
            class="px-4 py-2 border border-slate-600 text-slate-300 rounded-lg hover:bg-slate-800 transition">
            Cancelar
          </button>
          <button type="submit" :disabled="salvando"
            class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 disabled:opacity-50 transition font-medium">
            {{ salvando ? 'Confirmando...' : 'Confirmar Baixa' }}
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { X } from 'lucide-vue-next'
import api from '@/servicos/api'

const props = defineProps({
  item: { type: Object, required: true },
})
const emit = defineEmits(['fechar', 'salvo'])

const salvando = ref(false)
const erro     = ref('')

const form = ref({
  quantidade: null,
  motivo:     '',
})

async function confirmar() {
  erro.value = ''
  if (form.value.quantidade > props.item.quantidade) {
    erro.value = 'Quantidade maior que o estoque disponível.'
    return
  }
  salvando.value = true
  try {
    await api.patch(`/itens/${props.item.id_item}/baixa`, {
      quantidade: form.value.quantidade,
      motivo:     form.value.motivo,
    })
    emit('salvo')
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao registrar baixa.'
  } finally {
    salvando.value = false
  }
}
</script>

<style scoped>
.label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #cbd5e1;
  margin-bottom: 0.25rem;
}
.campo {
  width: 100%;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  color: #f1f5f9;
  outline: none;
  resize: none;
}
.campo:focus {
  border-color: #f97316;
  box-shadow: 0 0 0 2px rgba(249,115,22,0.2);
}
</style>