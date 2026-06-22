<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50" @click.self="$emit('fechar')">
    <div class="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">

      <!-- Cabeçalho -->
      <div class="flex justify-between items-start mb-6">
        <div>
          <h2 class="text-white font-bold text-lg">Detalhes do Produto</h2>
          <p class="text-sky-400 text-xs mt-0.5">Visualize informações completas e histórico de movimentações do produto.</p>
        </div>
        <button @click="$emit('fechar')" class="text-slate-400 hover:text-white">
          <X :size="18" />
        </button>
      </div>

      <!-- Grid de infos -->
      <div class="grid grid-cols-2 gap-6 mb-6">
        <div>
          <p class="text-sky-400 text-xs mb-1">Código / SKU</p>
          <p class="text-white font-bold">{{ item.sku || '—' }}</p>
        </div>
        <div>
          <p class="text-sky-400 text-xs mb-1">Nome do Produto</p>
          <p class="text-white font-bold">{{ item.nome || '—' }}</p>
        </div>
        <div>
          <p class="text-sky-400 text-xs mb-1">Lote</p>
          <span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-600 text-white">
            {{ item.lote?.numero_lote || loteNumero || '—' }}
          </span>
        </div>
        <div>
          <p class="text-sky-400 text-xs mb-1">Status</p>
          <span v-if="item.data_validade && estaVencido(item.data_validade)"
            class="px-2 py-0.5 rounded text-xs font-bold bg-red-600 text-white">Vencido</span>
          <span v-else-if="item.data_validade && proximoDoVencimento(item.data_validade)"
            class="px-2 py-0.5 rounded text-xs font-bold bg-yellow-600 text-white">Vencendo</span>
          <span v-else-if="item.quantidade === 0"
            class="px-2 py-0.5 rounded text-xs font-bold bg-red-800 text-white">Sem Estoque</span>
          <span v-else-if="item.quantidade <= item.estoque_minimo"
            class="px-2 py-0.5 rounded text-xs font-bold bg-orange-600 text-white">Crítico</span>
          <span v-else
            class="px-2 py-0.5 rounded text-xs font-bold bg-green-700 text-white">Normal</span>
        </div>
        <div>
          <p class="text-sky-400 text-xs mb-1">Quantidade</p>
          <p class="text-white font-bold">{{ item.quantidade }} {{ item.unidade_medida || 'unidades' }}</p>
        </div>
        <div>
          <p class="text-sky-400 text-xs mb-1">Data de Validade</p>
          <p class="text-white font-bold">{{ item.data_validade ? formatarData(item.data_validade) : '—' }}</p>
        </div>
        <div>
          <p class="text-sky-400 text-xs mb-1">Fornecedor</p>
          <p class="text-white">{{ item.fornecedor || '—' }}</p>
        </div>
        <div>
          <p class="text-sky-400 text-xs mb-1">Localização / Prateleira</p>
          <p class="text-white">{{ item.localizacao || '—' }}</p>
        </div>
      </div>

      <!-- Botões de ação -->
      <div class="flex gap-2 mb-6 flex-wrap">
        <button
          @click="$emit('editar', item)"
          class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium"
        >
          <Pencil :size="15" />
          Editar Produto
        </button>
        <button
          @click="$emit('baixa', item)"
          class="flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition text-sm font-medium"
        >
          <PackageOpen :size="15" />
          Registrar Baixa
        </button>
        <button
          @click="$emit('entrada', item)"
          class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium"
        >
          <PackagePlus :size="15" />
          Registrar Entrada
        </button>
        <button
          @click="$emit('excluir', item)"
          class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium"
        >
          <Trash2 :size="15" />
          Excluir
        </button>
      </div>

      <!-- Histórico -->
      <div>
        <h3 class="text-white font-bold mb-3">Histórico do Item</h3>

        <div v-if="carregandoHistorico" class="text-slate-400 text-sm text-center py-4">
          Carregando histórico...
        </div>

        <div v-else-if="historico.length === 0" class="text-slate-500 text-sm text-center py-4">
          Nenhuma movimentação registrada.
        </div>

        <table v-else class="w-full text-sm">
          <thead>
            <tr class="text-slate-400 border-b border-slate-800">
              <th class="text-left pb-2 font-medium">Data</th>
              <th class="text-left pb-2 font-medium">Tipo</th>
              <th class="text-left pb-2 font-medium">Quantidade</th>
              <th class="text-left pb-2 font-medium">Motivo</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            <tr v-for="mov in historico" :key="mov.id" class="hover:bg-slate-800/40 transition">
              <td class="py-2 text-slate-300">{{ formatarData(mov.created_at) }}</td>
              <td class="py-2">
                <span
                  :class="mov.tipo === 'entrada'
                    ? 'bg-green-700 text-white'
                    : 'bg-red-600 text-white'"
                  class="px-2 py-0.5 rounded text-xs font-bold capitalize"
                >
                  {{ mov.tipo === 'entrada' ? 'Entrada' : 'Saída' }}
                </span>
              </td>
              <td class="py-2"
                :class="mov.tipo === 'entrada' ? 'text-green-400' : 'text-red-400'">
                {{ mov.tipo === 'entrada' ? '+' : '' }}{{ mov.quantidade }}
              </td>
              <td class="py-2 text-slate-400">{{ mov.motivo || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { X, Pencil, PackageOpen, PackagePlus, Trash2 } from 'lucide-vue-next'
import api from '@/servicos/api'
import { formatarData, estaVencido, proximoDoVencimento } from '@/utils/date'

const props = defineProps({
  item:        { type: Object, required: true },
  loteNumero:  { type: String, default: '' },
})

defineEmits(['fechar', 'editar', 'baixa', 'entrada', 'excluir'])

const historico          = ref([])
const carregandoHistorico = ref(false)

onMounted(async () => {
  carregandoHistorico.value = true
  try {
    const res = await api.get(`/itens/${props.item.id_item}/historico`)
    historico.value = res.data
  } catch {
    historico.value = []
  } finally {
    carregandoHistorico.value = false
  }
})
</script>