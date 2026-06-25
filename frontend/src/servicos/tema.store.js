// src/servicos/tema.store.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useTemaStore = defineStore('tema', () => {
  const temaClaro = ref(localStorage.getItem('sige_tema') === 'claro')

  function toggleTema() {
    temaClaro.value = !temaClaro.value
    localStorage.setItem('sige_tema', temaClaro.value ? 'claro' : 'escuro')
  }

  return { temaClaro, toggleTema }
})
