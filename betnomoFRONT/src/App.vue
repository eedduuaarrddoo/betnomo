<script setup lang="ts">
import { ref } from 'vue'
import NavBar from '@/components/NavBar.vue'
import LoginModal from '@/components/LoginModal.vue'
import RegisterModal from '@/components/RegisterModal.vue'

const showLogin = ref(false)
const showRegister = ref(false)

function openLogin() {
  showRegister.value = false
  showLogin.value = true
}

function openRegister() {
  showLogin.value = false
  showRegister.value = true
}
</script>

<template>
  <div class="min-h-screen bg-hon-dark">
    <NavBar
      @open-login="openLogin"
      @open-register="openRegister"
    />

    <main>
      <RouterView />
    </main>

    <LoginModal
      v-model="showLogin"
      @open-register="openRegister"
    />

    <RegisterModal
      v-model="showRegister"
      @open-login="openLogin"
      @register-success="openLogin"
    />
  </div>
</template>
