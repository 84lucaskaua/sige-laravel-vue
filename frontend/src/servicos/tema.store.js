import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useTemaStore = defineStore('tema', () => {
  const temaClaro = ref(localStorage.getItem('sige_tema') === 'claro')

  function aplicarClasse() {
    const root = document.documentElement
    root.classList.remove('light', 'dark')
    root.classList.add(temaClaro.value ? 'light' : 'dark')
  }

  aplicarClasse()

  watch(temaClaro, () => {
    localStorage.setItem('sige_tema', temaClaro.value ? 'claro' : 'escuro')
    aplicarClasse()
  })

  function toggleTema() {
    temaClaro.value = !temaClaro.value
  }

  return { temaClaro, toggleTema }
})