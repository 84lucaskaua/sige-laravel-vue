import { ref, computed, onBeforeUnmount } from 'vue'

export function useModalArrastavel() {
  const arrastando = ref(false)
  const posicao = ref({ x: 0, y: 0 })
  let inicioMouse = { x: 0, y: 0 }
  let inicioPosicao = { x: 0, y: 0 }

  function aoMoverGlobal(evento) {
    if (!arrastando.value) return
    evento.preventDefault()
    posicao.value = {
      x: inicioPosicao.x + (evento.pageX - inicioMouse.x),
      y: inicioPosicao.y + (evento.pageY - inicioMouse.y),
    }
  }

  function aoSoltarGlobal() {
    if (!arrastando.value) return
    arrastando.value = false
    window.removeEventListener('mousemove', aoMoverGlobal)
    window.removeEventListener('mouseup', aoSoltarGlobal)
  }

  function aoIniciarArraste(evento) {
    // não inicia o drag se o clique começou em botão/input dentro do cabeçalho (ex: X de fechar)
    if (evento.target.closest('button, a, input, select, textarea')) return

    arrastando.value = true
    inicioMouse    = { x: evento.pageX, y: evento.pageY }
    inicioPosicao  = { ...posicao.value }

    window.addEventListener('mousemove', aoMoverGlobal)
    window.addEventListener('mouseup', aoSoltarGlobal)
  }

  // chama isso no @fechar ou logo antes de reabrir o modal, senão ele reabre deslocado
  function resetarPosicao() {
    posicao.value = { x: 0, y: 0 }
  }

  const estiloArraste = computed(() => ({
    transform: `translate(${posicao.value.x}px, ${posicao.value.y}px)`,
  }))

  onBeforeUnmount(() => {
    window.removeEventListener('mousemove', aoMoverGlobal)
    window.removeEventListener('mouseup', aoSoltarGlobal)
  })

  return { aoIniciarArraste, estiloArraste, resetarPosicao }
}