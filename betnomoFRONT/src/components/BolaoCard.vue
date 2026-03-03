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
  sorteado: boolean
  vencedor: string | null  // nome do vencedor vindo do backend
  status: 'aberto' | 'fechado'
  acao: 'participar' | 'sortear' | null
}

const props = defineProps<{ bolao: Bolao }>()
const emit  = defineEmits<{ (e: 'atualizar'): void }>()

const API   = import.meta.env.VITE_API_URL ?? '/api'
const token = () => localStorage.getItem('auth_token') ?? ''

const carregando = ref(false)
const erro       = ref('')
const sucesso    = ref('')

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
      erro.value = `Você não tem ficha Classe ${props.bolao.classe} disponível.`
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
    erro.value = 'Erro de conexão. Tente novamente.'
  } finally {
    carregando.value = false
  }
}

async function sortear() {
  if (!confirm('Confirmar sorteio deste bolão?')) return

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
    erro.value = 'Erro de conexão. Tente novamente.'
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

      <!-- Badge muda: sorteado quando encerrado, aberto/fechado quando ativo -->
      <span v-if="bolao.sorteado" class="bolao-status" style="background: rgba(61,214,140,0.12); color: #3dd68c;">
        sorteado
      </span>
      <span v-else :class="['bolao-status', bolao.status]">
        {{ bolao.status === 'aberto' ? 'Aberto' : 'Fechado' }}
      </span>
    </div>

    <div class="bolao-card-body">

      <!-- ── Exibição pós sorteio ──────────────────────────────────────────── -->
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

      <!-- ── Exibição normal ───────────────────────────────────────────────── -->
      <template v-else>
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

        <!-- Feedback -->
        <p v-if="erro"    style="font-size: 0.7rem; color: #e05252; text-align: center; margin-bottom: 4px;">{{ erro }}</p>
        <p v-if="sucesso" style="font-size: 0.7rem; color: #3dd68c; text-align: center; margin-bottom: 4px;">{{ sucesso }}</p>

        <!-- Ação: participar — usuário comum -->
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
            ✓ Participando
          </button>
        </template>

        <!-- Ação: sortear — admin -->
        <template v-else-if="bolao.acao === 'sortear'">
          <button
            class="bolao-btn"
            :disabled="carregando"
            @click="sortear"
          >
            <span v-if="carregando">Sorteando...</span>
            <span v-else>Sortear</span>
          </button>
        </template>

      </template>
    </div>
  </div>
</template>