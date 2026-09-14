<template>
  <div class="fixed bottom-5 right-5 z-[9999] flex flex-col items-end gap-3">

    <!-- Mini-botões (aparecem quando o menu está aberto) -->
    <TransitionGroup name="fab-item" tag="div" class="flex flex-col items-end gap-3">
      <button
        v-if="menuAberto"
        key="chat"
        class="w-12 h-12 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg flex items-center justify-center transition-colors"
        title="Assistente virtual"
        aria-label="Abrir assistente virtual"
        @click="abrirChat"
      >
        <MessageCircle :size="20" />
      </button>

      <button
        v-if="menuAberto"
        key="acessibilidade"
        class="w-12 h-12 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg flex items-center justify-center transition-colors"
        title="Acessibilidade"
        aria-label="Abrir opções de acessibilidade"
        @click="abrirAcessibilidade"
      >
        <ALargeSmall :size="20" />
      </button>
    </TransitionGroup>

    <!-- Botão principal -->
    <button
  class="w-14 h-14 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg flex items-center justify-center transition-transform active:scale-90"
  :aria-expanded="menuAberto || chatAberto || acessibilidadeAberto"
  aria-label="Abrir menu de suporte"
  @click="alternarMenu"
>
  <span class="icone-fab" :class="{ 'icone-fab-girado': menuAberto || chatAberto || acessibilidadeAberto }">
    <X v-if="menuAberto || chatAberto || acessibilidadeAberto" :size="22" />
    <Plus v-else :size="22" />
  </span>
</button>

  </div>

  <ChatbotWidget v-model:aberto="chatAberto" />
  <WidgetAcessibilidade v-model:aberto="acessibilidadeAberto" />
</template>

<script setup>
import { ref } from 'vue'
import { MessageCircle, ALargeSmall, X, Plus } from 'lucide-vue-next'
import ChatbotWidget from '@/componentes/ui/ChatbotWidget.vue'
import WidgetAcessibilidade from '@/componentes/ui/WidgetAcessibilidade.vue'

const menuAberto = ref(false)
const chatAberto = ref(false)
const acessibilidadeAberto = ref(false)

function alternarMenu() {
  if (chatAberto.value || acessibilidadeAberto.value) {
    chatAberto.value = false
    acessibilidadeAberto.value = false
    menuAberto.value = false
    return
  }
  menuAberto.value = !menuAberto.value
}

function abrirChat() {
  chatAberto.value = true
  acessibilidadeAberto.value = false
  menuAberto.value = false
}

function abrirAcessibilidade() {
  acessibilidadeAberto.value = true
  chatAberto.value = false
  menuAberto.value = false
}
</script>

<style scoped>
/* Ícone do botão principal gira suavemente ao abrir/fechar (+  -> X) */
.icone-fab {
  display: inline-flex;
  transition: transform 0.25s ease;
}
.icone-fab-girado {
  transform: rotate(90deg);
}

/* Entrada dos mini-botões: sobem com um leve "bounce" e escala,
   como se saíssem de dentro do botão principal */
.fab-item-enter-active {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.fab-item-leave-active {
  transition: all 0.15s ease-in;
}
.fab-item-enter-from {
  opacity: 0;
  transform: translateY(16px) scale(0.5);
}
.fab-item-leave-to {
  opacity: 0;
  transform: translateY(8px) scale(0.7);
}
</style>