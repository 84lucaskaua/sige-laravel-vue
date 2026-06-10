// ============================================================
// Serviço de API
//
// Configura o axios para fazer todas as chamadas ao backend.
// Adiciona automaticamente o token de autenticação em cada
// requisição, e trata erros de forma centralizada.
// ============================================================

import axios from 'axios'

// Cria uma instância do axios já configurada com a URL base
const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// ---- Interceptor de requisição ----
// Antes de cada chamada, adiciona o token de autenticação
api.interceptors.request.use((configuracao) => {
  const token = localStorage.getItem('token')

  if (token) {
    configuracao.headers.Authorization = `Bearer ${token}`
  }

  return configuracao
})

// ---- Interceptor de resposta ----
// Trata erros que vêm do backend de forma centralizada
api.interceptors.response.use(
  // Resposta com sucesso: passa direto
  (resposta) => resposta,

  // Resposta com erro
  (erro) => {
    // Se o backend retornou 401 (não autenticado), faz logout
    if (erro.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('usuario')
      window.location.href = '/login'
    }

    // Repassamos o erro para quem chamou tratar
    return Promise.reject(erro)
  }
)

export default api
