// ============================================================
// Rotas do Frontend (Vue Router)
//
// Define quais componentes aparecem em cada URL.
// As "guardas de navegação" (beforeEach) verificam
// se o usuário tem permissão antes de entrar em cada página.
// ============================================================

import { createRouter, createWebHistory } from 'vue-router'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'

// Importação lazy: cada página só é carregada quando o usuário acessar
const PaginaLogin       = () => import('@/paginas/PaginaLogin.vue')
const PaginaDashboard   = () => import('@/paginas/PaginaDashboard.vue')
const PaginaProdutos    = () => import('@/paginas/PaginaProdutos.vue')
const PaginaLotes       = () => import('@/paginas/PaginaLotes.vue')
const PaginaMovimentos  = () => import('@/paginas/PaginaMovimentos.vue')
const PaginaRelatorios  = () => import('@/paginas/PaginaRelatorios.vue')
const PaginaUsuarios    = () => import('@/paginas/PaginaUsuarios.vue')
const PaginaPerfil      = () => import('@/paginas/PaginaPerfil.vue')
const PaginaPerdas      = () => import('@/paginas/PaginaPerdas.vue')
const PaginaHistorico   = () => import('@/paginas/PaginaHistorico.vue')
const LayoutPrincipal   = () => import('@/componentes/layout/LayoutPrincipal.vue')

const rotas = [
  // Rota pública (não precisa de login)
  {
    path: '/login',
    name: 'login',
    component: PaginaLogin,
  },

  // Rotas protegidas (precisa de login)
  {
    path: '/',
    component: LayoutPrincipal,
    meta: { requerLogin: true },
    children: [
      {
        path: '',
        redirect: '/dashboard',
      },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: PaginaDashboard,
      },
      {
        path: 'produtos',
        name: 'produtos',
        component: PaginaProdutos,
      },
      {
        path: 'lotes',
        name: 'lotes',
        component: PaginaLotes,
      },
      {
        path: 'movimentos',
        name: 'movimentos',
        component: PaginaMovimentos,
      },
      {
        path: 'perdas',
        name: 'perdas',
        component: PaginaPerdas,
      },
      {
        path: 'historico',
        name: 'historico',
        component: PaginaHistorico,
      },
      {
        path: 'relatorios',
        name: 'relatorios',
        component: PaginaRelatorios,
      },
      {
        path: 'usuarios',
        name: 'usuarios',
        component: PaginaUsuarios,
        meta: { requerPerfil: 'root' },
      },
      {
        path: 'perfil',
        name: 'perfil',
        component: PaginaPerfil,
      },
    ],
  },

  // Redireciona qualquer URL desconhecida para o dashboard
  {
    path: '/:caminhoDesconhecido(.*)*',
    redirect: '/dashboard',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes: rotas,
})

// ---- Guarda de Navegação Global ----
router.beforeEach((rotaDestino) => {
  const autenticacao = useAutenticacaoStore()

  if (rotaDestino.meta.requerLogin && !autenticacao.estaLogado) {
    return { name: 'login' }
  }

  if (rotaDestino.meta.requerPerfil) {
    const perfilNecessario = rotaDestino.meta.requerPerfil
    if (autenticacao.perfil !== perfilNecessario) {
      return { name: 'dashboard' }
    }
  }

  if (rotaDestino.name === 'login' && autenticacao.estaLogado) {
    return { name: 'dashboard' }
  }
})

export default router