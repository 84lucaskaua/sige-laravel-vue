<template>
  <div
    class="fixed inset-0 flex items-center justify-center z-50"
    :class="temaClaro ? 'bg-black/40' : 'bg-black/60'"
    @click.self="$emit('fechar')"
  >
    <div
      class="rounded-xl shadow-xl p-6 w-full max-w-md"
      :class="temaClaro ? 'bg-white' : 'bg-slate-900'"
    >
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-bold" :class="tipo === 'entrada' ? 'text-green-600' : 'text-red-500'">
          {{ tipo === 'entrada' ? '⬆️ Registrar Entrada' : '⬇️ Registrar Saída' }}
        </h2>
        <button
          @click="$emit('fechar')"
          class="text-xl transition"
          :class="temaClaro ? 'text-gray-400 hover:text-gray-600' : 'text-slate-500 hover:text-white'"
        >✕</button>
      </div>

      <form @submit.prevent="salvar">

        <div class="mb-4">
          <label class="block text-sm font-medium mb-1" :class="temaClaro ? 'text-gray-700' : 'text-slate-300'">Produto / Item de Lote *</label>
          <select v-model="formulario.item_lote_id" required :class="campoClasse">
            <option value="">Selecione um produto...</option>
            <option v-for="item in itensLote" :key="item.id" :value="item.id">
              {{ item.produto?.nome }} — Lote {{ item.lote?.numero }} ({{ item.quantidade }} disponíveis)
            </option>
          </select>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium mb-1" :class="temaClaro ? 'text-gray-700' : 'text-slate-300'">Quantidade *</label>
          <input v-model="formulario.quantidade" type="number" min="1" required :class="campoClasse" placeholder="Ex: 10" />
        </div>

        <div v-if="tipo === 'entrada'" class="mb-4">
          <label class="block text-sm font-medium mb-1" :class="temaClaro ? 'text-gray-700' : 'text-slate-300'">Fornecedor</label>
          <input v-model="formulario.fornecedor" type="text" :class="campoClasse" placeholder="Nome do fornecedor" />
        </div>

        <div v-if="tipo === 'saida'" class="mb-4">
          <label class="block text-sm font-medium mb-1" :class="temaClaro ? 'text-gray-700' : 'text-slate-300'">Motivo da saída *</label>
          <select v-model="formulario.motivo" required :class="campoClasse">
            <option value="">Selecione o motivo...</option>
            <option value="Uso na cozinha">Uso na cozinha</option>
            <option value="Uso em limpeza">Uso em limpeza</option>
            <option value="Descarte por vencimento">Descarte por vencimento</option>
            <option value="Quebra ou avaria">Quebra ou avaria</option>
            <option value="Transferência">Transferência</option>
            <option value="Outro">Outro</option>
          </select>
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium mb-1" :class="temaClaro ? 'text-gray-700' : 'text-slate-300'">Observação</label>
          <textarea v-model="formulario.observacao" rows="2" :class="campoClasse" placeholder="Opcional"></textarea>
        </div>

        <div v-if="erro" class="mb-4 p-3 border rounded text-sm" :class="temaClaro ? 'bg-red-50 border-red-200 text-red-600' : 'bg-red-900/30 border-red-700 text-red-400'">
          {{ erro }}
        </div>

        <div class="flex justify-end gap-3">
          <button
            type="button"
            @click="$emit('fechar')"
            class="px-4 py-2 border rounded-lg transition text-sm"
            :class="temaClaro ? 'border-gray-300 text-gray-600 hover:bg-gray-50' : 'border-slate-600 text-slate-300 hover:bg-slate-800'"
          >Cancelar</button>
          <button
            type="submit"
            :disabled="salvando"
            :class="tipo === 'entrada' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'"
            class="px-4 py-2 text-white rounded-lg disabled:opacity-50 transition text-sm"
          >{{ salvando ? 'Salvando...' : 'Confirmar' }}</button>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/servicos/api'
import { useTemaStore } from '@/servicos/tema.store'
import { storeToRefs } from 'pinia'

const props = defineProps({
  tipo: { type: String, required: true },
})
const emit = defineEmits(['fechar', 'salvo'])

const temaStore = useTemaStore()
const { temaClaro } = storeToRefs(temaStore)

const campoClasse = computed(() =>
  temaClaro.value
    ? 'campo-texto bg-white border-gray-300 text-gray-900 placeholder-gray-400'
    : 'campo-texto bg-slate-800 border-slate-600 text-white placeholder-slate-500'
)

const itensLote = ref([])
const salvando  = ref(false)
const erro      = ref('')

const formulario = ref({
  item_lote_id: '',
  quantidade:   1,
  fornecedor:   '',
  motivo:       '',
  observacao:   '',
})

async function carregarItensLote() {
  try {
    const resposta = await api.get('/lotes/itens')
    itensLote.value = resposta.data
  } catch {}
}

async function salvar() {
  erro.value     = ''
  salvando.value = true
  try {
    const endpoint = props.tipo === 'entrada' ? '/movimentos/entrada' : '/movimentos/saida'
    await api.post(endpoint, formulario.value)
    emit('salvo')
  } catch (erroHttp) {
    erro.value = erroHttp.response?.data?.mensagem
      || erroHttp.response?.data?.message
      || 'Erro ao registrar movimento.'
  } finally {
    salvando.value = false
  }
}

onMounted(carregarItensLote)
</script>

<style scoped>
.campo-texto {
  width: 100%;
  border: 1px solid;
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