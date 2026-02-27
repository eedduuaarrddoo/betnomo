<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '../stores/auth'
import '../assets/css/userhome.css'
import ComprarFichaModal from '../components/ComprarFichaModal.vue'

const auth = useAuthStore()

const API   = import.meta.env.VITE_API_URL ?? '/api'
const token = () => localStorage.getItem('auth_token') ?? ''

// ── Categorias / filtro ───────────────────────────────────────────────────────
const categories = [
  { id: 'classe-a', label: 'Bolão Classe A', emoji: '🥇', classe: 'A' },
  { id: 'classe-b', label: 'Bolão Classe B', emoji: '🥈', classe: 'B' },
  { id: 'classe-c', label: 'Bolão Classe C', emoji: '🥉', classe: 'C' },
]

const activeCategory = ref('classe-a')

const classeAtiva = computed(
  () => categories.find(c => c.id === activeCategory.value)?.classe ?? 'A'
)

// ── Bolões ────────────────────────────────────────────────────────────────────
interface Bolao {
  id: number
  classe: string
  hora_abertura: string
  hora_sorteio: string
  participantes: number
  max_participantes: number
  valor_total: number
  status: 'aberto' | 'fechado'
}

const boloes        = ref<Bolao[]>([])
const loadingBoloes = ref(false)

async function carregarBoloes() {
  loadingBoloes.value = true
  try {
    const res  = await fetch(`${API}/boloes?classe=${classeAtiva.value}`, {
      headers: { Authorization: `Bearer ${token()}` },
    })
    boloes.value = await res.json()
  } catch (e) {
    console.error('Erro ao carregar bolões:', e)
  } finally {
    loadingBoloes.value = false
  }
}

// Recarrega toda vez que o usuário troca de categoria
watch(activeCategory, () => carregarBoloes())

// ── Fichas ────────────────────────────────────────────────────────────────────
interface ResumoFichas { A: number; B: number; C: number }

const fichasResumo  = ref<ResumoFichas>({ A: 0, B: 0, C: 0 })
const fichasTotal   = ref(0)
const loadingFichas = ref(false)

async function carregarFichas() {
  loadingFichas.value = true
  try {
    const res  = await fetch(`${API}/fichas`, {
      headers: { Authorization: `Bearer ${token()}` },
    })
    const data = await res.json()
    fichasResumo.value = data.resumo
    fichasTotal.value  = data.total
  } catch (e) {
    console.error('Erro ao carregar fichas:', e)
  } finally {
    loadingFichas.value = false
  }
}

// ── Modal compra ──────────────────────────────────────────────────────────────
const showComprarModal = ref(false)

function onFichaCreated() {
  carregarFichas()
}

// ── Helpers de exibição ───────────────────────────────────────────────────────
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

// contagem por categoria para o badge da sidebar
const countPorClasse = computed(() => {
  const map: Record<string, number> = { A: 0, B: 0, C: 0 }
  boloes.value.forEach(b => { map[b.classe] = (map[b.classe] ?? 0) + 1 })
  return map
})

const userInitial = computed(() =>
  auth.user?.username?.charAt(0).toUpperCase() || '?'
)

onMounted(() => {
  carregarFichas()
  carregarBoloes()
})
</script>

<template>
  <div class="dashboard-layout">

    <aside class="sidebar">
      <p class="sidebar-section-title">Categorias</p>

      <div style="padding: 0 4px;">
        <button
          v-for="cat in categories"
          :key="cat.id"
          :class="['sidebar-category-btn', activeCategory === cat.id ? 'active' : '']"
          @click="activeCategory = cat.id"
        >
          <span>{{ cat.emoji }}</span>
          <span>{{ cat.label }}</span>
          <span class="category-badge">{{ countPorClasse[cat.classe] ?? 0 }}</span>
        </button>
      </div>

      <div style="flex: 1" />

      <div class="player-card">

        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
          <div class="player-avatar">{{ userInitial }}</div>
          <div>
            <p style="font-family: 'Exo 2', sans-serif; font-size: 0.8rem; color: #c8d3da; font-weight: 600;">
              {{ auth.user?.username || 'Jogador' }}
            </p>
            <p style="font-size: 0.68rem; color: #3d4d5a;">Jogador</p>
          </div>
        </div>

        <div class="player-fichas">
          <div class="fichas-icon">F</div>
          <div>
            <p style="font-size: 0.65rem; color: #6b7b8a; line-height: 1;">Fichas</p>
            <p style="font-family: 'Cinzel', serif; font-size: 1rem; font-weight: 700; color: #f0a500; line-height: 1.3;">
              <span v-if="loadingFichas">…</span>
              <span v-else>{{ fichasTotal }}</span>
            </p>
          </div>
          <button
            style="margin-left: auto; font-size: 0.68rem; color: #3dd68c; font-weight: 600;
                   background: none; border: none; cursor: pointer; padding: 0;"
            @click="showComprarModal = true"
          >
            + Comprar
          </button>
        </div>

        <div
          v-if="!loadingFichas && fichasTotal > 0"
          style="display: flex; gap: 6px; margin-top: 8px;"
        >
          <div
            v-for="tipo in (['A', 'B', 'C'] as const)"
            :key="tipo"
            style="flex: 1; background: rgba(13,17,23,0.6); border-radius: 6px;
                   padding: 5px 4px; text-align: center;"
          >
            <p style="font-size: 0.58rem; color: #6b7b8a; margin-bottom: 2px; text-transform: uppercase;">
              Classe {{ tipo }}
            </p>
            <p
              style="font-family: 'Cinzel', serif; font-size: 0.88rem; font-weight: 700; line-height: 1;"
              :style="{ color: tipo === 'A' ? '#f0d060' : tipo === 'B' ? '#b0bec5' : '#c87941' }"
            >
              {{ fichasResumo[tipo] }}
            </p>
          </div>
        </div>

        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(30,36,40,0.8);">
          <p style="font-size: 0.68rem; color: #6b7b8a; margin-bottom: 4px;">Bolões ativos</p>
          <p style="font-family: 'Cinzel', serif; font-size: 1.1rem; font-weight: 700; color: #c8d3da;">0</p>
        </div>
      </div>
    </aside>

    <main class="main-content">

      <div class="banner-area">
        <div class="banner-main">
          <img
            src="../assets/images/bannerimg.png"
            alt="Banner"
            class="banner-main-img"
            onerror="this.style.display='none'"
          />
          <div class="banner-main-overlay">
            <p style="font-size: 0.7rem; color: #3dd68c; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 4px;">
              Bolões Abertos
            </p>
            <p style="font-family: 'Cinzel', serif; font-size: 1.4rem; font-weight: 700; color: white; line-height: 1.2;">
              Participe agora<br/>
              <span style="color: #f0a500;">e ganhe fichas!</span>
            </p>
          </div>
        </div>

        <div class="banner-side">
          <div class="banner-side-card">
            <div style="text-align: center;">
              <p style="font-size: 1.5rem; margin-bottom: 4px;">🎁</p>
              <p style="font-family: 'Cinzel', serif; font-size: 0.8rem; font-weight: 700; color: #f0a500;">1 Ficha Grátis</p>
              <p style="font-size: 0.68rem; color: #6b7b8a; margin-top: 2px;">ao criar conta</p>
            </div>
          </div>
          <div class="banner-side-card">
            <div style="text-align: center;">
              <p style="font-size: 1.5rem; margin-bottom: 4px;">🏆</p>
              <p style="font-family: 'Cinzel', serif; font-size: 0.8rem; font-weight: 700; color: #3dd68c;">Sempre 1 ganha</p>
              <p style="font-size: 0.68rem; color: #6b7b8a; margin-top: 2px;">em cada rodada</p>
            </div>
          </div>
        </div>
      </div>

      <div class="section-header">
        <span class="section-label">
          {{ categories.find(c => c.id === activeCategory)?.label }}
        </span>
        <span style="font-size: 0.75rem; color: #3d4d5a;">
          <span v-if="loadingBoloes">Carregando…</span>
          <span v-else>{{ boloes.length }} bolões disponíveis</span>
        </span>
      </div>

      <div class="boloes-grid">

        <!-- Loading skeleton -->
        <div v-if="loadingBoloes" v-for="n in 3" :key="n" class="bolao-card" style="opacity: 0.4; pointer-events: none;">
          <div class="bolao-card-header">
            <span class="bolao-class-tag">…</span>
            <span class="bolao-status aberto">…</span>
          </div>
          <div class="bolao-card-body">
            <div class="bolao-info-row"><span>Abertura</span><span>--:--</span></div>
            <div class="bolao-info-row"><span>Participantes</span><span>-/-</span></div>
            <div class="bolao-info-row"><span>Prêmio</span><span>-</span></div>
            <div class="bolao-progress-bar"><div class="bolao-progress-fill" style="width: 0%" /></div>
            <div class="bolao-sorteio-time">--:--</div>
            <button class="bolao-btn" disabled>…</button>
          </div>
        </div>

        <!-- Bolões reais vindos da API -->
        <div
          v-if="!loadingBoloes"
          v-for="bolao in boloes"
          :key="bolao.id"
          class="bolao-card"
        >
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
              <span style="font-size: 0.6rem; color: #6b7b8a; font-family: 'Exo 2', sans-serif; font-weight: 500; letter-spacing: 0.05em; text-transform: uppercase;">Sorteio</span>
              {{ bolao.hora_sorteio }}
            </div>

            <button
              class="bolao-btn"
              :disabled="bolao.status === 'fechado'"
            >
              {{ bolao.status === 'aberto' ? 'Participar' : 'Encerrado' }}
            </button>
          </div>
        </div>

        <div v-if="!loadingBoloes && boloes.length === 0" class="empty-state">
          <p style="font-size: 2rem; margin-bottom: 8px;">🎲</p>
          <p style="font-size: 0.9rem;">Nenhum bolão disponível nesta categoria</p>
        </div>
      </div>

    </main>

    <ComprarFichaModal
      :open="showComprarModal"
      @close="showComprarModal = false"
      @fichaCreated="onFichaCreated"
    />

  </div>
</template>