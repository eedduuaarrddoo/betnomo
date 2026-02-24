<script setup lang="ts">
import { ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import BaseInput from '@/components/BaseInput.vue'

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  openRegister: []
}>()

const auth = useAuthStore()
const loginTab = ref<'username' | 'email' | 'mobile'>('username')

const form = ref({
  identifier: '',
  password: ''
})

const showPassword = ref(false)

watch(() => props.modelValue, (val) => {
  if (val) {
    form.value = { identifier: '', password: '' }
    auth.clearError()
  }
})

function close() {
  emit('update:modelValue', false)
}

function onOverlayClick(e: MouseEvent) {
  if ((e.target as HTMLElement).classList.contains('modal-overlay')) close()
}

import { useRouter } from 'vue-router'

const router = useRouter()

async function handleSubmit() {
  const success = await auth.login({
    username: form.value.identifier,
    password: form.value.password
  })
  if (success) {
    close()
    router.push('/dashboard')
  }
}

function switchToRegister() {
  close()
  emit('openRegister')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="modal-overlay" @click="onOverlayClick">
        <div class="modal-container">
          <button class="modal-close-btn" @click="close">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>

          <div class="p-7 pt-8">
            <h2 class="font-display text-center text-2xl font-semibold mb-1">
              <span class="text-hon-gold italic">Entrar</span>
              <span class="text-white"> para Continuar</span>
            </h2>
            <p class="text-center text-xs text-hon-muted mb-6">
              Use sua conta Juvio para entrar.
            </p>

            <div class="flex border-b border-hon-border mb-6">
              <button
                v-for="tab in ['username', 'email', 'mobile'] as const"
                :key="tab"
                :class="['modal-tab', loginTab === tab ? 'active' : '']"
                @click="loginTab = tab"
              >
                {{ tab === 'username' ? 'Usuário' : tab === 'email' ? 'Email' : 'Celular' }}
              </button>
            </div>

            <form @submit.prevent="handleSubmit" class="flex flex-col gap-4">
              <BaseInput
                v-model="form.identifier"
                :label="loginTab === 'username' ? 'Usuário' : loginTab === 'email' ? 'Email' : 'Celular'"
                :placeholder="loginTab === 'username' ? 'Digite seu usuário...' : loginTab === 'email' ? 'Digite seu email...' : 'Digite seu celular...'"
                :type="loginTab === 'email' ? 'email' : loginTab === 'mobile' ? 'tel' : 'text'"
                required
              />

              <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-hon-text">
                  Senha <span class="text-hon-gold ml-0.5">*</span>
                </label>
                <div class="relative">
                  <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Digite sua senha..."
                    class="input-field pr-10"
                    required
                  />
                  <button
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-hon-muted hover:text-hon-text transition-colors"
                    @click="showPassword = !showPassword"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path v-if="!showPassword" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                  </button>
                </div>
              </div>

              <div v-if="auth.error" class="error-message">
                {{ auth.error }}
              </div>

              <button
                type="submit"
                class="btn-primary mt-1"
                :disabled="auth.isLoading"
              >
                <span v-if="auth.isLoading" class="inline-flex items-center gap-2">
                  <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  Entrando...
                </span>
                <span v-else>Entrar</span>
              </button>
            </form>

            <p class="text-center text-sm text-hon-muted mt-5">
              Não tem uma conta Juvio?
              <button
                class="text-hon-green hover:text-white transition-colors font-medium ml-1"
                @click="switchToRegister"
              >
                Criar uma.
              </button>
            </p>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.25s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
