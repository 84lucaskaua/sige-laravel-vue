<template>
  <!-- Botão flutuante -->
  <button
    v-if="!aberto"
    class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full bg-blue-600 hover:bg-blue-700 shadow-lg flex items-center justify-center transition"
    @click="aberto = true"
  >
    <MessageCircle :size="24" class="text-white" />
  </button>

  <!-- Janela do chat -->
  <div
    v-if="aberto"
    class="fixed bottom-6 right-6 z-50 w-96 h-[32rem] rounded-xl shadow-2xl flex flex-col bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800"
  >
    <!-- Cabeçalho -->
    <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-800 bg-blue-600 rounded-t-xl">
      <div class="flex items-center gap-2">
        <Bot :size="20" class="text-white" />
        <span class="font-semibold text-white text-sm">Assistente SIGE</span>
      </div>
      <button class="text-white hover:text-slate-200 transition" @click="aberto = false">
        <X :size="20" />
      </button>
    </div>

    <!-- Mensagens -->
    <div ref="areaMensagens" class="flex-1 overflow-y-auto p-4 space-y-3">
      <div v-if="mensagens.length === 0" class="text-center text-sm text-slate-400 dark:text-slate-500 mt-8">
        Pergunte sobre estoque, validades, perdas ou movimentações.
      </div>

      <div
        v-for="(msg, i) in mensagens"
        :key="i"
        class="flex"
        :class="msg.autor === 'usuario' ? 'justify-end' : 'justify-start'"
      >
        <div
          class="max-w-[80%] rounded-lg px-3 py-2 text-sm whitespace-pre-wrap"
          :class="msg.autor === 'usuario'
            ? 'bg-blue-600 text-white'
            : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100'"
        >
          {{ msg.texto }}
        </div>
      </div>

      <div v-if="carregando" class="flex justify-start">
        <div class="bg-slate-100 dark:bg-slate-800 rounded-lg px-3 py-2 text-sm text-slate-500 dark:text-slate-400">
          Digitando...
        </div>
      </div>
    </div>

    <!-- Campo de input -->
    <form class="p-3 border-t border-slate-200 dark:border-slate-800 flex gap-2" @submit.prevent="enviarMensagem">
      <input
        v-model="pergunta"
        type="text"
        placeholder="Digite sua pergunta..."
        :disabled="carregando"
        class="flex-1 rounded-lg px-3 py-2 text-sm outline-none bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:border-blue-500 transition disabled:opacity-50"
      />
      <button
        type="submit"
        :disabled="carregando || !pergunta.trim()"
        class="w-10 h-10 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-50 flex items-center justify-center transition shrink-0"
      >
        <Send :size="16" class="text-white" />
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import { MessageCircle, Bot, X, Send } from 'lucide-vue-next'
import api from '@/servicos/api'

const aberto        = ref(false)
const pergunta       = ref('')
const mensagens      = ref([])
const carregando     = ref(false)
const areaMensagens  = ref(null)

async function rolarParaFinal() {
  await nextTick()
  if (areaMensagens.value) {
    areaMensagens.value.scrollTop = areaMensagens.value.scrollHeight
  }
}

async function enviarMensagem() {
  const texto = pergunta.value.trim()
  if (!texto || carregando.value) return

  mensagens.value.push({ autor: 'usuario', texto })
  pergunta.value = ''
  carregando.value = true
  rolarParaFinal()

  try {
    const resposta = await api.post('/chatbot', { mensagem: texto })
    mensagens.value.push({ autor: 'bot', texto: resposta.data.resposta })
  } catch (e) {
    mensagens.value.push({
      autor: 'bot',
      texto: e.response?.data?.mensagem || 'Não foi possível responder agora. Tente novamente.',
    })
  } finally {
    carregando.value = false
    rolarParaFinal()
  }
}
</script>