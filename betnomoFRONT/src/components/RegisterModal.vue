<script setup lang="ts">
import { ref, watch } from 'vue'
import { useAuthStore } from '../stores/auth'
import BaseInput from '../components/BaseInput.vue'

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  openLogin: []
  registerSuccess: []
}>()

const auth = useAuthStore()

const form = ref({
  username: '',
  email: '',
  password: '',
  repeatPassword: ''
})

const showPassword        = ref(false)
const showRepeat          = ref(false)
const fieldErrors         = ref<Record<string, string>>({})
const pendingVerification = ref(false)

watch(() => props.modelValue, (val) => {
  if (val) {
    form.value = { username: '', email: '', password: '', repeatPassword: '' }
    fieldErrors.value         = {}
    pendingVerification.value = false
    auth.clearError()
  }
})

function close() {
  emit('update:modelValue', false)
}

function closePending() {
  pendingVerification.value = false
  close()
}

function onOverlayClick(e: MouseEvent) {
  if ((e.target as HTMLElement).classList.contains('modal-overlay')) close()
}

function validate(): boolean {
  fieldErrors.value = {}
  if (!form.value.username) fieldErrors.value.username = 'Campo obrigatório'
  if (!form.value.email)    fieldErrors.value.email    = 'Campo obrigatório'
  if (!form.value.password) fieldErrors.value.password = 'Campo obrigatório'
  if (form.value.password !== form.value.repeatPassword) {
    fieldErrors.value.repeatPassword = 'As senhas não coincidem'
  }
  return Object.keys(fieldErrors.value).length === 0
}

async function handleSubmit() {
  if (!validate()) return
  const success = await auth.register({
    username:              form.value.username,
    email:                 form.value.email,
    password:              form.value.password,
    password_confirmation: form.value.repeatPassword
  })
  if (success) {
    pendingVerification.value = true
  }
}

function switchToLogin() {
  close()
  emit('openLogin')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="modal-overlay" @click="onOverlayClick">
        <div class="modal-container" style="max-width: 460px; max-height: 90vh; overflow-y: auto;">
          <button class="modal-close-btn" @click="close">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>

          <div class="p-7 pt-8">

            <!-- ── Tela de verificação pendente ───────────────────── -->
            <div v-if="pendingVerification" class="flex flex-col items-center text-center gap-4 py-4">
              <div class="w-16 h-16 rounded-full bg-hon-green/10 flex items-center justify-center">
                <svg class="w-8 h-8 text-hon-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
              </div>
              <h2 class="font-display text-2xl font-semibold text-white">
                Verifique seu <span class="text-hon-green">e-mail</span>
              </h2>
              <p class="text-sm text-hon-muted max-w-xs">
                Enviamos um link de confirmação para
                <span class="text-hon-text font-medium">{{ form.email }}</span>.
                Clique no link para ativar sua conta.
              </p>
              <button class="btn-primary w-full mt-2" @click="closePending">
                Entendido
              </button>
            </div>

            <!-- ── Formulário de cadastro ─────────────────────────── -->
            <template v-else>
              <h2 class="font-display text-center text-2xl font-semibold text-white mb-1">
                Criar sua <span class="text-hon-green">Conta</span>
              </h2>
              <p class="text-center text-xs text-hon-muted mb-6">
                Preencha seus dados para criar a conta.
              </p>

              <form @submit.prevent="handleSubmit" class="flex flex-col gap-4">
                <BaseInput
                  v-model="form.username"
                  label="Usuário"
                  placeholder="Usado para fazer login..."
                  required
                  :error="fieldErrors.username"
                />

                <BaseInput
                  v-model="form.email"
                  label="Email"
                  placeholder="Seu endereço de email..."
                  type="email"
                  required
                  :error="fieldErrors.email"
                />

                <div class="flex flex-col gap-1.5">
                  <label class="text-sm font-medium text-hon-text">
                    Senha <span class="text-hon-gold ml-0.5">*</span>
                  </label>
                  <div class="relative">
                    <input
                      v-model="form.password"
                      :type="showPassword ? 'text' : 'password'"
                      class="input-field pr-10"
                      required
                    />
                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-hon-muted hover:text-hon-text transition-colors" @click="showPassword = !showPassword">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!showPassword" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                      </svg>
                    </button>
                  </div>
                  <span v-if="fieldErrors.password" class="text-xs text-red-400">{{ fieldErrors.password }}</span>
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="text-sm font-medium text-hon-text">
                    Repetir Senha <span class="text-hon-gold ml-0.5">*</span>
                  </label>
                  <div class="relative">
                    <input
                      v-model="form.repeatPassword"
                      :type="showRepeat ? 'text' : 'password'"
                      class="input-field pr-10"
                      required
                    />
                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-hon-muted hover:text-hon-text transition-colors" @click="showRepeat = !showRepeat">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!showRepeat" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                      </svg>
                    </button>
                  </div>
                  <span v-if="fieldErrors.repeatPassword" class="text-xs text-red-400">{{ fieldErrors.repeatPassword }}</span>
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
                    Criando conta...
                  </span>
                  <span v-else>Criar Conta</span>
                </button>
              </form>

              <p class="text-center text-sm text-hon-muted mt-5">
                Já tem uma conta?
                <button
                  class="text-hon-green hover:text-white transition-colors font-medium ml-1"
                  @click="switchToLogin"
                >
                  Entrar.
                </button>
              </p>
            </template>

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