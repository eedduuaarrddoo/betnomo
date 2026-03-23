<script setup lang="ts">
import { ref } from 'vue'

const API   = import.meta.env.VITE_API_URL ?? '/api'
const token = () => localStorage.getItem('auth_token') ?? ''

const emit = defineEmits<{
  (e: 'fechar'): void
  (e: 'criado'): void
}>()

const criando    = ref(false)
const criarError = ref('')

const novobolao = ref({
  classe:            'C',
  hora_abertura:     '',
  hora_sorteio:      '',
  max_participantes: 20,
})

async function criarBolao() {
  criando.value    = true
  criarError.value = ''

  try {
    const res = await fetch(`${API}/admin/boloes`, {
      method:  'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept':        'application/json',          // ← garante resposta JSON, não HTML
        Authorization:  `Bearer ${token()}`,
      },
      body: JSON.stringify(novobolao.value),
    })

    // Se não for JSON, captura o texto cru para debug
    const contentType = res.headers.get('content-type') ?? ''
    if (!contentType.includes('application/json')) {
      const raw = await res.text()
      throw new Error(`Servidor retornou ${res.status} (não-JSON). Verifique se a rota POST /admin/boloes existe e se o token é válido.\n\nPrimeiros 200 chars:\n${raw.slice(0, 200)}`)
    }

    const data = await res.json()
    if (!res.ok) throw new Error(data.message ?? data.error ?? 'Erro ao criar bolao')

    novobolao.value = { classe: 'C', hora_abertura: '', hora_sorteio: '', max_participantes: 20 }
    emit('criado')
  } catch (e: any) {
    criarError.value = e.message
  } finally {
    criando.value = false
  }
}
</script>

<template>
  <Transition name="modal-fade">
    <div class="cb-overlay" @click.self="emit('fechar')">
      <div class="cb-modal">
        <button class="cb-close" @click="emit('fechar')">✕</button>
        <h2 class="cb-title"><span>Criar</span> Bolão</h2>

        <div class="cb-form">
          <div class="cb-field">
            <label class="cb-label">Classe</label>
            <select v-model="novobolao.classe" class="cb-select">
              <option value="A">Classe A — R$ 50</option>
              <option value="B">Classe B — R$ 25</option>
              <option value="C">Classe C — R$ 5</option>
            </select>
          </div>

          <div class="cb-row">
            <div class="cb-field">
              <label class="cb-label">Abertura</label>
              <!--
                CORREÇÃO: era type="time" → agora type="datetime-local"
                O backend valida como 'required|date' e espera "2026-03-10T14:30"
              -->
              <input
                v-model="novobolao.hora_abertura"
                type="datetime-local"
                class="cb-input"
              />
            </div>
            <div class="cb-field">
              <label class="cb-label">Sorteio</label>
              <input
                v-model="novobolao.hora_sorteio"
                type="datetime-local"
                class="cb-input"
              />
            </div>
          </div>

          <div class="cb-field">
            <label class="cb-label">Máx. Participantes</label>
            <input
              v-model.number="novobolao.max_participantes"
              type="number"
              min="2"
              max="100"
              class="cb-input"
            />
          </div>

          <p v-if="criarError" class="cb-error" style="white-space: pre-wrap;">{{ criarError }}</p>

          <button class="cb-btn" :disabled="criando" @click="criarBolao">
            <span v-if="criando" class="cb-spinner" />
            <span>{{ criando ? 'Criando…' : 'Criar Bolão' }}</span>
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>