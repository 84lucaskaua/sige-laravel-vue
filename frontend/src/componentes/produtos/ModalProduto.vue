<template>
  <!-- Fundo escuro do modal (clica fora para fechar) -->
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('fechar')">

    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-lg">

      <!-- Título do modal -->
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-bold text-gray-800">
          {{ ehEdicao ? 'Editar Produto' : 'Novo Produto' }}
        </h2>
        <button @click="$emit('fechar')" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
      </div>

      <!-- Formulário -->
      <form @submit.prevent="salvar">

        <!-- Código e Nome -->
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
            <input v-model="formulario.codigo" type="text" required class="campo-texto" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Unidade *</label>
            <select v-model="formulario.unidade" required class="campo-texto">
              <option v-for="unidade in unidades" :key="unidade" :value="unidade">{{ unidade }}</option>
            </select>
          </div>
        </div>

        <!-- Nome completo -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
          <input v-model="formulario.nome" type="text" required class="campo-texto" />
        </div>

        <!-- Descrição -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
          <textarea v-model="formulario.descricao" rows="2" class="campo-texto"></textarea>
        </div>

        <!-- Categoria e Estoque Mínimo -->
        <div class="grid grid-cols-2 gap-4 mb-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
            <select v-model="formulario.categoria_id" class="campo-texto">
              <option value="">Sem categoria</option>
              <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estoque Mínimo *</label>
            <input v-model="formulario.estoque_minimo" type="number" min="0" required class="campo-texto" />
          </div>
        </div>

        <!-- Mensagem de erro -->
        <div v-if="erro" class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-600 text-sm">
          {{ erro }}
        </div>

        <!-- Botões -->
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
import { ref, computed, onMounted } from 'vue'
import api from '@/servicos/api'

// Props: recebe o produto quando é edição (null quando é criação)
const props = defineProps({
  produto: { type: Object, default: null },
})

// Eventos que este componente pode emitir para o pai
const emit = defineEmits(['fechar', 'salvo'])

// ---- Dados do formulário ----
const formulario = ref({
  codigo:          props.produto?.codigo          || '',
  nome:            props.produto?.nome            || '',
  descricao:       props.produto?.descricao       || '',
  categoria_id:    props.produto?.categoria_id    || '',
  unidade:         props.produto?.unidade         || 'UN',
  estoque_minimo:  props.produto?.estoque_minimo  || 0,
})

const categorias = ref([])
const salvando   = ref(false)
const erro       = ref('')

// Detecta se é edição ou criação
const ehEdicao = computed(() => !!props.produto)

// Lista de unidades disponíveis
const unidades = ['UN', 'CX', 'PCT', 'KG', 'G', 'L', 'ML', 'FR', 'RL', 'KIT', 'EMB']

/**
 * Carrega as categorias para o select
 */
async function carregarCategorias() {
  try {
    const resposta = await api.get('/categorias')
    categorias.value = resposta.data
  } catch {
    // Sem categorias não é crítico, só não vai preencher o select
  }
}

/**
 * Salva o produto (cria ou edita dependendo do caso)
 */
async function salvar() {
  erro.value   = ''
  salvando.value = true

  try {
    if (ehEdicao.value) {
      // Edição: usa PUT com o ID do produto
      await api.put(`/produtos/${props.produto.id}`, formulario.value)
    } else {
      // Criação: usa POST
      await api.post('/produtos', formulario.value)
    }

    // Avisa o componente pai que salvou com sucesso
    emit('salvo')

  } catch (erroHttp) {
    // Pega os erros de validação do Laravel
    const errosValidacao = erroHttp.response?.data?.errors
    if (errosValidacao) {
      // Junta todos os erros em uma mensagem só
      erro.value = Object.values(errosValidacao).flat().join('. ')
    } else {
      erro.value = erroHttp.response?.data?.message || 'Erro ao salvar produto.'
    }
  } finally {
    salvando.value = false
  }
}

onMounted(carregarCategorias)
</script>

<style scoped>
/* Estilo reutilizável para campos de formulário */
.campo-texto {
  width: 100%;
  border: 1px solid #D1D5DB;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
}
.campo-texto:focus {
  ring: 2px;
  border-color: #3B82F6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
}
</style>
