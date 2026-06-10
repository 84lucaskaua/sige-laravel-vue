<template>
  <!-- Tela de login com tema escuro -->
  <div class="min-h-screen flex items-center justify-center" style="background-color: #0d0d0d;">

    <div class="w-full max-w-md px-8 py-10 rounded-2xl" style="background-color: #1a1a1a; border: 1px solid #2a2a2a;">

      <!-- Logo do Senac -->
      <div class="text-center mb-6">
        <img
          src="../componentes/img/download.jpg"
          alt="Logo Senac"
          class="h-14 mx-auto mb-4"
        />

        <!-- Título -->
        <h1 class="text-3xl font-bold" style="color: #3b82f6;">SIGE</h1>
        <p class="text-sm mt-1" style="color: #9ca3af;">Sistema de Gerenciamento de Estoque</p>
      </div>

      <!-- Formulário de login -->
      <form @submit.prevent="fazerLogin" class="mt-8">

        <!-- Campo de email -->
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1" style="color: #d1d5db;">Email</label>
          <input
            v-model="email"
            type="email"
            placeholder="seu@email.com"
            required
            class="w-full rounded-lg px-4 py-3 text-sm outline-none transition"
            style="background-color: #2a2a2a; border: 1px solid #3a3a3a; color: #f3f4f6;"
          />
        </div>

        <!-- Campo de senha -->
        <div class="mb-6">
          <label class="block text-sm font-medium mb-1" style="color: #d1d5db;">Senha</label>
          <input
            v-model="senha"
            type="password"
            placeholder="••••••••"
            required
            class="w-full rounded-lg px-4 py-3 text-sm outline-none transition"
            style="background-color: #2a2a2a; border: 1px solid #3a3a3a; color: #f3f4f6;"
          />
        </div>

        <!-- Mensagem de erro -->
        <div v-if="mensagemErro" class="mb-4 p-3 rounded-lg text-sm" style="background-color: #3b1c1c; border: 1px solid #7f1d1d; color: #fca5a5;">
          {{ mensagemErro }}
        </div>

        <!-- Botão de entrar -->
        <button
          type="submit"
          :disabled="carregando"
          class="w-full rounded-lg py-3 font-semibold text-white transition"
          style="background-color: #2563eb;"
          onmouseover="this.style.backgroundColor='#1d4ed8'"
          onmouseout="this.style.backgroundColor='#2563eb'"
        >
          {{ carregando ? 'Entrando...' : 'Entrar' }}
        </button>

      </form>

      <!-- Divisor -->
      <div class="my-6" style="border-top: 1px solid #2a2a2a;"></div>

      <!-- Atalhos de login para teste -->
      <div class="space-y-2">
        <button
          @click="preencherLogin('admin@sige.com', 'Admin@2024')"
          class="w-full py-2 px-4 rounded-lg text-sm font-medium text-left transition"
          style="background-color: #1e3a5f; color: #93c5fd; border: 1px solid #2563eb;"
        >
      
        </button>
        <button
          @click="preencherLogin('operador@sige.com', 'Operador@2024')"
          class="w-full py-2 px-4 rounded-lg text-sm font-medium text-left transition"
          style="background-color: #1a3a2a; color: #6ee7b7; border: 1px solid #059669;"
        >
          <span class="font-bold">Operador:</span> operador@sige.com / Operador@2024
        </button>
        <button
          @click="preencherLogin('visualizador@sige.com', 'Visual@2024')"
          class="w-full py-2 px-4 rounded-lg text-sm font-medium text-left transition"
          style="background-color: #2a2a1a; color: #fde68a; border: 1px solid #d97706;"
        >
          <span class="font-bold">Visualizador:</span> visualizador@sige.com / Visual@2024
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'

// ---- Dados do formulário ----
const email        = ref('')
const senha        = ref('')
const carregando   = ref(false)
const mensagemErro = ref('')

const router       = useRouter()
const autenticacao = useAutenticacaoStore()

/**
 * Preenche o formulário com as credenciais de teste
 * Ao clicar nos botões azul/verde/amarelo da tela
 */
function preencherLogin(emailTeste, senhaTeste) {
  email.value = emailTeste
  senha.value = senhaTeste
}

/**
 * Envia o formulário de login para o backend
 */
async function fazerLogin() {
  mensagemErro.value = ''
  carregando.value   = true

  try {
    await autenticacao.fazerLogin(email.value, senha.value)
    router.push('/dashboard')

  } catch (erro) {
    mensagemErro.value = erro.response?.data?.message
      || 'Email ou senha incorretos.'

  } finally {
    carregando.value = false
  }
}
</script>