<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50" @click.self="fecharSeFora">

    <!-- ===== ETAPA 1: CONFIRMAÇÃO ===== -->
    <div v-if="etapa === 1" class="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-md p-6">

      <div class="flex justify-between items-start mb-5">
        <div class="flex items-center gap-2">
          <Shield class="text-yellow-400" :size="20" />
          <div>
            <h2 class="text-white font-bold">Confirmação de Segurança</h2>
            <p class="text-slate-400 text-xs">Esta ação requer confirmação em 2 etapas</p>
          </div>
        </div>
        <button @click="$emit('fechar')" class="text-slate-400 hover:text-white">
          <X :size="18" />
        </button>
      </div>

      <div class="bg-green-900/30 border border-green-700 rounded-lg p-4 mb-3">
        <div class="flex items-center gap-2 mb-1">
          <Shield class="text-green-400" :size="16" />
          <span class="text-white font-medium text-sm">Entrada de Estoque</span>
        </div>
        <p class="text-slate-300 text-sm">
          Você está adicionando
          <strong class="text-white">{{ form.quantidade || '?' }} unidades</strong>
          do produto <strong class="text-white">"{{ item.nome }}"</strong>.
          O estoque será atualizado de
          <strong class="text-white">{{ item.quantidade }}</strong> para
          <strong class="text-white">{{ item.quantidade + (form.quantidade || 0) }}</strong>.
        </p>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-2 mb-1">
          <Lock :size="15" class="text-slate-400" />
          <span class="text-white font-medium text-sm">Etapa 1 de 2: Confirmação</span>
        </div>
        <p class="text-slate-400 text-sm">Você está prestes a realizar uma ação que afeta o sistema. Confirme para prosseguir para a etapa de verificação PIN.</p>
      </div>

      <div class="flex gap-3">
        <button @click="$emit('fechar')"
          class="flex-1 py-2.5 rounded-lg border border-slate-600 text-slate-300 hover:bg-slate-800 transition text-sm">
          Cancelar
        </button>
        <button @click="etapa = 2"
          class="flex-1 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition font-medium text-sm">
          Confirmar e Prosseguir
        </button>
      </div>
    </div>

    <!-- ===== ETAPA 2: PIN ===== -->
    <div v-else-if="etapa === 2" class="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-md p-6">

      <div class="flex justify-between items-start mb-5">
        <div class="flex items-center gap-2">
          <Lock class="text-blue-400" :size="20" />
          <div>
            <h2 class="text-white font-bold">Verificação de PIN</h2>
            <p class="text-slate-400 text-xs">Etapa 2 de 2: Digite o PIN de segurança</p>
          </div>
        </div>
        <button @click="$emit('fechar')" class="text-slate-400 hover:text-white">
          <X :size="18" />
        </button>
      </div>

      <div class="mb-6">
        <label class="block text-sm text-slate-400 mb-2">PIN de Segurança</label>
        <input
          ref="inputPin"
          v-model="pinDigitado"
          type="password"
          maxlength="4"
          inputmode="numeric"
          pattern="[0-9]*"
          placeholder="• • • •"
          autofocus
          class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-3 text-center text-2xl tracking-[0.6em] outline-none focus:border-blue-500 transition placeholder-slate-600"
          @input="pinDigitado = pinDigitado.replace(/\D/g, '')"
          @keyup.enter="verificarPin"
        />
        <p v-if="erroPin" class="text-red-400 text-sm mt-2 text-center">{{ erroPin }}</p>
        <p v-if="tentativas > 0 && !erroPin" class="text-yellow-400 text-xs mt-2 text-center">
          Tentativas restantes: {{ 3 - tentativas }}
        </p>
      </div>

      <div class="flex gap-3">
        <button @click="etapa = 1; pinDigitado = ''; erroPin = ''"
          class="flex-1 py-2.5 rounded-lg border border-slate-600 text-slate-300 hover:bg-slate-800 transition text-sm">
          Voltar
        </button>
        <button @click="verificarPin" :disabled="pinDigitado.length < 4 || salvando"
          class="flex-1 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition font-medium text-sm">
          {{ salvando ? 'Confirmando...' : 'Confirmar PIN' }}
        </button>
      </div>
    </div>

    <!-- ===== ETAPA 0: FORMULÁRIO DE ENTRADA ===== -->
    <div v-else class="bg-slate-900 border border-slate-700 rounded-xl p-6 w-full max-w-md">

      <div class="flex justify-between items-center mb-6">
        <div>
          <h2 class="text-lg font-bold text-white">Entrada de Estoque</h2>
          <p class="text-slate-400 text-xs mt-0.5">Registre a entrada de produtos no estoque.</p>
        </div>
        <button @click="$emit('fechar')" class="text-slate-400 hover:text-white">
          <X :size="20" />
        </button>
      </div>

      <!-- Info do produto -->
      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 mb-6">
        <p class="text-slate-400 text-xs mb-1">Produto</p>
        <p class="text-white font-bold text-base">{{ item.nome }}</p>
        <p class="text-slate-400 text-xs mt-2">Estoque disponível</p>
        <p class="text-white font-bold text-xl">{{ item.quantidade }} {{ item.unidade_medida }}</p>
      </div>

      <form @submit.prevent="abrirConfirmacao">

        <div class="mb-4">
          <label class="label">Quantidade para entrada *</label>
          <input
            v-model.number="form.quantidade"
            type="number"
            min="1"
            required
            class="campo"
            placeholder="Ex: 10"
          />
        </div>

        <div class="mb-6">
          <label class="label">Motivo (opcional)</label>
          <textarea
            v-model="form.motivo"
            rows="3"
            class="campo"
            placeholder="Ex: Compra, Retorno, Ajuste..."
          />
        </div>

        <div v-if="erro" class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded text-red-400 text-sm">
          {{ erro }}
        </div>

        <div class="flex justify-end gap-3">
          <button type="button" @click="$emit('fechar')"
            class="px-4 py-2 border border-slate-600 text-slate-300 rounded-lg hover:bg-slate-800 transition">
            Cancelar
          </button>
          <button type="submit"
            :disabled="!form.quantidade || form.quantidade < 1"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed transition font-medium">
            Confirmar Entrada
          </button>
        </div>

      </form>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { X, Shield, Lock } from 'lucide-vue-next'
import api from '@/servicos/api'

const props = defineProps({
  item: { type: Object, required: true },
})
const emit = defineEmits(['fechar', 'salvo'])

const etapa       = ref(0)
const pinDigitado = ref('')
const erroPin     = ref('')
const tentativas  = ref(0)
const salvando    = ref(false)
const erro        = ref('')

const PIN_CORRETO = '8401'

const form = ref({
  quantidade: null,
  motivo:     '',
})

function abrirConfirmacao() {
  erro.value        = ''
  pinDigitado.value = ''
  erroPin.value     = ''
  tentativas.value  = 0
  etapa.value       = 1
}

function fecharSeFora() {
  if (etapa.value === 0) emit('fechar')
}

async function verificarPin() {
  if (pinDigitado.value !== PIN_CORRETO) {
    tentativas.value++
    erroPin.value     = tentativas.value >= 3
      ? 'PIN incorreto. Acesso bloqueado. Contate o administrador.'
      : 'PIN incorreto. Tente novamente.'
    pinDigitado.value = ''
    return
  }

  erroPin.value  = ''
  salvando.value = true
  try {
    await api.patch(`/itens/${props.item.id_item}/entrada`, {
      quantidade: form.value.quantidade,
      motivo:     form.value.motivo,
    })
    emit('salvo')
  } catch (e) {
    erro.value        = e.response?.data?.message || 'Erro ao registrar entrada.'
    pinDigitado.value = ''
    etapa.value       = 0
  } finally {
    salvando.value = false
  }
}
</script>

<style scoped>
.label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #cbd5e1;
  margin-bottom: 0.25rem;
}
.campo {
  width: 100%;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  color: #f1f5f9;
  outline: none;
  resize: none;
}
.campo:focus {
  border-color: #22c55e;
  box-shadow: 0 0 0 2px rgba(34,197,94,0.2);
}
</style>