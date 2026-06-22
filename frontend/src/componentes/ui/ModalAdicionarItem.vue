<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50" @click.self="$emit('fechar')">
    <div class="bg-slate-900 border border-slate-700 rounded-xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">

      <div class="flex justify-between items-center mb-6">
        <div>
          <h2 class="text-lg font-bold text-white">Adicionar Item ao Lote {{ lote?.numero_lote }}</h2>
          <p class="text-slate-400 text-xs mt-0.5">Preencha as informações do produto para adicionar ao lote.</p>
        </div>
        <button @click="$emit('fechar')" class="text-slate-400 hover:text-white">
          <X :size="20" />
        </button>
      </div>

      <form @submit.prevent="salvar">

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="label">Código / SKU</label>
            <input v-model="form.sku" type="text" class="campo" placeholder="Ex: PROD001" />
          </div>
          <div>
            <label class="label">Nome do Produto *</label>
            <input v-model="form.nome" type="text" required class="campo" placeholder="Ex: Arroz Integral" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="label">Quantidade *</label>
            <input v-model.number="form.quantidade" type="number" min="1" required class="campo" placeholder="Ex: 50" />
          </div>
          <div>
            <label class="label">Unidade *</label>
            <select v-model="form.unidade_medida" class="campo">
              <option value="UN">UN — Unidade</option>
              <option value="KG">KG — Quilograma</option>
              <option value="L">L — Litro</option>
              <option value="CX">CX — Caixa</option>
              <option value="PCT">PCT — Pacote</option>
              <option value="M">M — Metro</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="label">Estoque Mínimo</label>
            <input v-model.number="form.estoque_minimo" type="number" min="0" class="campo" placeholder="Ex: 10" />
          </div>
          <div>
            <label class="label">Validade</label>
            <input v-model="form.data_validade" type="date" class="campo" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="label">Fornecedor</label>
            <input v-model="form.fornecedor" type="text" class="campo" placeholder="Ex: Fornecedor ABC" />
          </div>
          <div>
            <label class="label">Localização / Prateleira</label>
            <input v-model="form.localizacao" type="text" class="campo" placeholder="Ex: A-12" />
          </div>
        </div>

        <div class="mb-4">
          <label class="label">Prioridade Manual</label>
          <select v-model="form.prioridade_abc" class="campo">
            <option value="">Automática</option>
            <option value="A">A — Alta</option>
            <option value="B">B — Média</option>
            <option value="C">C — Baixa</option>
          </select>
        </div>

     <div class="mb-6">
  <label class="label">Categoria *</label>
  <select v-model="form.categoria" required class="campo">
    <option value="" disabled>Selecione uma categoria *</option>
    <option value="Medicina">Medicina</option>
    <option value="Enfermagem">Enfermagem</option>
    <option value="Odontologia">Odontologia</option>
    <option value="Laboratório">Laboratório</option>
    <option value="Higiene e Antissepsia">Higiene e Antissepsia</option>
    <option value="Estética">Estética</option>
    <option value="Podologia">Podologia</option>
    <option value="Equipamentos">Equipamentos</option>
    <option value="Consumíveis">Consumíveis</option>
    <option value="Outros">Outros</option>
  </select>
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
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition">
            {{ salvando ? 'Adicionando...' : 'Adicionar' }}
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
  lote: { type: Object, required: true },
})
const emit = defineEmits(['fechar', 'salvo'])

const salvando = ref(false)
const erro     = ref('')

const form = ref({
  sku:            '',
  nome:           '',
  quantidade:     null,
  unidade_medida: 'UN',
  estoque_minimo: 0,
  data_validade:  '',
  fornecedor:     '',
  localizacao:    '',
  prioridade_abc: '',
  categoria:      '',
})

async function salvar() {
  erro.value     = ''
  salvando.value = true
  try {
    await api.post(`/lotes/${props.lote.id_lote}/itens`, form.value)
    emit('salvo')
  } catch (e) {
    const erros = e.response?.data?.errors
    erro.value = erros
      ? Object.values(erros).flat().join('. ')
      : e.response?.data?.message || 'Erro ao adicionar item.'
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
}
.campo:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
}
option {
  background: #1e293b;
}
</style>