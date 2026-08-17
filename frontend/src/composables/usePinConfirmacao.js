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
    pedirEmail: false,
    email: '',
    emailEsperado: '',
    emailConfirmado: false,
    erroEmail: '',
    validandoEmail: false,
  })
  let intervaloReenvio = null
  let resolvePromise = null
  function abrirEAguardarConfirmacao(opcoes = {}) {
    estado.subtitulo       = opcoes.subtitulo || 'Necessário para confirmar esta ação'
    estado.pinDigitado     = ''
    estado.erroPin         = ''
    estado.codigoEnviado   = false
    estado.pedirEmail      = false
    estado.email           = ''
    estado.emailConfirmado = false
    estado.erroEmail       = ''
    estado.modalAberto     = true
    return new Promise((resolve) => { resolvePromise = resolve })
  }
  function abrirEPedirEmail(opcoes = {}) {
    estado.subtitulo       = opcoes.subtitulo || 'Por favor, insira seu email'
    estado.pinDigitado     = ''
    estado.erroPin         = ''
    estado.codigoEnviado   = false
    estado.pedirEmail      = true
    estado.email           = ''
    estado.emailEsperado   = opcoes.emailEsperado || ''
    estado.emailConfirmado = false
    estado.erroEmail       = ''
    estado.modalAberto     = true
    return new Promise((resolve) => { resolvePromise = resolve })
  }
  async function confirmarEmail() {
    estado.erroEmail = ''
    const digitado = estado.email.trim().toLowerCase()
    if (!digitado) { estado.erroEmail = 'Informe seu email.'; return }
    if (digitado !== estado.emailEsperado.trim().toLowerCase()) {
      estado.erroEmail = 'Esse email não corresponde à sua conta.'
      return
    }
    estado.validandoEmail  = true
    estado.emailConfirmado = true
    await solicitarCodigoPin()
    estado.validandoEmail  = false
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
      estado.modalAberto     = false
      estado.pinDigitado     = ''
      estado.codigoEnviado   = false
      estado.pedirEmail      = false
      estado.email           = ''
      estado.emailConfirmado = false
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
    estado.modalAberto     = false
    estado.pinDigitado     = ''
    estado.erroPin         = ''
    estado.codigoEnviado   = false
    estado.pedirEmail      = false
    estado.email           = ''
    estado.emailConfirmado = false
    estado.erroEmail       = ''
    clearInterval(intervaloReenvio)
    if (resolvePromise) { resolvePromise(false); resolvePromise = null }
  }
  return { estado, abrirEAguardarConfirmacao, abrirEPedirEmail, confirmarEmail, solicitarCodigoPin, verificarPin, cancelar }
}