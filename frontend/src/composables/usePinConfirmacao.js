import { reactive } from 'vue'
import api from '@/servicos/api'

export function usePinConfirmacao() {
  const estado = reactive({
    modalAberto: false,
    pinDigitado: '',
    erroPin: '',
    codigoEnviado: false,
    enviandoCodigo: false,
    verificando: false,
    segundosReenvio: 0,
    subtitulo: 'Necessário para confirmar esta ação',
  })

  let intervaloReenvio = null
  let resolvePromise = null

  function abrirEAguardarConfirmacao(opcoes = {}) {
    estado.subtitulo     = opcoes.subtitulo || 'Necessário para confirmar esta ação'
    estado.pinDigitado   = ''
    estado.erroPin       = ''
    estado.codigoEnviado = false
    estado.modalAberto   = true
    return new Promise((resolve) => { resolvePromise = resolve })
  }

  async function solicitarCodigoPin() {
    estado.enviandoCodigo = true
    estado.erroPin = ''
    try {
      await api.post('/perfil/solicitar-pin')
      estado.codigoEnviado = true
      estado.pinDigitado = ''
      iniciarContagemReenvio()
    } catch (e) {
      estado.erroPin = e.response?.data?.message || 'Erro ao enviar o PIN. Tente novamente.'
    } finally {
      estado.enviandoCodigo = false
    }
  }

  function iniciarContagemReenvio() {
    estado.segundosReenvio = 30
    clearInterval(intervaloReenvio)
    intervaloReenvio = setInterval(() => {
      estado.segundosReenvio--
      if (estado.segundosReenvio <= 0) clearInterval(intervaloReenvio)
    }, 1000)
  }

  async function verificarPin() {
    estado.erroPin = ''
    estado.verificando = true
    try {
      await api.post('/perfil/verificar-pin', { pin: estado.pinDigitado })
      estado.modalAberto   = false
      estado.pinDigitado   = ''
      estado.codigoEnviado = false
      clearInterval(intervaloReenvio)
      if (resolvePromise) { resolvePromise(true); resolvePromise = null }
    } catch (e) {
      estado.erroPin = e.response?.data?.message || 'PIN incorreto. Tente novamente.'
      estado.pinDigitado = ''
    } finally {
      estado.verificando = false
    }
  }

  function cancelar() {
    estado.modalAberto   = false
    estado.pinDigitado   = ''
    estado.erroPin       = ''
    estado.codigoEnviado = false
    clearInterval(intervaloReenvio)
    if (resolvePromise) { resolvePromise(false); resolvePromise = null }
  }

  return { estado, abrirEAguardarConfirmacao, solicitarCodigoPin, verificarPin, cancelar }
}