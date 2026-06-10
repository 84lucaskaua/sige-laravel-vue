// ============================================================
// Store de Autenticação
//
// Guarda o estado de login do usuário usando o Pinia.
// O Pinia é o gerenciador de estado do Vue 3.
// "State" = dados; "Actions" = funções que mudam os dados.
// ============================================================

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/servicos/api'

export const useAutenticacaoStore = defineStore('autenticacao', () => {

  // ---- State (dados do store) ----

  // Dados do usuário logado (null se não estiver logado)
  const usuario = ref(JSON.parse(localStorage.getItem('usuario')) || null)

  // Token de acesso (null se não estiver logado)
  const token = ref(localStorage.getItem('token') || null)

  // ---- Computed (valores calculados) ----

  // true se o usuário está logado
  const estaLogado = computed(() => !!token.value)

  // Perfil do usuário (admin, operador, visualizador)
  const perfil = computed(() => usuario.value?.perfil || null)

  // Helpers de permissão
  const ehAdmin = computed(() => perfil.value === 'admin')
  const podeMovimentar = computed(() => ['admin', 'operador'].includes(perfil.value))
  const podeCadastrar = computed(() => ['admin', 'operador'].includes(perfil.value))

  // ---- Actions (funções) ----

  /**
   * Faz o login do usuário
   * Salva o token e os dados no localStorage para persistir após recarregar a página
   */
  async function fazerLogin(email, senha) {
    const resposta = await api.post('/login', { email, senha })

    // Salva o token e os dados do usuário
    token.value   = resposta.data.token
    usuario.value = resposta.data.usuario

    // Persiste no localStorage para sobreviver ao refresh da página
    localStorage.setItem('token', token.value)
    localStorage.setItem('usuario', JSON.stringify(usuario.value))

    return resposta.data
  }

  /**
   * Faz o logout do usuário
   * Limpa os dados locais e avisa o backend para invalidar o token
   */
  async function fazerLogout() {
    try {
      // Avisa o backend para invalidar o token
      await api.post('/logout')
    } catch {
      // Mesmo que o backend dê erro, fazemos logout local
    } finally {
      // Limpa os dados do usuário
      token.value   = null
      usuario.value = null

      localStorage.removeItem('token')
      localStorage.removeItem('usuario')
    }
  }

  return {
    // State
    usuario,
    token,
    // Computed
    estaLogado,
    perfil,
    ehAdmin,
    podeMovimentar,
    podeCadastrar,
    // Actions
    fazerLogin,
    fazerLogout,
  }
})
