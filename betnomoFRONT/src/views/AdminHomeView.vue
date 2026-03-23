<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import BolaoCard, { type Bolao } from '../components/BolaoCard.vue'
import CriarBolaoModal from '../components/CriarBolaomodal.vue'   
import '../assets/css/AdminHomeView.css'
import '../assets/css/userhome.css'

const auth   = useAuthStore()
const router = useRouter()

const API   = import.meta.env.VITE_API_URL ?? '/api'
const token = () => localStorage.getItem('auth_token') ?? ''

const activeNav    = ref('boloes')
const filtroClasse = ref<'TODOS' | 'A' | 'B' | 'C'>('TODOS')

const boloes        = ref<Bolao[]>([])
const loadingBoloes = ref(false)

const boloesFiltrados = computed(() => {
  if (filtroClasse.value === 'TODOS') return boloes.value
  return boloes.value.filter(b => b.classe === filtroClasse.value)
})

async function carregarBoloes() {
  loadingBoloes.value = true
  try {
    const res = await fetch(`${API}/boloes`, {
      headers: {
        Authorization: `Bearer ${token()}`,
        Accept: 'application/json',
      },
    })
    boloes.value = await res.json()
  } catch (e) {
    console.error('Erro ao carregar boloes:', e)
  } finally {
    loadingBoloes.value = false
  }
}

const stats = computed(() => ({
  total:    boloes.value.length,
  abertos:  boloes.value.filter(b => b.status === 'aberto').length,
  fechados: boloes.value.filter(b => b.status === 'fechado').length,
  fichas:   boloes.value.reduce((acc, b) => acc + b.valor_total, 0),
}))

// ── Modal ─────────────────────────────────────────────────────────────────────
const showCriarModal = ref(false)

function onBolaoCreated() {
  showCriarModal.value = false
  carregarBoloes()
}

// ── Auth ──────────────────────────────────────────────────────────────────────
const userInitial = computed(() =>
  auth.user?.username?.charAt(0).toUpperCase() || 'A'
)

function logout() {
  auth.logout()
  router.push('/')
}

onMounted(() => carregarBoloes())
</script>

<template>
  <div class="admin-layout">

    <aside class="admin-sidebar">
      <div class="admin-logo"><span>Adm </span>Juvio</div>

      <p class="admin-section-title">Menu</p>

      <button :class="['admin-nav-btn', activeNav === 'boloes' ? 'active' : '']" @click="activeNav = 'boloes'">
        <span class="nav-icon">🎲</span>
        Boloes
        <span class="admin-nav-badge">{{ stats.total }}</span>
      </button>

      <button :class="['admin-nav-btn', activeNav === 'usuarios' ? 'active' : '']" @click="activeNav = 'usuarios'">
        <span class="nav-icon">👥</span>
        Usuarios
      </button>

      <button :class="['admin-nav-btn', activeNav === 'fichas' ? 'active' : '']" @click="activeNav = 'fichas'">
        <span class="nav-icon">🪙</span>
        Fichas
      </button>

      <div style="flex: 1" />

      <div class="admin-user-card">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
          <div class="admin-avatar">{{ userInitial }}</div>
          <div>
            <p style="font-size: 0.8rem; color: #c8d3da; font-weight: 600;">{{ auth.user?.username || 'Admin' }}</p>
            <span class="admin-badge">Admin</span>
          </div>
        </div>
        <button
          style="width:100%; padding: 7px; background: rgba(224,82,82,0.1); border: 1px solid rgba(224,82,82,0.25);
                 border-radius: 6px; color: #e05252; font-size: 0.72rem; font-weight: 600;
                 cursor: pointer; font-family: 'Exo 2', sans-serif;"
          @click="logout"
        >
          Sair
        </button>
      </div>
    </aside>

    <div class="admin-main">

      <div class="admin-topbar">
        <div>
          <p class="admin-topbar-title">Dashboard Admin</p>
          <p class="admin-topbar-sub">Gerencie boloes e acompanhe os resultados</p>
        </div>
      </div>

      <div class="admin-content">

        <div class="admin-stats-grid">
          <div class="admin-stat-card gold">
            <p class="stat-label">Total de Boloes</p>
            <p class="stat-value gold">{{ stats.total }}</p>
          </div>
          <div class="admin-stat-card green">
            <p class="stat-label">Abertos</p>
            <p class="stat-value green">{{ stats.abertos }}</p>
          </div>
          <div class="admin-stat-card red">
            <p class="stat-label">Fechados</p>
            <p class="stat-value red">{{ stats.fechados }}</p>
          </div>
          <div class="admin-stat-card silver">
            <p class="stat-label">Fichas em Jogo</p>
            <p class="stat-value">{{ stats.fichas }}</p>
            <p class="stat-sub">soma dos premios</p>
          </div>
        </div>

        <div v-if="activeNav === 'boloes'">

          <div class="admin-section-header">
            <span class="admin-section-label">Boloes</span>
            <button class="btn-criar-bolao" @click="showCriarModal = true">+ Criar Bolao</button>
          </div>

          <div class="admin-filter-tabs">
            <button
              v-for="c in ['TODOS', 'A', 'B', 'C']"
              :key="c"
              :class="['filter-tab', filtroClasse === c ? 'active' : '']"
              @click="filtroClasse = c as any"
            >
              {{ c === 'TODOS' ? 'Todos' : `Classe ${c}` }}
            </button>
          </div>

          <div v-if="loadingBoloes" class="boloes-grid">
            <div v-for="n in 3" :key="n" class="bolao-card" style="opacity: 0.4; pointer-events: none;">
              <div class="bolao-card-header">
                <span class="bolao-class-tag">...</span>
                <span class="bolao-status aberto">...</span>
              </div>
              <div class="bolao-card-body">
                <div class="bolao-info-row"><span>Abertura</span><span>--:--</span></div>
                <div class="bolao-info-row"><span>Participantes</span><span>-/-</span></div>
                <div class="bolao-info-row"><span>Premio</span><span>-</span></div>
                <div class="bolao-progress-bar"><div class="bolao-progress-fill" style="width:0%" /></div>
                <div class="bolao-sorteio-time">--:--</div>
                <button class="bolao-btn" disabled>...</button>
              </div>
            </div>
          </div>

          <div v-else-if="boloesFiltrados.length === 0" class="admin-empty">
            <p style="font-size: 2rem; margin-bottom: 8px;">🎲</p>
            <p>Nenhum bolao encontrado.</p>
          </div>

          <div v-else class="boloes-grid">
            <BolaoCard
              v-for="bolao in boloesFiltrados"
              :key="bolao.id"
              :bolao="bolao"
              @atualizar="carregarBoloes"
            />
          </div>

        </div>

        <div v-else class="admin-empty" style="margin-top: 40px;">
          <p style="font-size: 2rem; margin-bottom: 8px;">🚧</p>
          <p>Secao em construcao</p>
        </div>

      </div>
    </div>

    <!-- Modal agora é um componente separado -->
    <CriarBolaoModal
      v-if="showCriarModal"
      @fechar="showCriarModal = false"
      @criado="onBolaoCreated"
    />

  </div>
</template>