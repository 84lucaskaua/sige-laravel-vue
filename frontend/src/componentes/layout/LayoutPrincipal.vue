<template>
  <div class="flex h-screen bg-black">

    <!-- ===== SIDEBAR ===== -->
    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col">

      <!-- Logo -->
      <div class="p-5 border-b border-slate-800 flex items-center gap-3">
        <img :src="logo" alt="Senac" class="h-8" />
        <div>
          <h1 class="text-base font-bold text-white">SIGE</h1>
          <p class="text-xs text-slate-400">Sistema de Estoque</p>
        </div>
      </div>

      <!-- Usuário -->
      <div class="px-4 py-4 border-b border-slate-800">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-9 h-9 rounded-full bg-blue-600 overflow-hidden flex items-center justify-center text-sm font-bold text-white">
            <img v-if="usuario?.foto_url" :src="usuario.foto_url" class="w-full h-full object-cover" />
            <span v-else>{{ iniciaisDoUsuario }}</span>
          </div>
          <div>
            <p class="text-sm font-medium text-white">{{ usuario?.name }}</p>
            <p class="text-xs text-slate-400 capitalize">{{ usuario?.perfil }}</p>
          </div>
        </div>
        <RouterLink
          to="/perfil"
          class="flex items-center gap-2 text-xs text-slate-400 hover:text-white px-2 py-1.5 rounded-lg hover:bg-slate-800 transition"
        >
          <Settings :size="14" />
          Editar Perfil
        </RouterLink>
      </div>

      <!-- Menu -->
      <nav class="flex-1 p-3 overflow-y-auto">
        <ul class="space-y-0.5">
          <li v-for="item in itensDoMenu" :key="item.rota">
            <RouterLink
              :to="item.rota"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition text-sm"
              active-class="bg-blue-600 text-white font-medium hover:bg-blue-600"
            >
              <component :is="item.icone" :size="18" />
              <span>{{ item.nome }}</span>
            </RouterLink>
          </li>
        </ul>
      </nav>

      <!-- Sair -->
      <div class="p-3 border-t border-slate-800">
        <button
          @click="sair"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-red-400 hover:bg-red-900/20 hover:text-red-300 transition text-sm"
        >
          <LogOut :size="18" />
          Sair
        </button>
      </div>

    </aside>

    <!-- ===== CONTEÚDO ===== -->
    <main class="flex-1 overflow-auto">
      <RouterView />
    </main>

  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import {
  LayoutDashboard, PackagePlus, Package, Trash2,
  History, FileText, BarChart3, Download, Shield,
  Users, Settings, LogOut
} from 'lucide-vue-next'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import logoSenac from '@/componentes/img/Senac_logo.svg.png'

const router       = useRouter()
const autenticacao = useAutenticacaoStore()
const logo         = logoSenac

const usuario = computed(() => autenticacao.usuario)

const iniciaisDoUsuario = computed(() => {
  if (!usuario.value?.name) return '?'
  return usuario.value.name
    .split(' ')
    .slice(0, 2)
    .map(p => p[0])
    .join('')
    .toUpperCase()
})

const itensDoMenu = computed(() => {
  const menus = [
    { nome: 'Dashboard',       rota: '/dashboard',      icone: LayoutDashboard },
    { nome: 'Lotes',           rota: '/lotes',          icone: PackagePlus     },
    { nome: 'Produtos',        rota: '/produtos',       icone: Package         },
    { nome: 'Perdas',          rota: '/perdas',         icone: Trash2          },
    { nome: 'Histórico',       rota: '/historico',      icone: History         },
    { nome: 'Relatórios',      rota: '/relatorios',     icone: FileText        },
    { nome: 'Rel. Avançados',  rota: '/rel-avancados',  icone: BarChart3       },
    { nome: 'Import/Export',   rota: '/importexport',   icone: Download        },
    { nome: 'Log',             rota: '/log',            icone: Shield          },
  ]

  if (autenticacao.ehAdmin) {
    menus.push({ nome: 'Usuários', rota: '/usuarios', icone: Users })
  }

  return menus
})

async function sair() {
  await autenticacao.fazerLogout()
  router.push('/login')
}
</script>