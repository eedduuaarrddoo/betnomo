<script setup lang="ts">
import { ref, computed } from 'vue'

export interface Bolao {
  id: number
  classe: string
  hora_abertura: string
  hora_sorteio: string
  participantes: number
  participantes_nomes: string[]
  max_participantes: number
  fichas_count: number
  valor_total: number
  sorteado: boolean
  vencedor: string | null
  status: 'aberto' | 'fechado'
  acao: 'participar' | 'sortear' | null
}

const props = defineProps<{ bolao: Bolao }>()
const emit  = defineEmits<{ (e: 'atualizar'): void }>()

const API   = import.meta.env.VITE_API_URL ?? '/api'
const token = () => localStorage.getItem('auth_token') ?? ''

const carregando        = ref(false)
const erro              = ref('')
const sucesso           = ref('')
const mostrarJogadores  = ref(false)

// Moedas: array de MAX 20 slots, true = ficha inserida, false = vazio
const MAX_MOEDAS = 20
const moedas = computed(() => {
  const slots = []
  for (let i = 0; i < MAX_MOEDAS; i++) {
    slots.push(i < props.bolao.fichas_count)
  }
  return slots
})

async function participar() {
  carregando.value = true
  erro.value       = ''
  sucesso.value    = ''

  try {
    const resFichas  = await fetch(`${API}/fichas`, {
      headers: { Authorization: `Bearer ${token()}` },
    })
    const dataFichas = await resFichas.json()

    const fichaDisponivel = (dataFichas.fichas as any[]).find(
      f => f.tipo === props.bolao.classe && !f.usada
    )

    if (!fichaDisponivel) {
      erro.value = `Voce nao tem ficha Classe ${props.bolao.classe} disponivel.`
      return
    }

    const res  = await fetch(`${API}/boloes/${props.bolao.id}/participar`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token()}` },
      body:    JSON.stringify({ token: fichaDisponivel.token }),
    })
    const data = await res.json()

    if (!res.ok) {
      erro.value = data.error ?? 'Erro ao participar.'
      return
    }

    sucesso.value = 'Participando!'
    emit('atualizar')

  } catch {
    erro.value = 'Erro de conexao. Tente novamente.'
  } finally {
    carregando.value = false
  }
}

async function sortear() {
  if (!confirm('Confirmar sorteio deste bolao?')) return

  carregando.value = true
  erro.value       = ''
  sucesso.value    = ''

  try {
    const res  = await fetch(`${API}/admin/boloes/${props.bolao.id}/sortear`, {
      method:  'POST',
      headers: { Authorization: `Bearer ${token()}` },
    })
    const data = await res.json()

    if (!res.ok) {
      erro.value = data.error ?? 'Erro ao sortear.'
      return
    }

    emit('atualizar')

  } catch {
    erro.value = 'Erro de conexao. Tente novamente.'
  } finally {
    carregando.value = false
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
      <span v-if="bolao.sorteado" class="bolao-status" style="background: rgba(61,214,140,0.12); color: #3dd68c;">
        sorteado
      </span>
      <span v-else :class="['bolao-status', bolao.status]">
        {{ bolao.status === 'aberto' ? 'Aberto' : 'Fechado' }}
      </span>
    </div>

    <div class="bolao-card-body">

      <!-- ── Pos sorteio ──────────────────────────────────────────────────── -->
      <template v-if="bolao.sorteado">
        <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 16px 0;">
          <div style="text-align: center;">
            <p style="font-size: 0.65rem; color: #6b7b8a; text-transform: uppercase;
                      letter-spacing: 0.1em; margin-bottom: 8px; font-family: 'Exo 2', sans-serif;">
              Vencedor
            </p>
            <p style="font-family: 'Cinzel', serif; font-size: 1rem; font-weight: 700;
                      color: #3dd68c; text-decoration: underline;">
              {{ bolao.vencedor ?? 'Desconhecido' }}
            </p>
          </div>
        </div>
      </template>

      <!-- ── Exibicao normal ───────────────────────────────────────────────── -->
      <template v-else>

        <!-- Datas -->
        <div class="bolao-info-row">
          <span>Abertura</span>
          <span>{{ bolao.hora_abertura }}</span>
        </div>

        <!-- Participantes com toggle -->
        <div
          class="bolao-info-row"
          style="cursor: pointer; user-select: none;"
          @click="mostrarJogadores = !mostrarJogadores"
        >
          <span>
            Jogadores
            <span style="font-size: 0.6rem; color: #3d4d5a; margin-left: 4px;">
              {{ mostrarJogadores ? '▲' : '▼' }}
            </span>
          </span>
          <span>{{ bolao.participantes }}/{{ bolao.max_participantes }}</span>
        </div>

        <!-- Lista de jogadores -->
        <div
          v-if="mostrarJogadores && bolao.participantes_nomes.length > 0"
          style="background: rgba(13,17,23,0.5); border-radius: 6px; padding: 6px 8px;
                 margin-bottom: 4px; max-height: 80px; overflow-y: auto;"
        >
          <p
            v-for="nome in bolao.participantes_nomes"
            :key="nome"
            style="font-size: 0.68rem; color: #8a9baa; padding: 2px 0;
                   border-bottom: 1px solid rgba(255,255,255,0.04); font-family: 'Exo 2', sans-serif;"
          >
            {{ nome }}
          </p>
        </div>
        <div
          v-else-if="mostrarJogadores"
          style="font-size: 0.68rem; color: #3d4d5a; text-align: center; margin-bottom: 4px;"
        >
          Nenhum jogador ainda
        </div>

        <!-- Mostrador de moedas (fichas inseridas) -->
        <div class="bolao-info-row" style="align-items: flex-start;">
          <span>Premio</span>
          <div style="display: flex; flex-wrap: wrap; gap: 3px; justify-content: flex-end; max-width: 140px;">
            <span
              v-for="(ativa, i) in moedas"
              :key="i"
              :title="ativa ? 'Ficha inserida' : 'Vaga'"
              style="font-size: 0.78rem; line-height: 1; transition: opacity 0.2s;"
              :style="{ opacity: ativa ? '1' : '0.15' }"
            >
              🪙
            </span>
          </div>
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

        <!-- Feedback -->
        <p v-if="erro"    style="font-size: 0.7rem; color: #e05252; text-align: center; margin-bottom: 4px;">{{ erro }}</p>
        <p v-if="sucesso" style="font-size: 0.7rem; color: #3dd68c; text-align: center; margin-bottom: 4px;">{{ sucesso }}</p>

        <!-- Participar -->
        <template v-if="bolao.acao === 'participar'">
          <button
            v-if="!sucesso"
            class="bolao-btn"
            :disabled="bolao.status === 'fechado' || carregando"
            @click="participar"
          >
            <span v-if="carregando">Entrando...</span>
            <span v-else>{{ bolao.status === 'aberto' ? 'Participar' : 'Encerrado' }}</span>
          </button>
          <button v-else class="bolao-btn" disabled style="background: #3dd68c; color: #0d1117;">
            Participando
          </button>
        </template>

        <!-- Sortear -->
        <template v-else-if="bolao.acao === 'sortear'">
          <button class="bolao-btn" :disabled="carregando" @click="sortear">
            <span v-if="carregando">Sorteando...</span>
            <span v-else>Sortear</span>
          </button>
        </template>

      </template>
    </div>
  </div>
</template>