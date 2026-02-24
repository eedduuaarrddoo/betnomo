<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import '../assets/css/userhome.css'
import ComprarFichaModal from '../components/ComprarFichaModal.vue' 

const auth = useAuthStore()

const activeCategory   = ref('classe-a')
const showComprarModal = ref(false)               

const categories = [
  { id: 'classe-a', label: 'Bolão Classe A', emoji: '🥇', count: 3 },
  { id: 'classe-b', label: 'Bolão Classe B', emoji: '🥈', count: 5 },
  { id: 'classe-c', label: 'Bolão Classe C', emoji: '🥉', count: 4 },
]

interface Bolao {
  id: number
  classe: string
  abertura: string
  status: 'aberto' | 'fechado'
  participantes: number
  maxParticipantes: number
  sorteio: string
  premio: string
}

const boloes: Bolao[] = [
  { id: 1, classe: 'classe-a', abertura: '15:00', status: 'aberto', participantes: 13, maxParticipantes: 20, sorteio: '22:00', premio: '50 fichas' },
  { id: 2, classe: 'classe-a', abertura: '15:00', status: 'aberto', participantes: 18, maxParticipantes: 20, sorteio: '22:00', premio: '50 fichas' },
  { id: 3, classe: 'classe-a', abertura: '15:00', status: 'aberto', participantes: 7,  maxParticipantes: 20, sorteio: '22:00', premio: '50 fichas' },
  { id: 4, classe: 'classe-b', abertura: '14:00', status: 'aberto', participantes: 9,  maxParticipantes: 30, sorteio: '21:00', premio: '30 fichas' },
  { id: 5, classe: 'classe-b', abertura: '14:00', status: 'fechado', participantes: 30, maxParticipantes: 30, sorteio: '21:00', premio: '30 fichas' },
  { id: 6, classe: 'classe-b', abertura: '14:00', status: 'aberto', participantes: 22, maxParticipantes: 30, sorteio: '21:00', premio: '30 fichas' },
  { id: 7, classe: 'classe-c', abertura: '13:00', status: 'aberto', participantes: 5,  maxParticipantes: 15, sorteio: '20:00', premio: '15 fichas' },
  { id: 8, classe: 'classe-c', abertura: '13:00', status: 'aberto', participantes: 11, maxParticipantes: 15, sorteio: '20:00', premio: '15 fichas' },
]

const filteredBoloes = computed(() =>
  boloes.filter(b => b.classe === activeCategory.value)
)

function getClassTag(classe: string) {
  if (classe === 'classe-a') return 'gold'
  if (classe === 'classe-b') return 'silver'
  return ''
}

function getClassLabel(classe: string) {
  return categories.find(c => c.id === classe)?.label.replace('Bolão ', '') || ''
}

function progressPercent(b: Bolao) {
  return Math.round((b.participantes / b.maxParticipantes) * 100)
}

const userInitial = computed(() =>
  auth.user?.username?.charAt(0).toUpperCase() || '?'
)

const userFichas = ref(1)

// Chamado pelo modal ao criar ficha com sucesso — atualiza o contador
function onFichaCreated() {
  userFichas.value += 1
}

onMounted(async () => {
  // TODO: buscar fichas do usuário via API
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
          <span class="category-badge">{{ cat.count }}</span>
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
              {{ userFichas }}
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
          {{ filteredBoloes.length }} bolões disponíveis
        </span>
      </div>

      <div class="boloes-grid">
        <div
          v-for="bolao in filteredBoloes"
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
              <span>{{ bolao.abertura }}</span>
            </div>
            <div class="bolao-info-row">
              <span>Participantes</span>
              <span>{{ bolao.participantes }}/{{ bolao.maxParticipantes }}</span>
            </div>
            <div class="bolao-info-row">
              <span>Prêmio</span>
              <span style="color: #f0a500;">{{ bolao.premio }}</span>
            </div>

            <div class="bolao-progress-bar">
              <div
                class="bolao-progress-fill"
                :style="{ width: progressPercent(bolao) + '%' }"
              />
            </div>

            <div class="bolao-sorteio-time">
              <span style="font-size: 0.6rem; color: #6b7b8a; font-family: 'Exo 2', sans-serif; font-weight: 500; letter-spacing: 0.05em; text-transform: uppercase;">Sorteio</span>
              {{ bolao.sorteio }}
            </div>

            <button
              class="bolao-btn"
              :disabled="bolao.status === 'fechado'"
            >
              {{ bolao.status === 'aberto' ? 'Participar' : 'Encerrado' }}
            </button>
          </div>
        </div>

        <div v-if="filteredBoloes.length === 0" class="empty-state">
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