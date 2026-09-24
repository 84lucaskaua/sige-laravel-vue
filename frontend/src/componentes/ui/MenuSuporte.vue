<template>
  <div class="fixed bottom-5 right-5 z-[9999]" :style="estilo">

    <!-- Mini-botões (aparecem quando o menu está aberto).
         Ficam acima da bolinha; se ela estiver perto do topo da tela, abrem para baixo. -->
    <TransitionGroup
      name="fab-item"
      tag="div"
      :class="[
        'absolute right-0 flex flex-col items-end gap-3',
        abrirParaBaixo ? 'top-full mt-3' : 'bottom-full mb-3',
      ]"
      :style="{ '--desl': abrirParaBaixo ? '-16px' : '16px' }"
    >
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

    <!-- Botão principal (arraste para mover, clique para abrir) -->
    <button
      ref="botaoRef"
      class="w-14 h-14 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg flex items-center justify-center transition-transform active:scale-90 touch-none select-none cursor-grab"
      :class="{ '!cursor-grabbing': arrastando }"
      :aria-expanded="menuAberto || chatAberto || acessibilidadeAberto"
      aria-label="Abrir menu de suporte"
      @pointerdown="aoPressionar"
      @pointermove="aoMover"
      @pointerup="aoSoltar"
      @pointercancel="aoSoltar"
      @click.capture="aoClicarCapturando"
      @click="alternarMenu"
    >
      <span class="icone-fab" :class="{ 'icone-fab-girado': menuAberto || chatAberto || acessibilidadeAberto }">
        <X v-if="menuAberto || chatAberto || acessibilidadeAberto" :size="22" />
        <Plus v-else :size="22" />
      </span>
    </button>

  </div>

  <ChatbotWidget v-model:aberto="chatAberto" :estilo-posicao="ancora(384, 512)" />
  <WidgetAcessibilidade v-model:aberto="acessibilidadeAberto" :estilo-posicao="ancora(192, 140)" />
</template>

<script setup>
import { ref } from 'vue'
import { MessageCircle, ALargeSmall, X, Plus } from 'lucide-vue-next'
import ChatbotWidget from '@/componentes/ui/ChatbotWidget.vue'
import WidgetAcessibilidade from '@/componentes/ui/WidgetAcessibilidade.vue'
// Ajuste o caminho para a pasta onde você guarda seus outros composables
import { useBotaoFlutuanteArrastavel } from '@/composables/useBotaoFlutuanteArrastavel'

const { estilo, posicao, arrastando, aoPressionar, aoMover, aoSoltar, aoClicarCapturando } =
  useBotaoFlutuanteArrastavel({ chave: 'sige:posicao-botao-suporte' })

const botaoRef = ref(null)
const menuAberto = ref(false)
const chatAberto = ref(false)
const acessibilidadeAberto = ref(false)
const abrirParaBaixo = ref(false)

// Os painéis (chat e acessibilidade) abrem colados na bolinha, onde quer que ela esteja:
// acima dela quando há espaço, senão abaixo, sempre dentro da tela.
const TAMANHO_BOLINHA = 56
const FOLGA = 20
const MARGEM = 8

function ancora(largura, altura) {
  const direitaBola = posicao.value?.direita ?? 20
  const baixoBola = posicao.value?.baixo ?? 20
  const larguraTela = document.documentElement.clientWidth
  const alturaTela = document.documentElement.clientHeight
  const right = Math.max(MARGEM, Math.min(direitaBola, larguraTela - largura - MARGEM))
  const topoBola = alturaTela - baixoBola - TAMANHO_BOLINHA

  if (topoBola - FOLGA - altura >= MARGEM) {
    return { right: `${right}px`, bottom: `${baixoBola + TAMANHO_BOLINHA + FOLGA}px` }
  }
  const topoAbaixo = alturaTela - baixoBola + FOLGA
  if (topoAbaixo + altura <= alturaTela - MARGEM) {
    return { right: `${right}px`, top: `${topoAbaixo}px` }
  }
  return { right: `${right}px`, top: `${MARGEM}px` }
}

function alternarMenu() {
  if (chatAberto.value || acessibilidadeAberto.value) {
    chatAberto.value = false
    acessibilidadeAberto.value = false
    menuAberto.value = false
    return
  }
  if (!menuAberto.value && botaoRef.value) {
    // Sem espaço em cima (~150px)? Abre os mini-botões para baixo.
    abrirParaBaixo.value = botaoRef.value.getBoundingClientRect().top < 150
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

/* Entrada dos mini-botões: deslizam com um leve "bounce" e escala,
   como se saíssem de dentro do botão principal (--desl inverte se abrir para baixo) */
.fab-item-enter-active {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.fab-item-leave-active {
  transition: all 0.15s ease-in;
}
.fab-item-enter-from {
  opacity: 0;
  transform: translateY(var(--desl, 16px)) scale(0.5);
}
.fab-item-leave-to {
  opacity: 0;
  transform: translateY(calc(var(--desl, 16px) / 2)) scale(0.7);
}
</style>