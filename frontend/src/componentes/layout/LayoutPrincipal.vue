<template>
  <div :class="['flex h-screen', temaClaro ? 'bg-gray-100' : 'bg-black']">

    <!-- ===== SIDEBAR ===== -->
    <aside :class="[
      temaClaro ? 'bg-white border-gray-200' : 'bg-slate-900 border-slate-800',
      'border-r flex flex-col transition-all duration-300',
      expandido ? 'w-64' : 'w-16'
    ]">

      <!-- Logo -->
      <div :class="['p-4 border-b flex items-center justify-between min-h-[65px]', temaClaro ? 'border-gray-200' : 'border-slate-800']">
        <div v-if="expandido" class="flex items-center gap-3">
          <img :src="logo" alt="Senac" class="h-8" />
          <div>
            <h1 :class="['text-base font-bold', temaClaro ? 'text-gray-900' : 'text-white']">SIGE</h1>
            <p :class="['text-xs', temaClaro ? 'text-gray-500' : 'text-slate-400']">Sistema de Estoque</p>
          </div>
        </div>
        <button
          @click="expandido = !expandido"
          :class="['transition p-1 rounded-lg ml-auto', temaClaro ? 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          <Menu v-if="!expandido" :size="20" />
          <X v-else :size="20" />
        </button>
      </div>

      <!-- Usuário -->
      <div v-if="expandido" :class="['px-4 py-4 border-b', temaClaro ? 'border-gray-200' : 'border-slate-800']">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-9 h-9 rounded-full bg-blue-600 overflow-hidden flex items-center justify-center text-sm font-bold text-white shrink-0">
            <img v-if="usuario?.foto_url" :src="usuario.foto_url" class="w-full h-full object-cover" />
            <span v-else>{{ iniciaisDoUsuario }}</span>
          </div>
          <div>
            <p :class="['text-sm font-medium', temaClaro ? 'text-gray-900' : 'text-white']">{{ usuario?.name }}</p>
            <p :class="['text-xs capitalize', temaClaro ? 'text-gray-500' : 'text-slate-400']">{{ usuario?.perfil }}</p>
          </div>
        </div>
        <RouterLink
          to="/perfil"
          :class="['flex items-center gap-2 text-xs px-2 py-1.5 rounded-lg transition', temaClaro ? 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' : 'text-slate-400 hover:text-white hover:bg-slate-800']"
        >
          <Settings :size="14" />
          Editar Perfil
        </RouterLink>
      </div>

      <!-- Avatar colapsado -->
      <div v-else :class="['flex justify-center py-4 border-b', temaClaro ? 'border-gray-200' : 'border-slate-800']">
        <div class="w-8 h-8 rounded-full bg-blue-600 overflow-hidden flex items-center justify-center text-xs font-bold text-white">
          <img v-if="usuario?.foto_url" :src="usuario.foto_url" class="w-full h-full object-cover" />
          <span v-else>{{ iniciaisDoUsuario }}</span>
        </div>
      </div>

      <!-- Menu -->
      <nav class="flex-1 p-2 overflow-y-auto">
        <ul class="space-y-0.5">
          <li v-for="item in itensDoMenu" :key="item.rota">
            <RouterLink
              :to="item.rota"
              :title="!expandido ? item.nome : ''"
              :class="[
                'flex items-center gap-3 px-3 py-2.5 rounded-lg transition text-sm',
                temaClaro ? 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' : 'text-slate-400 hover:bg-slate-800 hover:text-white',
                !expandido && 'justify-center'
              ]"
              active-class="bg-blue-600 !text-white font-medium hover:bg-blue-600"
            >
              <component :is="item.icone" :size="18" class="shrink-0" />
              <span v-if="expandido">{{ item.nome }}</span>
            </RouterLink>
          </li>
        </ul>
      </nav>

      <!-- Rodapé -->
      <div :class="['p-2 border-t space-y-0.5', temaClaro ? 'border-gray-200' : 'border-slate-800']">

        <!-- Notificações -->
        <button
          @click="abrirNotificacoes"
          :title="!expandido ? 'Notificações' : ''"
          :class="[
            'relative w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition text-sm',
            temaClaro ? 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' : 'text-slate-400 hover:bg-slate-800 hover:text-white',
            !expandido && 'justify-center'
          ]"
        >
          <div class="relative shrink-0">
            <Bell :size="18" />
            <span
              v-if="totalNaoLidas > 0"
              class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center"
            >{{ totalNaoLidas > 9 ? '9+' : totalNaoLidas }}</span>
          </div>
          <span v-if="expandido">Notificações</span>
        </button>

        <!-- Atalhos -->
        <button
          @click="painelAtalhosAberto = true"
          :title="!expandido ? 'Atalhos' : ''"
          :class="[
            'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition text-sm',
            temaClaro ? 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' : 'text-slate-400 hover:bg-slate-800 hover:text-white',
            !expandido && 'justify-center'
          ]"
        >
          <Keyboard :size="18" class="shrink-0" />
          <span v-if="expandido">Atalhos</span>
        </button>

        <!-- Modo Claro/Escuro -->
        <button
          @click="toggleTema"
          :title="!expandido ? (temaClaro ? 'Modo Escuro' : 'Modo Claro') : ''"
          :class="[
            'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition text-sm',
            temaClaro ? 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' : 'text-slate-400 hover:bg-slate-800 hover:text-white',
            !expandido && 'justify-center'
          ]"
        >
          <Sun v-if="temaClaro" :size="18" class="shrink-0" />
          <Moon v-else :size="18" class="shrink-0" />
          <span v-if="expandido">{{ temaClaro ? 'Modo Escuro' : 'Modo Claro' }}</span>
        </button>

        <!-- Sair -->
        <button
          @click="sair"
          :title="!expandido ? 'Sair' : ''"
          :class="[
            'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-red-400 hover:bg-red-900/20 hover:text-red-300 transition text-sm',
            !expandido && 'justify-center'
          ]"
        >
          <LogOut :size="18" class="shrink-0" />
          <span v-if="expandido">Sair</span>
        </button>

      </div>
    </aside>

    <!-- ===== PAINEL DE NOTIFICAÇÕES ===== -->
    <transition name="slide">
      <div
        v-if="painelAberto"
        :class="['fixed right-0 top-0 h-full w-96 border-l z-50 flex flex-col shadow-2xl', temaClaro ? 'bg-white border-gray-200' : 'bg-slate-900 border-slate-800']"
      >
        <div :class="['flex items-center justify-between px-5 py-4 border-b', temaClaro ? 'border-gray-200' : 'border-slate-800']">
          <h2 :class="['font-bold text-base', temaClaro ? 'text-gray-900' : 'text-white']">Notificações</h2>
          <button @click="painelAberto = false" :class="['transition', temaClaro ? 'text-gray-500 hover:text-gray-900' : 'text-slate-400 hover:text-white']">
            <X :size="18" />
          </button>
        </div>

        <div :class="['flex border-b', temaClaro ? 'border-gray-200' : 'border-slate-800']">
          <button
            @click="aba = 'todas'"
            :class="['flex-1 py-2.5 text-sm font-medium transition', aba === 'todas' ? 'bg-blue-600 text-white' : (temaClaro ? 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' : 'text-slate-400 hover:text-white hover:bg-slate-800')]"
          >Todas ({{ notificacoes.length }})</button>
          <button
            @click="aba = 'nao_lidas'"
            :class="['flex-1 py-2.5 text-sm font-medium transition', aba === 'nao_lidas' ? 'bg-blue-600 text-white' : (temaClaro ? 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' : 'text-slate-400 hover:text-white hover:bg-slate-800')]"
          >Não Lidas ({{ totalNaoLidas }})</button>
        </div>

        <div class="flex-1 overflow-y-auto">
          <div v-if="carregando" :class="['flex items-center justify-center h-40 text-sm', temaClaro ? 'text-gray-500' : 'text-slate-400']">Carregando...</div>
          <div v-else-if="notificacoesFiltradas.length === 0" class="flex flex-col items-center justify-center h-64 gap-3">
            <div :class="['w-12 h-12 rounded-full flex items-center justify-center', temaClaro ? 'bg-gray-100' : 'bg-slate-800']">
              <Info :size="22" :class="temaClaro ? 'text-gray-400' : 'text-slate-500'" />
            </div>
            <p :class="['font-medium text-sm', temaClaro ? 'text-gray-900' : 'text-white']">Nenhuma notificação</p>
            <p :class="['text-xs', temaClaro ? 'text-gray-400' : 'text-slate-500']">Você não tem notificações no momento</p>
          </div>
          <div v-else :class="['divide-y', temaClaro ? 'divide-gray-200' : 'divide-slate-800']">
            <div
              v-for="notif in notificacoesFiltradas"
              :key="notif.id"
              @click="marcarLida(notif)"
              :class="['px-5 py-4 cursor-pointer transition', temaClaro ? 'hover:bg-gray-50' : 'hover:bg-slate-800/60', !notif.lida && 'border-l-2 border-blue-500']"
            >
              <div class="flex items-start gap-3">
                <div :class="[
                  'w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5',
                  notif.tipo === 'vencimento'
                    ? (temaClaro ? 'bg-orange-100' : 'bg-orange-900/40')
                    : (temaClaro ? 'bg-red-100' : 'bg-red-900/40')
                ]">
                  <AlertTriangle :size="16" :class="notif.tipo === 'vencimento' ? 'text-orange-400' : 'text-red-400'" />
                </div>
                <div class="flex-1 min-w-0">
                  <p :class="['text-sm font-medium leading-snug', temaClaro ? 'text-gray-900' : 'text-white']">{{ notif.titulo }}</p>
                  <p :class="['text-xs mt-0.5 leading-relaxed', temaClaro ? 'text-gray-500' : 'text-slate-400']">{{ notif.descricao }}</p>
                  <p :class="['text-xs mt-1.5', temaClaro ? 'text-gray-400' : 'text-slate-600']">{{ notif.tempo }}</p>
                </div>
                <div v-if="!notif.lida" class="w-2 h-2 rounded-full bg-blue-500 shrink-0 mt-2"></div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="notificacoes.length > 0" :class="['px-5 py-3 border-t', temaClaro ? 'border-gray-200' : 'border-slate-800']">
          <button @click="marcarTodasLidas" class="text-xs text-blue-400 hover:text-blue-300 transition">
            Marcar todas como lidas
          </button>
        </div>
      </div>
    </transition>

    <div v-if="painelAberto" @click="painelAberto = false" class="fixed inset-0 z-40" />

    <!-- ===== PAINEL DE ATALHOS ===== -->
    <transition name="slide">
      <div
        v-if="painelAtalhosAberto"
        :class="['fixed right-0 top-0 h-full w-96 border-l z-50 flex flex-col shadow-2xl', temaClaro ? 'bg-white border-gray-200' : 'bg-slate-900 border-slate-800']"
      >
        <div :class="['flex items-start justify-between px-5 py-4 border-b', temaClaro ? 'border-gray-200' : 'border-slate-800']">
          <div class="flex items-center gap-3">
            <div :class="['w-9 h-9 rounded-lg flex items-center justify-center', temaClaro ? 'bg-blue-100' : 'bg-blue-600/20']">
              <Keyboard :size="18" class="text-blue-400" />
            </div>
            <div>
              <h2 :class="['font-bold text-base', temaClaro ? 'text-gray-900' : 'text-white']">Atalhos de Teclado</h2>
              <p :class="['text-xs', temaClaro ? 'text-gray-500' : 'text-slate-400']">Acelere seu trabalho com atalhos de teclado</p>
            </div>
          </div>
          <button @click="painelAtalhosAberto = false" :class="['transition mt-1', temaClaro ? 'text-gray-500 hover:text-gray-900' : 'text-slate-400 hover:text-white']">
            <X :size="18" />
          </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-5">

          <div v-for="(grupo, label) in gruposAtalhos" :key="label">
            <p :class="['text-xs font-semibold uppercase tracking-wider mb-2', temaClaro ? 'text-gray-400' : 'text-slate-500']">{{ label }}</p>
            <div class="space-y-1">
              <div
                v-for="atalho in grupo"
                :key="atalho.acao"
                :class="['flex items-center justify-between rounded-lg px-3 py-2.5', temaClaro ? 'bg-gray-100' : 'bg-slate-800']"
              >
                <span :class="['text-sm', temaClaro ? 'text-gray-700' : 'text-slate-300']">{{ atalho.acao }}</span>
                <div class="flex items-center gap-1">
                  <kbd
                    v-for="tecla in atalho.teclas"
                    :key="tecla"
                    :class="['px-2 py-0.5 border rounded text-xs font-mono', temaClaro ? 'bg-white border-gray-300 text-gray-700' : 'bg-slate-700 border-slate-600 text-white']"
                  >{{ tecla }}</kbd>
                </div>
              </div>
            </div>
          </div>

          <div :class="['border rounded-lg px-4 py-3', temaClaro ? 'bg-blue-50 border-blue-200' : 'bg-blue-600/20 border-blue-600/40']">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-base">💡</span>
              <span class="text-blue-400 text-sm font-semibold">Dica Profissional</span>
            </div>
            <p :class="['text-xs leading-relaxed', temaClaro ? 'text-gray-600' : 'text-slate-300']">
              Use <kbd :class="['px-1.5 py-0.5 border rounded text-xs font-mono', temaClaro ? 'bg-white border-gray-300 text-gray-700' : 'bg-slate-700 border-slate-600 text-white']">Ctrl+K</kbd> para acessar a busca global rapidamente de qualquer lugar do sistema.
            </p>
          </div>

        </div>

        <div :class="['px-5 py-3 border-t flex items-center justify-between', temaClaro ? 'border-gray-200' : 'border-slate-800']">
          <span :class="['text-xs', temaClaro ? 'text-gray-400' : 'text-slate-500']">{{ totalAtalhos }} atalhos disponíveis</span>
          <div class="flex items-center gap-2">
            <kbd :class="['px-2 py-0.5 border rounded text-xs font-mono', temaClaro ? 'bg-white border-gray-300 text-gray-700' : 'bg-slate-700 border-slate-600 text-white']">ESC</kbd>
            <button @click="painelAtalhosAberto = false" :class="['text-sm transition', temaClaro ? 'text-gray-600 hover:text-gray-900' : 'text-slate-300 hover:text-white']">Fechar</button>
          </div>
        </div>
      </div>
    </transition>

    <div v-if="painelAtalhosAberto" @click="painelAtalhosAberto = false" class="fixed inset-0 z-40" />

    <!-- ===== CONTEÚDO ===== -->
    <main class="flex-1 overflow-auto">
      <RouterView />
    </main>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import {
  LayoutDashboard, PackagePlus, Package, Trash2,
  History, FileText, BarChart3, Download, Shield,
  Users, Settings, LogOut, Menu, X, Bell, Keyboard,
  Sun, Moon, AlertTriangle, Info
} from 'lucide-vue-next'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import { useTemaStore } from '@/servicos/tema.store'
import { storeToRefs } from 'pinia'
import logoSenac from '@/componentes/img/Senac_logo.svg.png'
import api from '@/servicos/api'

const router              = useRouter()
const autenticacao        = useAutenticacaoStore()
const temaStore           = useTemaStore()
const { temaClaro }       = storeToRefs(temaStore)
const logo                = logoSenac
const expandido           = ref(true)
const painelAberto        = ref(false)
const painelAtalhosAberto = ref(false)
const aba                 = ref('todas')
const carregando          = ref(false)
const notificacoes        = ref([])

const usuario = computed(() => autenticacao.usuario)

const iniciaisDoUsuario = computed(() => {
  if (!usuario.value?.name) return '?'
  return usuario.value.name.split(' ').slice(0, 2).map(p => p[0]).join('').toUpperCase()
})

const itensDoMenu = computed(() => {
  const menus = [
    { nome: 'Dashboard',      rota: '/dashboard',     icone: LayoutDashboard },
    { nome: 'Lotes',          rota: '/lotes',         icone: PackagePlus     },
    { nome: 'Produtos',       rota: '/produtos',      icone: Package         },
    { nome: 'Perdas',         rota: '/perdas',        icone: Trash2          },
    { nome: 'Histórico',      rota: '/historico',     icone: History         },
    { nome: 'Relatórios',     rota: '/relatorios',    icone: FileText        },
    { nome: 'Rel. Avançados', rota: '/rel-avancados', icone: BarChart3       },
    { nome: 'Import/Export',  rota: '/importexport',  icone: Download        },
    { nome: 'Log',            rota: '/log',           icone: Shield          },
  ]
  if (autenticacao.ehAdmin) menus.push({ nome: 'Usuários', rota: '/usuarios', icone: Users })
  return menus
})

// ===== ATALHOS =====
const atalhosBusca = [
  { acao: 'Abrir busca global', teclas: ['Ctrl+K'] },
]
const atalhoNavegacao = [
  { acao: 'Abrir busca global',           teclas: ['Ctrl+K'] },
  { acao: 'Ir para Dashboard',            teclas: ['Alt+D']  },
  { acao: 'Ir para Lotes',               teclas: ['Alt+L']  },
  { acao: 'Ir para Produtos',            teclas: ['Alt+P']  },
  { acao: 'Ir para Histórico',           teclas: ['Alt+H']  },
  { acao: 'Ir para Relatórios Avançados', teclas: ['Alt+R'] },
  { acao: 'Ir para Log',                 teclas: ['Alt+A']  },
  { acao: 'Abrir notificações',          teclas: ['Alt+N']  },
]
const atalhosAcoes = [
  { acao: 'Mostrar atalhos de teclado', teclas: ['Shift+?'] },
  { acao: 'Alternar tema claro/escuro', teclas: ['Alt+T']   },
]

const gruposAtalhos = computed(() => ({
  'Busca': atalhosBusca,
  'Navegação': atalhoNavegacao,
  'Ações Rápidas': atalhosAcoes,
}))

const totalAtalhos = computed(() =>
  atalhosBusca.length + atalhoNavegacao.length + atalhosAcoes.length
)

// ===== NOTIFICAÇÕES =====
const notificacoesFiltradas = computed(() =>
  aba.value === 'nao_lidas' ? notificacoes.value.filter(n => !n.lida) : notificacoes.value
)
const totalNaoLidas = computed(() => notificacoes.value.filter(n => !n.lida).length)

function diasParaVencer(dataValidade) {
  if (!dataValidade) return null
  const hoje = new Date(); hoje.setHours(0,0,0,0)
  const venc = new Date(dataValidade); venc.setHours(0,0,0,0)
  return Math.ceil((venc - hoje) / (1000 * 60 * 60 * 24))
}

function tempoRelativo(dataValidade) {
  const dias = diasParaVencer(dataValidade)
  if (dias === null) return ''
  if (dias < 0)  return `Vencido há ${Math.abs(dias)} dia(s)`
  if (dias === 0) return 'Vence hoje'
  return `Vence em ${dias} dia(s)`
}

async function carregarNotificacoes() {
  carregando.value = true
  try {
    const { data } = await api.get('/produtos')
    const lista = []
    data.forEach(item => {
      if (item.data_validade) {
        const dias = diasParaVencer(item.data_validade)
        if (dias !== null && dias <= 30) {
          lista.push({
            id: `venc-${item.id_item}`, tipo: 'vencimento',
            titulo: dias < 0 ? `${item.nome} está vencido` : dias === 0 ? `${item.nome} vence hoje` : `${item.nome} vence em breve`,
            descricao: `${tempoRelativo(item.data_validade)} · Estoque: ${item.quantidade} ${item.unidade_medida}`,
            tempo: tempoRelativo(item.data_validade), lida: false,
          })
        }
      }
      if (item.estoque_minimo && item.quantidade <= item.estoque_minimo) {
        lista.push({
          id: `estoque-${item.id_item}`, tipo: 'estoque',
          titulo: `Estoque baixo: ${item.nome}`,
          descricao: `Quantidade atual (${item.quantidade} ${item.unidade_medida}) atingiu o mínimo de ${item.estoque_minimo}`,
          tempo: 'Agora', lida: false,
        })
      }
    })
    notificacoes.value = lista
  } catch (e) {
    console.error('Erro ao carregar notificações', e)
  } finally {
    carregando.value = false
  }
}

async function abrirNotificacoes() {
  painelAberto.value = true
  aba.value = 'todas'
  await carregarNotificacoes()
}

function marcarLida(notif) { notif.lida = true }
function marcarTodasLidas() { notificacoes.value.forEach(n => n.lida = true) }

function toggleTema() {
  temaStore.toggleTema()
}

async function sair() {
  await autenticacao.fazerLogout()
  router.push('/login')
}

function handleKeyboard(e) {
  const ctrl  = e.ctrlKey
  const alt   = e.altKey
  const shift = e.shiftKey
  const key   = e.key.toLowerCase()

  if (ctrl && key === 'k')  { e.preventDefault() }
  if (alt && key === 'd')   { e.preventDefault(); router.push('/dashboard') }
  if (alt && key === 'l')   { e.preventDefault(); router.push('/lotes') }
  if (alt && key === 'p')   { e.preventDefault(); router.push('/produtos') }
  if (alt && key === 'h')   { e.preventDefault(); router.push('/historico') }
  if (alt && key === 'r')   { e.preventDefault(); router.push('/rel-avancados') }
  if (alt && key === 'a')   { e.preventDefault(); router.push('/log') }
  if (alt && key === 'n')   { e.preventDefault(); abrirNotificacoes() }
  if (alt && key === 't')   { e.preventDefault(); toggleTema() }
  if (shift && e.key === '?') { e.preventDefault(); painelAtalhosAberto.value = true }
  if (e.key === 'Escape') {
    painelAberto.value        = false
    painelAtalhosAberto.value = false
  }
}

onMounted(() => {
  carregarNotificacoes()
  window.addEventListener('keydown', handleKeyboard)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyboard)
})
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(100%);
}
</style>