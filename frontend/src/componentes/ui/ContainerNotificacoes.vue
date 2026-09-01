<template>
  <!-- Toasts -->
  <div class="fixed top-5 right-5 z-[10000] flex flex-col gap-2 w-80 max-w-[90vw]">
    <transition-group name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="rounded-lg shadow-lg border px-4 py-3 flex items-start gap-3"
        :class="classesPorTipo(toast.tipo)"
      >
        <component :is="iconePorTipo(toast.tipo)" :size="18" class="mt-0.5 shrink-0" />
        <p class="text-sm font-medium flex-1">{{ toast.mensagem }}</p>
        <button class="opacity-70 hover:opacity-100 transition" @click="removerToast(toast.id)">
          <X :size="16" />
        </button>
      </div>
    </transition-group>
  </div>

  <!-- Modal de confirmação -->
  <div v-if="confirmacaoAberta" class="fixed inset-0 bg-black/70 flex items-center justify-center z-[10001] p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-sm p-6">
      <h2 class="text-slate-900 dark:text-white font-bold text-lg mb-2">{{ confirmacaoTitulo }}</h2>
      <p class="text-slate-600 dark:text-slate-300 text-sm mb-6">{{ confirmacaoMensagem }}</p>

      <div class="flex gap-3">
        <button
          class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
          @click="responderConfirmacao(false)"
        >
          Cancelar
        </button>
        <button
          class="flex-1 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition font-medium"
          @click="responderConfirmacao(true)"
        >
          Confirmar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { CheckCircle2, XCircle, AlertTriangle, Info, X } from 'lucide-vue-next'
import { useNotificacao } from '@/composables/useNotificacao'

const {
  toasts, removerToast,
  confirmacaoAberta, confirmacaoTitulo, confirmacaoMensagem, responderConfirmacao,
} = useNotificacao()

function classesPorTipo(tipo) {
  const mapa = {
    sucesso: 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-700 text-green-700 dark:text-green-300',
    erro:    'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-700 text-red-700 dark:text-red-300',
    aviso:   'bg-amber-50 dark:bg-amber-900/30 border-amber-200 dark:border-amber-700 text-amber-700 dark:text-amber-300',
    info:    'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-300',
  }
  return mapa[tipo] || mapa.info
}

function iconePorTipo(tipo) {
  const mapa = {
    sucesso: CheckCircle2,
    erro:    XCircle,
    aviso:   AlertTriangle,
    info:    Info,
  }
  return mapa[tipo] || Info
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(20px);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
</style>