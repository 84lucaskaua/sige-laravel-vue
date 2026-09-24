import { ref, computed, onMounted, onUnmounted } from 'vue'

/**
 * Deixa um botão flutuante (position: fixed) arrastável pela tela, com mouse e toque.
 * - O clique normal continua funcionando: só vira arrasto depois de mover ~5px.
 * - Fica sempre dentro da tela e lembra a última posição (localStorage).
 * - A posição é guardada como distância da direita e de baixo, igual ao
 *   "bottom-5 right-5" original, então o botão não pula quando o menu abre.
 */
export function useBotaoFlutuanteArrastavel({ chave = 'sige:posicao-botao-suporte', margem = 8 } = {}) {
  const posicao = ref(null) // { direita, baixo } em px; null = posição padrão do CSS
  const arrastando = ref(false)
  let inicio = null
  let moveu = false
  let bloquearClique = false
  let tamanho = { w: 56, h: 56 }

  const larguraTela = () => document.documentElement.clientWidth
  const alturaTela = () => document.documentElement.clientHeight

  function limitar(direita, baixo) {
    return {
      direita: Math.min(Math.max(margem, direita), Math.max(margem, larguraTela() - tamanho.w - margem)),
      baixo: Math.min(Math.max(margem, baixo), Math.max(margem, alturaTela() - tamanho.h - margem)),
    }
  }

  function salvar() {
    try {
      localStorage.setItem(chave, JSON.stringify(posicao.value))
    } catch {
      /* sem localStorage: a posição vale só até recarregar */
    }
  }

  function carregar() {
    try {
      const salvo = JSON.parse(localStorage.getItem(chave) || 'null')
      if (salvo && typeof salvo.direita === 'number' && typeof salvo.baixo === 'number') {
        posicao.value = limitar(salvo.direita, salvo.baixo)
      }
    } catch {
      /* ignora valor inválido */
    }
  }

  function aoPressionar(e) {
    if (e.button !== undefined && e.button !== 0) return
    const r = e.currentTarget.getBoundingClientRect()
    tamanho = { w: r.width, h: r.height }
    inicio = {
      px: e.clientX,
      py: e.clientY,
      direita: larguraTela() - r.right,
      baixo: alturaTela() - r.bottom,
    }
    moveu = false
    bloquearClique = false
    e.currentTarget.setPointerCapture?.(e.pointerId)
  }

  function aoMover(e) {
    if (!inicio) return
    const dx = e.clientX - inicio.px
    const dy = e.clientY - inicio.py
    if (!moveu && Math.hypot(dx, dy) < 5) return
    moveu = true
    arrastando.value = true
    posicao.value = limitar(inicio.direita - dx, inicio.baixo - dy)
  }

  function aoSoltar() {
    if (!inicio) return
    inicio = null
    arrastando.value = false
    if (moveu) {
      bloquearClique = true // o "click" logo após o arrasto não deve abrir/fechar o menu
      salvar()
    }
  }

  // Use com @click.capture no botão: descarta o clique gerado ao soltar o arrasto
  function aoClicarCapturando(e) {
    if (bloquearClique) {
      e.preventDefault()
      e.stopImmediatePropagation()
      bloquearClique = false
    }
  }

  function aoRedimensionar() {
    if (posicao.value) posicao.value = limitar(posicao.value.direita, posicao.value.baixo)
  }

  onMounted(() => {
    carregar()
    window.addEventListener('resize', aoRedimensionar)
  })
  onUnmounted(() => window.removeEventListener('resize', aoRedimensionar))

  // Aplique no elemento fixo que envolve o botão
  const estilo = computed(() => {
    if (!posicao.value) return {}
    return {
      right: `${posicao.value.direita}px`,
      bottom: `${posicao.value.baixo}px`,
      left: 'auto',
      top: 'auto',
    }
  })

  return { estilo, posicao, arrastando, aoPressionar, aoMover, aoSoltar, aoClicarCapturando }
}