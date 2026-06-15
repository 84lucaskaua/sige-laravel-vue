<template>
  <!-- Tela de login com tema escuro -->
  <div class="min-h-screen flex items-center justify-center bg-slate-950">

    <div class="w-full max-w-md px-8 py-10 rounded-2xl bg-slate-900 border border-slate-800">

      <!-- Logo do Senac -->
      <div class="text-center mb-6">
        <img
          :src="logo"
          alt="Logo Senac"
          class="h-14 mx-auto mb-4"
        />

        <!-- Título -->
        <h1 class="text-3xl font-bold text-sky-400">SIGE</h1>
        <p class="text-sm mt-1 text-slate-400">Sistema de Gerenciamento de Estoque</p>
      </div>

      <!-- Formulário de login -->
      <form @submit.prevent="fazerLogin" class="mt-8">

        <!-- Campo de email -->
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1 text-slate-300">Email</label>
          <input
            v-model="email"
            type="email"
            placeholder="seu@email.com"
            required
            class="w-full rounded-lg px-4 py-3 text-sm outline-none bg-slate-800 border border-slate-700 text-slate-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
          />
        </div>

        <!-- Campo de senha -->
        <div class="mb-6">
          <label class="block text-sm font-medium mb-1 text-slate-300">Senha</label>
          <input
            v-model="senha"
            type="password"
            placeholder="••••••••"
            required
            class="w-full rounded-lg px-4 py-3 text-sm outline-none bg-slate-800 border border-slate-700 text-slate-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
          />
        </div>

        <!-- Mensagem de erro -->
        <div v-if="mensagemErro" class="mb-4 p-3 rounded-lg text-sm bg-red-900 border border-red-700 text-red-200">
          {{ mensagemErro }}
        </div>

        <!-- Botão de entrar -->
        <button
          type="submit"
          :disabled="carregando"
          class="w-full rounded-lg py-3 font-semibold text-white bg-blue-600 hover:bg-blue-700 transition disabled:opacity-50"
        >
          {{ carregando ? 'Entrando...' : 'Entrar' }}
        </button>

      </form>

      <!-- Divisor -->
      <div class="my-6 border-t border-slate-800"></div>

      <!-- Atalhos de login para teste -->
      <div class="space-y-2">
        <button
          type="button"
          @click="preencherLogin('admin@sige.com', 'Admin@2024')"
          class="w-full py-2 px-4 rounded-lg text-sm font-medium text-left bg-slate-800 text-sky-200 border border-sky-600 hover:bg-slate-700 transition"
        >
          <span class="font-bold">Admin:</span> admin@sige.com / Admin@2024
        </button>
        <button
          type="button"
          @click="preencherLogin('operador@sige.com', 'Operador@2024')"
          class="w-full py-2 px-4 rounded-lg text-sm font-medium text-left bg-slate-800 text-emerald-300 border border-emerald-600 hover:bg-slate-700 transition"
        >
          <span class="font-bold">Operador:</span> operador@sige.com / Operador@2024
        </button>
        <button
          type="button"
          @click="preencherLogin('visualizador@sige.com', 'Visual@2024')"
          class="w-full py-2 px-4 rounded-lg text-sm font-medium text-left bg-slate-800 text-amber-200 border border-amber-600 hover:bg-slate-700 transition"
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
import logoSenac from '@/componentes/img/download.jpg'

// ---- Dados do formulário ----
const email        = ref('')
const logo         = logoSenac
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