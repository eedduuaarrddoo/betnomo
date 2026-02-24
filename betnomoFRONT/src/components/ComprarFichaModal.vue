<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import '../assets/css/ComprarFichaModal.css'

// ── Props / Emits ─────────────────────────────────────────────────────────────
const props = defineProps<{ open: boolean }>()
const emit  = defineEmits<{ (e: 'close'): void; (e: 'fichaCreated'): void }>()

// ── State ─────────────────────────────────────────────────────────────────────
type Tipo = 'A' | 'B' | 'C'

const tipoSelecionado = ref<Tipo | null>(null)
const step            = ref<'selecionar' | 'qr' | 'sucesso'>('selecionar')
const qrBase64        = ref('')
const pixPayload      = ref('')
const referencia      = ref('')
const loadingQr       = ref(false)
const confirming      = ref(false)
const copiado         = ref(false)
const error           = ref('')

const tipos: { id: Tipo; label: string; valor: number; desc: string }[] = [
  { id: 'A', label: 'Ficha Classe A', valor: 50, desc: 'Participe dos melhores bolões' },
  { id: 'B', label: 'Ficha Classe B', valor: 25, desc: 'Acesso aos bolões intermediários' },
  { id: 'C', label: 'Ficha Classe C', valor:  5, desc: 'Comece com o básico' },
]

const tipoAtual = computed(() => tipos.find(t => t.id === tipoSelecionado.value))

// Reseta ao fechar
watch(() => props.open, (val) => {
  if (!val) {
    setTimeout(() => {
      step.value            = 'selecionar'
      tipoSelecionado.value = null
      qrBase64.value        = ''
      pixPayload.value      = ''
      referencia.value      = ''
      error.value           = ''
      copiado.value         = false
    }, 300)
  }
})

// ── API ───────────────────────────────────────────────────────────────────────
const API   = import.meta.env.VITE_API_URL ?? '/api'
const token = () => localStorage.getItem('auth_token') ?? ''

async function avancarParaQr() {
  if (!tipoSelecionado.value) return
  loadingQr.value = true
  error.value     = ''

  try {
    const res  = await fetch(`${API}/fichas/gerar-qr`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token()}` },
      body:    JSON.stringify({ tipo: tipoSelecionado.value }),
    })
    const data = await res.json()
    if (!res.ok) throw new Error(data.message ?? 'Erro ao gerar QR code')

    qrBase64.value   = data.qr_base64
    pixPayload.value = data.pix_payload
    referencia.value = data.referencia
    step.value       = 'qr'
  } catch (e: any) {
    error.value = e.message
  } finally {
    loadingQr.value = false
  }
}

async function confirmarCompra() {
  confirming.value = true
  error.value      = ''

  try {
    const res  = await fetch(`${API}/fichas/confirmar`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token()}` },
      body:    JSON.stringify({ tipo: tipoSelecionado.value, referencia: referencia.value }),
    })
    const data = await res.json()
    if (!res.ok) throw new Error(data.message ?? 'Erro ao confirmar compra')

    step.value = 'sucesso'
    emit('fichaCreated')
  } catch (e: any) {
    error.value = e.message
  } finally {
    confirming.value = false
  }
}

async function copiarPix() {
  await navigator.clipboard.writeText(pixPayload.value).catch(() => {})
  copiado.value = true
  setTimeout(() => (copiado.value = false), 2000)
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="open" class="fm-overlay" @click.self="emit('close')">
        <div class="fm-modal">

          <button class="fm-close" @click="emit('close')">×</button>

          <h2 class="fm-title">
            <span class="fm-gold">Comprar</span> Fichas
          </h2>
          <p class="fm-subtitle">Selecione o tipo de ficha e pague via PIX</p>

          <!-- STEP 1 — Selecionar tipo -->
          <div v-if="step === 'selecionar'">
            <div class="fm-tipos">
              <button
                v-for="t in tipos"
                :key="t.id"
                :class="['fm-tipo-btn', tipoSelecionado === t.id ? 'ativo' : '']"
                @click="tipoSelecionado = t.id"
              >
                <div class="fm-badge" :data-tipo="t.id">{{ t.id }}</div>
                <div class="fm-tipo-info">
                  <p class="fm-tipo-nome">{{ t.label }}</p>
                  <p class="fm-tipo-desc">{{ t.desc }}</p>
                </div>
                <div class="fm-tipo-valor">R$ {{ t.valor }}</div>
              </button>
            </div>

            <p v-if="error" class="fm-error">{{ error }}</p>

            <button
              class="fm-btn-primary"
              :disabled="!tipoSelecionado || loadingQr"
              @click="avancarParaQr"
            >
              <span v-if="loadingQr" class="fm-spinner" />
              <span v-if="loadingQr">Gerando QR code…</span>
              <span v-else>Pagar com PIX</span>
            </button>
          </div>

          <!-- STEP 2 — QR Code -->
          <div v-else-if="step === 'qr'" class="fm-qr-step">
            <div class="fm-qr-header">
              <button class="fm-back" @click="step = 'selecionar'">← Voltar</button>
              <div class="fm-qr-valor">
                <span class="fm-badge sm" :data-tipo="tipoSelecionado">{{ tipoSelecionado }}</span>
                R$ {{ tipoAtual?.valor }}
              </div>
            </div>

            <p class="fm-instrucao">Escaneie o QR code no seu banco ou copie o código PIX.</p>

            <div class="fm-qr-box">
              <img v-if="qrBase64" :src="qrBase64" alt="QR Code PIX" class="fm-qr-img" />
              <div v-else class="fm-qr-placeholder">
                <span class="fm-spinner lg" />
              </div>
            </div>

            <button class="fm-btn-copiar" @click="copiarPix">
              {{ copiado ? '✓ Copiado!' : '📋 Copiar código PIX' }}
            </button>

            <p v-if="error" class="fm-error">{{ error }}</p>

            <button class="fm-btn-confirmar" :disabled="confirming" @click="confirmarCompra">
              <span v-if="confirming" class="fm-spinner dark" />
              <span v-if="confirming">Confirmando…</span>
              <span v-else>✓ Já paguei — criar minha ficha</span>
            </button>

            <p class="fm-aviso">⚠️ Clique somente após concluir o pagamento PIX.</p>
          </div>

          <!-- STEP 3 — Sucesso -->
          <div v-else-if="step === 'sucesso'" class="fm-sucesso">
            <p class="fm-sucesso-icon">🎉</p>
            <p class="fm-sucesso-titulo">Ficha criada!</p>
            <p class="fm-sucesso-desc">
              Sua ficha <strong>Classe {{ tipoSelecionado }}</strong> está pronta para uso.
            </p>
            <button class="fm-btn-primary" @click="emit('close')">Fechar</button>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>