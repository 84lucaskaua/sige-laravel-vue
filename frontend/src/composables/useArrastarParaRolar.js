import { ref, onBeforeUnmount } from 'vue'

export function useArrastarParaRolar() {
  const elementoRef = ref(null)
  const arrastando = ref(false)
  let posX = 0
  let posY = 0
  let scrollXInicial = 0
  let scrollYInicial = 0

  function aoMoverGlobal(evento) {
    if (!arrastando.value || !elementoRef.value) return
    evento.preventDefault()
    const distanciaX = (evento.pageX - posX) * 1.2
    const distanciaY = (evento.pageY - posY) * 1.2
    elementoRef.value.scrollLeft = scrollXInicial - distanciaX
    elementoRef.value.scrollTop  = scrollYInicial - distanciaY
  }

  function aoSoltarGlobal() {
    if (!arrastando.value) return
    arrastando.value = false
    if (elementoRef.value) elementoRef.value.style.cursor = 'grab'
    window.removeEventListener('mousemove', aoMoverGlobal)
    window.removeEventListener('mouseup', aoSoltarGlobal)
  }

  function aoIniciar(evento) {
    if (!elementoRef.value) return
    if (evento.target.closest('button, a, input, select, textarea, .drag-handle')) return

    arrastando.value = true
    posX = evento.pageX
    posY = evento.pageY
    scrollXInicial = elementoRef.value.scrollLeft
    scrollYInicial = elementoRef.value.scrollTop
    elementoRef.value.style.cursor = 'grabbing'

    window.addEventListener('mousemove', aoMoverGlobal)
    window.addEventListener('mouseup', aoSoltarGlobal)
  }

  onBeforeUnmount(() => {
    window.removeEventListener('mousemove', aoMoverGlobal)
    window.removeEventListener('mouseup', aoSoltarGlobal)
  })

  return { elementoRef, aoIniciar }
}