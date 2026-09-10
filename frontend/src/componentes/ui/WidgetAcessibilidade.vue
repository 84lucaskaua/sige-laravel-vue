<template>
  <div class="fixed bottom-24 right-5 z-[9999]">

    <!-- Painel expandido -->
    <div
      v-if="painelAberto"
      class="mb-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg p-3 flex flex-col gap-2 w-48"
    >
      <div class="flex items-center justify-between px-1">
        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Tamanho da fonte</p>
        <button
          class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition"
          title="Fechar"
          aria-label="Fechar opções de fonte"
          @click="painelAberto = false"
        >
          <X :size="16" />
        </button>
      </div>

      <div class="flex items-center gap-1">
        <button
          class="flex-1 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-bold text-sm"
          title="Diminuir fonte"
          :disabled="nivelAtual === 'normal'"
          :class="{ 'opacity-40 cursor-not-allowed': nivelAtual === 'normal' }"
          @click="diminuirFonte"
        >
          A-
        </button>
        <button
          class="flex-1 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-bold text-base"
          title="Restaurar padrão"
          @click="restaurarPadrao"
        >
          A
        </button>
        <button
          class="flex-1 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-bold text-lg"
          title="Aumentar fonte"
          :disabled="nivelAtual === 'extra'"
          :class="{ 'opacity-40 cursor-not-allowed': nivelAtual === 'extra' }"
          @click="aumentarFonte"
        >
          A+
        </button>
      </div>

      <p class="text-[11px] text-slate-400 dark:text-slate-500 px-1">
        Nível atual: {{ rotuloNivel }}
      </p>
    </div>

    <!-- Botão flutuante principal -->
    <button
      class="w-12 h-12 rounded-full bg-blue-600 text-white shadow-lg hover:bg-blue-700 transition flex items-center justify-center"
      title="Opções de acessibilidade"
      aria-label="Abrir opções de acessibilidade"
      @click="painelAberto = !painelAberto"
    >
      <ALargeSmall :size="22" />
    </button>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ALargeSmall, X } from 'lucide-vue-next'
import { useAcessibilidade } from '@/composables/useAcessibilidade'

const painelAberto = ref(false)
const { nivelAtual, aumentarFonte, diminuirFonte, restaurarPadrao } = useAcessibilidade()

const rotuloNivel = computed(() => {
  const rotulos = { normal: 'Normal', grande: 'Grande', extra: 'Extra Grande' }
  return rotulos[nivelAtual.value] || 'Normal'
})
</script>