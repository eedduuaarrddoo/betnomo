<script setup lang="ts">
import { ref } from 'vue'

export interface Bolao {
  id: number
  classe: string
  hora_abertura: string
  hora_sorteio: string
  participantes: number
  max_participantes: number
  valor_total: number
  status: 'aberto' | 'fechado'
}

const props = defineProps<{ bolao: Bolao }>()
const emit  = defineEmits<{ (e: 'participou'): void }>()

const API   = import.meta.env.VITE_API_URL ?? '/api'
const token = () => localStorage.getItem('auth_token') ?? ''

const participando = ref(false)
const erro         = ref('')
const sucesso      = ref(false)

async function participar() {
  participando.value = true
  erro.value         = ''
  sucesso.value      = false

  try {
    // 1. Busca fichas do usuário e pega a primeira da classe correta
    const resFichas = await fetch(`${API}/fichas`, {
      headers: { Authorization: `Bearer ${token()}` },
    })
    const dataFichas = await resFichas.json()

    const fichaDisponivel = (dataFichas.fichas as any[]).find(
      f => f.tipo === props.bolao.classe && !f.usada
    )

    if (!fichaDisponivel) {
      erro.value = `Você não tem ficha Classe ${props.bolao.classe} disponível.`
      return
    }

    // 2. Entra no bolão com o token da ficha
    const res  = await fetch(`${API}/boloes/${props.bolao.id}/participar`, {
      method:  'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token()}`,
      },
      body: JSON.stringify({ token: fichaDisponivel.token }),
    })

    const data = await res.json()

    if (!res.ok) {
      erro.value = data.error ?? 'Erro ao participar.'
      return
    }

    sucesso.value = true
    emit('participou')

  } catch (e) {
    erro.value = 'Erro de conexão. Tente novamente.'
  } finally {
    participando.value = false
  }
}

function getClassTag(classe: string) {
  if (classe === 'A') return 'gold'
  if (classe === 'B') return 'silver'
  return ''
}

function getClassLabel(classe: string) {
  const map: Record<string, string> = { A: 'Classe A', B: 'Classe B', C: 'Classe C' }
  return map[classe] ?? classe
}

function progressPercent(b: Bolao) {
  return Math.round((b.participantes / b.max_participantes) * 100)
}
</script>

<template>
  <div class="bolao-card">
    <div class="bolao-card-header">
      <span :class="['bolao-class-tag', getClassTag(bolao.classe)]">
        {{ getClassLabel(bolao.classe) }}
      </span>
      <span :class="['bolao-status', bolao.status]">
        {{ bolao.status === 'aberto' ? 'Aberto' : 'Fechado' }}
      </span>
    </div>

    <div class="bolao-card-body">
      <div class="bolao-info-row">
        <span>Abertura</span>
        <span>{{ bolao.hora_abertura }}</span>
      </div>
      <div class="bolao-info-row">
        <span>Participantes</span>
        <span>{{ bolao.participantes }}/{{ bolao.max_participantes }}</span>
      </div>
      <div class="bolao-info-row">
        <span>Prêmio</span>
        <span style="color: #f0a500;">{{ bolao.valor_total }} fichas</span>
      </div>

      <div class="bolao-progress-bar">
        <div
          class="bolao-progress-fill"
          :style="{ width: progressPercent(bolao) + '%' }"
        />
      </div>

      <div class="bolao-sorteio-time">
        <span style="font-size: 0.6rem; color: #6b7b8a; font-family: 'Exo 2', sans-serif;
                     font-weight: 500; letter-spacing: 0.05em; text-transform: uppercase;">
          Sorteio
        </span>
        {{ bolao.hora_sorteio }}
      </div>

      <!-- Mensagem de erro -->
      <p v-if="erro" style="font-size: 0.7rem; color: #e05252; text-align: center; margin-bottom: 4px;">
        {{ erro }}
      </p>

      <!-- Slot para o admin substituir o botão (ex: Sortear) -->
      <slot name="acao">
        <!-- Botão padrão para o usuário comum -->
        <button
          v-if="!sucesso"
          class="bolao-btn"
          :disabled="bolao.status === 'fechado' || participando"
          @click="participar"
        >
          <span v-if="participando">Entrando…</span>
          <span v-else>{{ bolao.status === 'aberto' ? 'Participar' : 'Encerrado' }}</span>
        </button>

        <button v-else class="bolao-btn" disabled style="background: #3dd68c; color: #0d1117;">
          ✓ Participando
        </button>
      </slot>
    </div>
  </div>
</template>