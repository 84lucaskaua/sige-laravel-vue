<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">

      <div class="flex justify-between items-center mb-6">
        <div>
          <h2 class="text-lg font-bold text-slate-900 dark:text-white">Editar Item</h2>
          <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">Modifique quantidade, unidade, validade e localização.</p>
        </div>
        <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="$emit('fechar')">
          <X :size="20" />
        </button>
      </div>

      <!-- Dados do produto: somente leitura. Editar nome/SKU/categoria/fornecedor é feito na tela de Produtos. -->
      <div class="mb-5 p-3 rounded-lg bg-slate-100 dark:bg-slate-800 text-sm">
        <p class="text-slate-900 dark:text-white font-medium">{{ item.produto?.nome || '—' }}</p>
        <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">
          SKU: {{ item.produto?.sku || '—' }}
          <span v-if="item.produto?.categoria?.nome"> · Categoria: {{ item.produto.categoria.nome }}</span>
          <span v-if="item.produto?.fornecedor?.nome"> · Fornecedor: {{ item.produto.fornecedor.nome }}</span>
        </p>
      </div>

      <form @submit.prevent="salvar">

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="label">Quantidade *</label>
            <input v-model.number="form.quantidade" type="number" min="1" required class="campo" />
          </div>
          <div>
            <label class="label">Unidade *</label>
            <select v-model="form.unidade_medida" class="campo">
              <option value="UN">UN — Unidade</option>
              <option value="CX">CX — Caixa</option>
              <option value="PCT">PCT — Pacote</option>
              <option value="PTC">PTC — Pacote (variação)</option>
              <option value="FR">FR — Frasco</option>
              <option value="RL">RL — Rolo</option>
              <option value="EMB">EMB — Embalagem</option>
              <option value="KIT">KIT — Kit</option>
              <option value="BEM">BEM — Bem</option>
              <option value="UM">UM — Unidade de Medida</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="label">Validade</label>
            <input v-model="form.data_validade" type="date" class="campo campo-data" />
          </div>
          <div>
            <label class="label">Localização / Prateleira</label>
            <input v-model="form.localizacao" type="text" class="campo" placeholder="Ex: A-12" />
          </div>
        </div>

        <div class="mb-6">
          <label class="label">Prioridade Manual</label>
          <select v-model="form.prioridade_abc" class="campo">
            <option value="">Automática</option>
            <option value="A">A — Alta</option>
            <option value="B">B — Média</option>
            <option value="C">C — Baixa</option>
          </select>
        </div>

        <div v-if="erro" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded text-red-600 dark:text-red-400 text-sm">
          {{ erro }}
        </div>

        <div class="flex justify-end gap-3">
          <button
            type="button"
            class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            @click="$emit('fechar')"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="salvando"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
          >
            {{ salvando ? 'Salvando...' : 'Salvar Alterações' }}
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

// Só campos que o backend (ItemLoteController@update) realmente salva
const form = ref({
  quantidade:      props.item.quantidade     ?? null,
  unidade_medida:  props.item.unidade_medida || 'UN',
  data_validade:   props.item.data_validade  || '',
  localizacao:     props.item.localizacao    || '',
  prioridade_abc:  props.item.prioridade_abc || '',
})

async function salvar() {
  erro.value     = ''
  salvando.value = true
  try {
    await api.put(`/itens/${props.item.id_item}`, form.value)
    emit('salvo')
  } catch (e) {
    const erros = e.response?.data?.errors
    erro.value = erros
      ? Object.values(erros).flat().join('. ')
      : e.response?.data?.message || 'Erro ao salvar item.'
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
  color: var(--muted-foreground);
  margin-bottom: 0.25rem;
}
.campo {
  width: 100%;
  background: var(--input);
  border: 1px solid var(--border);
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  color: var(--foreground);
  outline: none;
}
.campo:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
}
option {
  background: var(--card);
  color: var(--foreground);
}
.campo-data {
  color-scheme: light;
}
:global(.dark) .campo-data {
  color-scheme: dark;
}
</style>