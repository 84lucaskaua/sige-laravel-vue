<template>
  <div
    v-if="produto"
    class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4"
    @click.self="$emit('fechar')"
  >
    <div class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white rounded-xl w-full max-w-lg max-h-[85vh] overflow-y-auto p-6">

      <!-- Cabeçalho -->
      <div class="flex justify-between items-start mb-1">
        <h2 class="text-xl font-bold">Detalhes do Produto</h2>
        <button
          class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl leading-none"
          @click="$emit('fechar')"
        >
          ×
        </button>
      </div>
      <p class="text-blue-500 dark:text-blue-400 text-sm mb-6">
        Visualize as validades e lotes cadastrados para este produto.
      </p>

      <!-- Código / Nome -->
      <div class="grid grid-cols-2 gap-4 mb-5">
        <div>
          <p class="text-blue-500 dark:text-blue-400 text-xs mb-1">Código / SKU</p>
          <p class="font-bold">{{ produto.sku ?? '—' }}</p>
        </div>
        <div>
          <p class="text-blue-500 dark:text-blue-400 text-xs mb-1">Nome do Produto</p>
          <p class="font-bold">{{ produto.nome }}</p>
        </div>
      </div>

      <!-- Quantidade total / Categoria -->
      <div class="grid grid-cols-2 gap-4 mb-6">
        <div>
          <p class="text-blue-500 dark:text-blue-400 text-xs mb-1">Quantidade Total</p>
          <p class="font-bold">{{ formatNumero(produto.quantidade) }} {{ produto.unidade_medida }}</p>
        </div>
        <div>
          <p class="text-blue-500 dark:text-blue-400 text-xs mb-1">Categoria</p>
          <p class="font-bold">{{ produto.categoria_nome ?? '—' }}</p>
        </div>
      </div>

      <!-- Lista de lotes/validades -->
      <p class="font-bold mb-3">Lotes e Validades</p>
      <div class="space-y-3 mb-2">
        <div
          v-for="v in produto.validades"
          :key="v.id_item"
          class="bg-slate-100 dark:bg-slate-800 rounded-lg p-4 grid grid-cols-2 gap-3"
        >
          <div>
            <p class="text-blue-500 dark:text-blue-400 text-xs mb-1">Lote</p>
            <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded font-medium">{{ v.numero_lote ?? '—' }}</span>
          </div>
          <div>
            <p class="text-blue-500 dark:text-blue-400 text-xs mb-1">Status</p>
            <span :class="badgeValidade(v)">{{ labelValidade(v) }}</span>
          </div>
          <div>
            <p class="text-blue-500 dark:text-blue-400 text-xs mb-1">Quantidade</p>
            <p class="font-bold">{{ formatNumero(v.quantidade) }} {{ v.unidade }}</p>
          </div>
          <div>
            <p class="text-blue-500 dark:text-blue-400 text-xs mb-1">Data de Validade</p>
            <p class="font-bold">{{ formatarData(v.data_validade) }}</p>
          </div>
          <div class="col-span-2">
            <p class="text-blue-500 dark:text-blue-400 text-xs mb-1">Localização / Prateleira</p>
            <p class="font-bold">{{ v.localizacao ?? '—' }}</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
defineProps({
  produto: { type: Object, default: null },
  formatNumero: { type: Function, required: true },
  formatarData: { type: Function, required: true },
  badgeValidade: { type: Function, required: true },
  labelValidade: { type: Function, required: true }
})

defineEmits(['fechar'])
</script>