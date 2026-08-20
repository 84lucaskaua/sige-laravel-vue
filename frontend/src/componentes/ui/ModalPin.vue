<!-- eslint-disable vue/no-mutating-props -->
<template>
  <div v-if="pin.estado.modalAberto" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6">
      <div class="flex justify-between items-start mb-5">
        <div class="flex items-center gap-2">
          <Lock class="text-blue-600 dark:text-blue-400" :size="20" />
          <div>
            <h2 class="text-slate-900 dark:text-white font-bold">Verificação de PIN</h2>
            <p class="text-slate-500 dark:text-slate-400 text-xs">{{ pin.estado.subtitulo }}</p>
          </div>
        </div>
        <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="pin.cancelar()">
          <X :size="18" />
        </button>
      </div>

      <!-- Passo 0: pedir email antes de enviar o PIN -->
      <div v-if="pin.estado.pedirEmail && !pin.estado.emailConfirmado" class="mb-6">
        <label class="block text-sm text-slate-500 dark:text-slate-400 mb-2">Por favor, insira seu email</label>
        <input
          v-model="pin.estado.email"
          type="email"
          placeholder="seuemail@exemplo.com"
          autofocus
          class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-500 transition placeholder-slate-400 dark:placeholder-slate-600"
          @keyup.enter="pin.confirmarEmail()"
        />
        <p v-if="pin.estado.erroEmail" class="text-red-600 dark:text-red-400 text-sm mt-2">{{ pin.estado.erroEmail }}</p>
        <button
          class="w-full mt-4 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition font-medium"
          :disabled="pin.estado.validandoEmail"
          @click="pin.confirmarEmail()"
        >
          {{ pin.estado.validandoEmail ? 'Enviando...' : 'Continuar' }}
        </button>
      </div>

      <template v-else>
        <!-- Passo 1: sem código enviado ainda (fluxo do modal de Salvar, sem pedir email) -->
        <div v-if="!pin.estado.codigoEnviado && !pin.estado.pedirEmail" class="mb-6 text-center">
          <p class="text-slate-500 dark:text-slate-400 text-sm mb-5">
            Se você já tem um PIN, digite-o. Se não tem ou esqueceu, solicite um novo por email.
          </p>
          <button
            class="w-full py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition font-medium"
            :disabled="pin.estado.enviandoCodigo"
            @click="pin.solicitarCodigoPin()"
          >
            {{ pin.estado.enviandoCodigo ? 'Enviando...' : 'Não tenho PIN / esqueci' }}
          </button>
          <p v-if="pin.estado.erroPin" class="text-red-600 dark:text-red-400 text-sm mt-3">{{ pin.estado.erroPin }}</p>

          <div class="mt-5 text-left">
            <label class="block text-sm text-slate-500 dark:text-slate-400 mb-2">Ou digite seu PIN diretamente</label>
            <input
              v-model="pin.estado.pinDigitado"
              type="text"
              maxlength="6"
              inputmode="numeric"
              pattern="[0-9]*"
              placeholder="• • • • • •"
              class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg px-4 py-3 text-center text-2xl tracking-[0.5em] outline-none focus:border-blue-500 transition placeholder-slate-400 dark:placeholder-slate-600"
              @input="pin.estado.pinDigitado = pin.estado.pinDigitado.replace(/\D/g, '')"
              @keyup.enter="pin.verificarPin()"
            />
          </div>
        </div>

        <!-- Passo 2: código acabou de ser enviado -->
        <div v-else class="mb-6">
          <p v-if="pin.estado.pedirEmail" class="text-center text-blue-600 dark:text-blue-400 text-sm font-medium mb-3">
            Por favor, verifique seu email
          </p>
          <label class="block text-sm text-slate-500 dark:text-slate-400 mb-2">PIN enviado por email</label>
          <input
            v-model="pin.estado.pinDigitado"
            type="text"
            maxlength="6"
            inputmode="numeric"
            pattern="[0-9]*"
            placeholder="• • • • • •"
            autofocus
            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg px-4 py-3 text-center text-2xl tracking-[0.5em] outline-none focus:border-blue-500 transition placeholder-slate-400 dark:placeholder-slate-600"
            @input="pin.estado.pinDigitado = pin.estado.pinDigitado.replace(/\D/g, '')"
            @keyup.enter="pin.verificarPin()"
          />
          <p v-if="pin.estado.erroPin" class="text-red-600 dark:text-red-400 text-sm mt-2 text-center">{{ pin.estado.erroPin }}</p>

          <div class="flex items-center justify-center mt-3">
            <button
              class="text-blue-600 dark:text-blue-400 text-xs hover:underline disabled:opacity-40 disabled:cursor-not-allowed"
              :disabled="pin.estado.segundosReenvio > 0"
              @click="pin.solicitarCodigoPin()"
            >
              {{ pin.estado.segundosReenvio > 0 ? `Aguarde ${pin.estado.segundosReenvio}s` : 'Pedir novo PIN' }}
            </button>
          </div>

          <p class="text-slate-500 dark:text-slate-500 text-xs mt-3 text-center">
            Esse PIN é seu e continua valendo até você pedir um novo. Guarde em lugar seguro.
          </p>
        </div>
      </template>

      <div class="flex gap-3">
        <button class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition" @click="pin.cancelar()">
          Cancelar
        </button>
        <button
          v-if="!(pin.estado.pedirEmail && !pin.estado.emailConfirmado)"
          class="flex-1 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition font-medium"
          :disabled="pin.estado.pinDigitado.length < 6 || pin.estado.verificando"
          @click="pin.verificarPin()"
        >
          {{ pin.estado.verificando ? 'Verificando...' : 'Confirmar PIN' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
/* eslint-disable vue/no-mutating-props */
import { Lock, X } from 'lucide-vue-next'

defineProps({
  pin: { type: Object, required: true },
})
</script>