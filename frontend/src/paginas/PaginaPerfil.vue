<template>
  <div class="p-6 flex justify-center">
    <div class="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-md">

      <div class="flex items-center justify-between p-5 border-b border-slate-800">
        <div>
          <h1 class="text-lg font-bold text-white">Editar Perfil</h1>
          <p class="text-xs text-slate-400 mt-0.5">Atualize suas informações pessoais e configurações de conta</p>
        </div>
      </div>

      <div class="p-5 space-y-5">

        <div v-if="sucesso" class="p-3 rounded-lg bg-green-900/40 border border-green-700 text-green-300 text-sm">{{ sucesso }}</div>
        <div v-if="erro" class="p-3 rounded-lg bg-red-900/40 border border-red-700 text-red-300 text-sm">{{ erro }}</div>

        <div class="flex flex-col items-center gap-3">
          <div class="relative">
            <div class="w-20 h-20 rounded-full bg-slate-700 flex items-center justify-center overflow-hidden">
              <img v-if="imagemPreview" :src="imagemPreview" class="w-full h-full object-cover" />
              <UserRound v-else :size="36" class="text-slate-400" />
            </div>
            <label class="absolute bottom-0 right-0 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center cursor-pointer hover:bg-blue-700 transition">
              <Camera :size="13" class="text-white" />
              <input type="file" accept="image/*" class="hidden" @change="onFotoChange" />
            </label>
          </div>
          <button v-if="imagemPreview" type="button" @click="removerImagem" class="text-xs bg-red-900/40 hover:bg-red-900/60 text-red-400 px-4 py-1.5 rounded-lg transition">
            Remover Foto
          </button>
        </div>

        <div>
          <label class="flex items-center gap-1.5 text-sm text-slate-300 font-medium mb-1">
            <UserRound :size="14" /> Nome
          </label>
          <input v-model="form.nome" type="text" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500" />
        </div>

        <div>
          <label class="flex items-center gap-1.5 text-sm text-slate-300 font-medium mb-1">
            <Mail :size="14" /> Email
          </label>
          <input :value="usuario && usuario.email" disabled class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-3 py-2 text-slate-500 text-sm cursor-not-allowed" />
          <p class="text-xs text-blue-400 mt-1">O email não pode ser alterado</p>
        </div>

        <div class="border-t border-slate-800 pt-4">
          <label class="flex items-center gap-1.5 text-sm text-slate-300 font-medium mb-3">
            <Lock :size="14" /> Alterar Senha (opcional)
          </label>
          <div class="space-y-3">
            <input v-model="senhas.atual" type="password" placeholder="Digite sua senha atual" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500 placeholder-slate-600" />
            <input v-model="senhas.nova" type="password" placeholder="Mínimo 6 caracteres" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500 placeholder-slate-600" />
            <input v-model="senhas.confirmacao" type="password" placeholder="Digite a senha novamente" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500 placeholder-slate-600" />
          </div>
        </div>

        <div class="flex gap-3 pt-2">
          <button type="button" @click="$router.back()" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium py-2 rounded-lg transition">Cancelar</button>
          <button type="button" @click="salvar" :disabled="salvando" class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium py-2 rounded-lg transition">
            {{ salvando ? 'Salvando...' : 'Salvar Alterações' }}
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { UserRound, Mail, Lock, Camera } from 'lucide-vue-next'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'

const autenticacao = useAutenticacaoStore()
const usuario = autenticacao.usuario

const sucesso = ref('')
const erro = ref('')
const salvando = ref(false)
const imagemPreview = ref((usuario && usuario.foto_url) ? usuario.foto_url : '')
const imagemBase64 = ref('')

const form = reactive({
  nome: (usuario && usuario.name) ? usuario.name : '',
})

const senhas = reactive({
  atual: '',
  nova: '',
  confirmacao: '',
})

function onFotoChange(e) {
  const arquivo = e.target.files[0]
  if (!arquivo) return
  if (arquivo.size > 2 * 1024 * 1024) {
    erro.value = 'A imagem deve ter no máximo 2MB.'
    return
  }
  const reader = new FileReader()
  reader.onloadend = () => {
    imagemBase64.value = reader.result
    imagemPreview.value = reader.result
  }
  reader.readAsDataURL(arquivo)
}

function removerImagem() {
  imagemBase64.value = ''
  imagemPreview.value = ''
}

function limparMensagens() {
  sucesso.value = ''
  erro.value = ''
}

async function salvar() {
  limparMensagens()

  if (senhas.nova || senhas.atual || senhas.confirmacao) {
    if (!senhas.atual) { erro.value = 'Informe a senha atual.'; return }
    if (senhas.nova.length < 6) { erro.value = 'A nova senha deve ter pelo menos 6 caracteres.'; return }
    if (senhas.nova !== senhas.confirmacao) { erro.value = 'A nova senha e a confirmação não coincidem.'; return }
  }

  salvando.value = true
  try {
    const resposta = await api.put('/perfil', {
      nome: form.nome,
      foto_url: imagemBase64.value || imagemPreview.value || null,
    })

    autenticacao.usuario.name = resposta.data.usuario.name
    autenticacao.usuario.foto_url = resposta.data.usuario.foto_url
    localStorage.setItem('usuario', JSON.stringify(autenticacao.usuario))

    if (senhas.nova) {
      await api.put('/perfil/senha', {
        senha_atual: senhas.atual,
        nova_senha: senhas.nova,
        nova_senha_confirmation: senhas.confirmacao,
      })
      senhas.atual = ''
      senhas.nova = ''
      senhas.confirmacao = ''
    }

    sucesso.value = 'Perfil atualizado com sucesso!'
  } catch (e) {
    erro.value = (e.response && e.response.data && e.response.data.message) ? e.response.data.message : 'Erro ao salvar.'
  } finally {
    salvando.value = false
  }
}
</script>