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

const usuarios        = ref<any[]>([])
const loadingUsuarios = ref(false)
const buscaUsuario    = ref('')

const boloesFiltrados = computed(() => {
  if (filtroClasse.value === 'TODOS') return boloes.value
  return boloes.value.filter(b => b.classe === filtroClasse.value)
})

const usuariosFiltrados = computed(() => {
  if (!buscaUsuario.value.trim()) return usuarios.value
  const query = buscaUsuario.value.toLowerCase()
  return usuarios.value.filter(u => 
    u.username.toLowerCase().includes(query) || 
    u.email.toLowerCase().includes(query)
  )
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

async function carregarUsuarios() {
  loadingUsuarios.value = true
  try {
    const res = await fetch(`${API}/admin/usuarios`, {
      headers: {
        Authorization: `Bearer ${token()}`,
        Accept: 'application/json',
      },
    })
    usuarios.value = await res.json()
  } catch (e) {
    console.error('Erro ao carregar usuarios:', e)
  } finally {
    loadingUsuarios.value = false
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

onMounted(() => {
  carregarBoloes()
  carregarUsuarios()
})
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
        <span class="admin-nav-badge">{{ usuarios.length }}</span>
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

        <div v-else-if="activeNav === 'usuarios'">
          <div class="admin-section-header">
            <span class="admin-section-label">Usuários Cadastrados</span>
            
            <div class="admin-search-wrapper">
              <input 
                v-model="buscaUsuario" 
                type="text" 
                placeholder="Buscar por usuário ou e-mail..." 
                class="cb-input admin-search-input" 
              />
            </div>
          </div>

          <div v-if="loadingUsuarios" class="admin-empty">
            <div class="cb-spinner admin-spinner" />
            <p>Carregando usuários...</p>
          </div>

          <div v-else-if="usuariosFiltrados.length === 0" class="admin-empty">
            <p>👥</p>
            <p>Nenhum usuário encontrado.</p>
          </div>

          <div v-else class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Usuário</th>
                  <th>E-mail</th>
                  <th class="text-center">Fichas A</th>
                  <th class="text-center">Fichas B</th>
                  <th class="text-center">Fichas C</th>
                  <th class="text-right">Função</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="u in usuariosFiltrados" :key="u.id">
                  <td>#{{ u.id }}</td>
                  <td class="username-cell">{{ u.username }}</td>
                  <td>{{ u.email }}</td>
                  <td class="text-center">
                    <span class="classe-badge A mini-badge">
                      {{ u.fichas_resumo?.A ?? 0 }}
                    </span>
                  </td>
                  <td class="text-center">
                    <span class="classe-badge B mini-badge">
                      {{ u.fichas_resumo?.B ?? 0 }}
                    </span>
                  </td>
                  <td class="text-center">
                    <span class="classe-badge C mini-badge">
                      {{ u.fichas_resumo?.C ?? 0 }}
                    </span>
                  </td>
                  <td class="text-right">
                    <span 
                      :class="['status-pill', u.is_admin ? 'aberto' : 'fechado', 'role-badge']"
                    >
                      {{ u.is_admin ? 'Admin' : 'Jogador' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
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