<template>
  <!-- Container principal: sidebar + conteúdo -->
  <div class="flex h-screen bg-gray-100">

    <!-- ===== SIDEBAR (menu lateral) ===== -->
    <aside class="w-64 bg-white shadow-md flex flex-col">

      <!-- Logo no topo da sidebar -->
      <div class="p-6 border-b">
        <h1 class="text-xl font-bold text-blue-600">SIGE</h1>
        <p class="text-xs text-gray-500">Gestão de Estoque</p>
      </div>

      <!-- Menu de navegação -->
      <nav class="flex-1 p-4">
        <ul class="space-y-1">

          <!-- Cada item do menu -->
          <li v-for="item in itensDoMenu" :key="item.rota">
            <RouterLink
              :to="item.rota"
              class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition"
              active-class="bg-blue-50 text-blue-600 font-medium"
            >
              <span class="text-lg">{{ item.icone }}</span>
              <span>{{ item.nome }}</span>
            </RouterLink>
          </li>

        </ul>
      </nav>

      <!-- Área do usuário no rodapé da sidebar -->
      <div class="p-4 border-t">
        <div class="flex items-center gap-3 mb-3">
          <!-- Avatar com as iniciais do nome -->
          <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-medium">
            {{ iniciaisDoUsuario }}
          </div>
          <div>
            <p class="text-sm font-medium text-gray-800">{{ usuario?.nome }}</p>
            <p class="text-xs text-gray-500 capitalize">{{ usuario?.perfil }}</p>
          </div>
        </div>

        <!-- Botão de sair -->
        <button
          @click="sair"
          class="w-full text-left text-sm text-red-500 hover:text-red-700 px-2 py-1 rounded hover:bg-red-50 transition"
        >
          🚪 Sair
        </button>
      </div>

    </aside>

    <!-- ===== CONTEÚDO DA PÁGINA ===== -->
    <main class="flex-1 overflow-auto">
      <!-- RouterView renderiza a página atual -->
      <RouterView />
    </main>

  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'

const router = useRouter()
const autenticacao = useAutenticacaoStore()

// Dados do usuário logado
const usuario = computed(() => autenticacao.usuario)

// Pega as duas primeiras letras do nome para usar como avatar
const iniciaisDoUsuario = computed(() => {
  if (!usuario.value?.nome) return '?'
  return usuario.value.nome
    .split(' ')
    .slice(0, 2)
    .map(parte => parte[0])
    .join('')
    .toUpperCase()
})

// ---- Itens do menu lateral ----
// O menu muda conforme o perfil do usuário
const itensDoMenu = computed(() => {
  const menus = [
    { nome: 'Dashboard',    rota: '/dashboard',  icone: '📊' },
    { nome: 'Produtos',     rota: '/produtos',   icone: '📦' },
    { nome: 'Lotes',        rota: '/lotes',      icone: '🗂️' },
    { nome: 'Movimentos',   rota: '/movimentos', icone: '🔄' },
    { nome: 'Relatórios',   rota: '/relatorios', icone: '📋' },
  ]

  // O menu de usuários só aparece para admins
  if (autenticacao.ehAdmin) {
    menus.push({ nome: 'Usuários', rota: '/usuarios', icone: '👥' })
  }

  return menus
})

/**
 * Faz o logout e redireciona para o login
 */
async function sair() {
  await autenticacao.fazerLogout()
  router.push('/login')
}
</script>
