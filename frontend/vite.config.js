// ============================================================
// Configuração do Vite para o frontend Vue.js
// ============================================================

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],

  // Atalho de caminho: '@' aponta para a pasta 'src'
  // Assim escrevemos '@/servicos/api' em vez de '../../servicos/api'
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },

  server: {
    port: 3000,
    host: '0.0.0.0',  // Necessário para funcionar dentro do Docker

    // Redireciona chamadas para /api para o backend Laravel
    // Assim evita problema de CORS durante o desenvolvimento
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
})
