<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50" @click.self="$emit('fechar')">
    <div class="bg-slate-900 border border-slate-700 rounded-xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">

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

        <div class="mb-6">
          <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold text-slate-300">Itens do Lote</h3>
            <button type="button" @click="adicionarItem" class="text-blue-400 text-sm hover:text-blue-300">
              + Adicionar item
            </button>
          </div>

          <div v-for="(item, indice) in formulario.itens" :key="indice" class="border border-slate-700 rounded-lg p-4 mb-3 bg-slate-800">
            <div class="flex justify-between items-center mb-3">
              <span class="text-sm font-medium text-slate-400">Item {{ indice + 1 }}</span>
              <button type="button" @click="removerItem(indice)" class="text-red-400 hover:text-red-300 text-sm">
                Remover
              </button>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div class="col-span-2">
                <label class="block text-xs text-slate-400 mb-1">Produto *</label>
                <select v-model="item.produto_id" required class="campo text-sm">
                  <option value="">Selecione...</option>
                  <option v-for="produto in produtos" :key="produto.id_produto" :value="produto.id_produto">
                    {{ produto.nome }} ({{ produto.unidade_medida }})
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-xs text-slate-400 mb-1">Quantidade *</label>
                <input v-model="item.quantidade" type="number" min="1" required class="campo text-sm" />
              </div>

              <div>
                <label class="block text-xs text-slate-400 mb-1">Estoque Mínimo</label>
                <input v-model="item.estoque_minimo" type="number" min="0" class="campo text-sm" />
              </div>

              <div>
                <label class="block text-xs text-slate-400 mb-1">Validade</label>
                <input v-model="item.data_validade" type="date" class="campo text-sm" />
              </div>

              <div>
                <label class="block text-xs text-slate-400 mb-1">Fornecedor</label>
                <input v-model="item.fornecedor" type="text" class="campo text-sm" />
              </div>

              <div class="col-span-2">
                <label class="block text-xs text-slate-400 mb-1">Localização</label>
                <input v-model="item.localizacao" type="text" class="campo text-sm" placeholder="Ex: Prateleira A3" />
              </div>

              <div class="col-span-2">
                <label class="block text-xs text-slate-400 mb-1">Prioridade ABC</label>
                <select v-model="item.prioridade_abc" class="campo text-sm">
                  <option value="">Sem prioridade</option>
                  <option value="A">A — Alta</option>
                  <option value="B">B — Média</option>
                  <option value="C">C — Baixa</option>
                </select>
              </div>
            </div>
          </div>

          <p v-if="formulario.itens.length === 0" class="text-sm text-slate-500 italic text-center py-4">
            Nenhum item adicionado. Clique em "+ Adicionar item".
          </p>
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
import { ref, computed, onMounted } from 'vue'
import { X } from 'lucide-vue-next'
import api from '@/servicos/api'

const props = defineProps({
  lote: { type: Object, default: null },
})
const emit = defineEmits(['fechar', 'salvo'])

const ehEdicao = computed(() => !!props.lote)
const produtos = ref([])
const salvando = ref(false)
const erro     = ref('')

const formulario = ref({
  numero:       props.lote?.numero_lote  || '',
  data_entrada: props.lote?.data_entrada || new Date().toISOString().split('T')[0],
  descricao:    props.lote?.descricao    || '',
  itens:        [],
})

function adicionarItem() {
  formulario.value.itens.push({
    produto_id:     '',
    quantidade:     1,
    estoque_minimo: 0,
    data_validade:  '',
    localizacao:    '',
    fornecedor:     '',
    prioridade_abc: '',
  })
}

function removerItem(indice) {
  formulario.value.itens.splice(indice, 1)
}

async function carregarProdutos() {
  try {
    const resposta = await api.get('/produtos')
    produtos.value = resposta.data
  } catch {
    // não crítico
  }
}

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

onMounted(carregarProdutos)
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