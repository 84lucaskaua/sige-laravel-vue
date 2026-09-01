import { ref } from 'vue'

export function useArrastarParaRolar() {
  const elementoRef = ref(null)
  const arrastando = ref(false)
  let posX = 0
  let scrollInicial = 0

  function aoIniciar(evento) {
    if (!elementoRef.value) return
    arrastando.value = true
    posX = evento.pageX - elementoRef.value.offsetLeft
    scrollInicial = elementoRef.value.scrollLeft
    elementoRef.value.style.cursor = 'grabbing'
  }

  function aoMover(evento) {
    if (!arrastando.value || !elementoRef.value) return
    evento.preventDefault()
    const x = evento.pageX - elementoRef.value.offsetLeft
    const distancia = (x - posX) * 1.2 // multiplicador de velocidade
    elementoRef.value.scrollLeft = scrollInicial - distancia
  }

  function aoSoltar() {
    arrastando.value = false
    if (elementoRef.value) elementoRef.value.style.cursor = 'grab'
  }

  return { elementoRef, aoIniciar, aoMover, aoSoltar }
}