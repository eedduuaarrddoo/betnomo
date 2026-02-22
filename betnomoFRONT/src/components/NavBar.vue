<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import BaseButton from '@/components/BaseButton.vue'
import { useAuthStore } from '@/stores/auth'

const emit = defineEmits<{
  openLogin: []
  openRegister: []
}>()

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const mobileMenuOpen = ref(false)

const navLinks = [
  { label: 'Home', to: '/' },
  { label: 'Sobre', to: '/about' }
]

function isActive(path: string) {
  return route.path === path
}

function handleLogout() {
  auth.logout()
  router.push('/')
}
</script>

<template>
  <nav class="navbar fixed top-0 left-0 right-0 z-40 h-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-full flex items-center justify-between gap-6">

      <RouterLink to="/" class="flex items-center gap-2 shrink-0">
        <!-- (caminho da imagem aqui: /src/assets/images/logo.png) -->
        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-hon-gold to-orange-600 flex items-center justify-center">
          <span class="font-display font-bold text-sm text-hon-darker">BET</span>
        </div>
        <span class="font-display text-white font-semibold text-lg hidden sm:block tracking-wide">
        <span class="text-hon-gold">NOMO</span>
        </span>
      </RouterLink>

      <div class="hidden md:flex items-center gap-8">
        <RouterLink
          v-for="link in navLinks"
          :key="link.to"
          :to="link.to"
          :class="['navbar-link font-body font-medium text-sm tracking-wide transition-colors duration-200',
            isActive(link.to) ? 'text-hon-gold active' : 'text-hon-text hover:text-white']"
        >
          {{ link.label }}
        </RouterLink>
      </div>

      <div class="hidden md:flex items-center gap-3">
        <template v-if="!auth.isAuthenticated()">
          <BaseButton variant="ghost" @click="emit('openLogin')">
            Entrar
          </BaseButton>
          <BaseButton variant="outline" @click="emit('openRegister')">
            Criar Conta
          </BaseButton>
        </template>
        <template v-else>
          <span class="text-sm text-hon-text font-medium">
            {{ auth.user?.displayName || auth.user?.username }}
          </span>
          <BaseButton variant="ghost" @click="handleLogout">
            Sair
          </BaseButton>
        </template>
      </div>

      <button
        class="md:hidden p-2 text-hon-muted hover:text-white transition-colors"
        @click="mobileMenuOpen = !mobileMenuOpen"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <transition
      enter-active-class="transition-all duration-200"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div
        v-if="mobileMenuOpen"
        class="md:hidden absolute top-16 left-0 right-0 bg-hon-darker border-b border-hon-border px-4 py-4 flex flex-col gap-3"
      >
        <RouterLink
          v-for="link in navLinks"
          :key="link.to"
          :to="link.to"
          :class="['py-2 font-medium text-sm', isActive(link.to) ? 'text-hon-gold' : 'text-hon-text']"
          @click="mobileMenuOpen = false"
        >
          {{ link.label }}
        </RouterLink>
        <hr class="border-hon-border" />
        <template v-if="!auth.isAuthenticated()">
          <button class="py-2 text-left text-sm text-hon-text font-medium" @click="emit('openLogin'); mobileMenuOpen = false">
            Entrar
          </button>
          <button class="py-2 text-left text-sm text-hon-green font-semibold" @click="emit('openRegister'); mobileMenuOpen = false">
            Criar Conta
          </button>
        </template>
        <template v-else>
          <button class="py-2 text-left text-sm text-red-400 font-medium" @click="handleLogout; mobileMenuOpen = false">
            Sair
          </button>
        </template>
      </div>
    </transition>
  </nav>
</template>
