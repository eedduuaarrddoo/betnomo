<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const route  = useRoute()
const router = useRouter()
const auth   = useAuthStore()

const status = ref<'loading' | 'success' | 'error'>('loading')

onMounted(async () => {
  try {
    const token      = route.params.token as string
    const redirectTo = await auth.verifyEmail(token)
    status.value     = 'success'
    setTimeout(() => router.push(redirectTo), 2000)
  } catch {
    status.value = 'error'
  }
})
</script>

<template>
  <div style="
    min-height: 100vh;
    background: #0a0e13;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Exo 2', sans-serif;
  ">
    <div style="
      background: rgba(13,17,23,0.95);
      border: 1px solid rgba(61,214,140,0.12);
      border-radius: 14px;
      padding: 48px 40px;
      max-width: 400px;
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 20px;
      text-align: center;
      box-shadow: 0 8px 40px rgba(0,0,0,0.5);
    ">

      <!-- Logo / topo -->
      <p style="
        font-family: 'Cinzel', serif;
        font-size: 1rem;
        font-weight: 700;
        color: #f0a500;
        letter-spacing: 0.08em;
        margin-bottom: 4px;
      ">
        Bolão da Sorte
      </p>

      <!-- ── Carregando ───────────────────────────────────── -->
      <template v-if="status === 'loading'">
        <div style="
          width: 64px; height: 64px;
          border-radius: 50%;
          background: rgba(61,214,140,0.08);
          display: flex; align-items: center; justify-content: center;
        ">
          <svg
            style="width: 32px; height: 32px; color: #3dd68c; animation: spin 1s linear infinite;"
            fill="none" viewBox="0 0 24 24"
          >
            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
        </div>

        <div>
          <p style="font-family: 'Cinzel', serif; font-size: 1.2rem; font-weight: 700; color: #c8d3da; margin-bottom: 6px;">
            Verificando...
          </p>
          <p style="font-size: 0.8rem; color: #6b7b8a;">
            Aguarde enquanto confirmamos seu e-mail.
          </p>
        </div>
      </template>

      <!-- ── Sucesso ──────────────────────────────────────── -->
      <template v-else-if="status === 'success'">
        <div style="
          width: 64px; height: 64px;
          border-radius: 50%;
          background: rgba(61,214,140,0.1);
          display: flex; align-items: center; justify-content: center;
          border: 1.5px solid rgba(61,214,140,0.3);
        ">
          <svg style="width: 30px; height: 30px; color: #3dd68c;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>

        <div>
          <p style="font-family: 'Cinzel', serif; font-size: 1.3rem; font-weight: 700; color: #c8d3da; margin-bottom: 6px;">
            E-mail verificado!
          </p>
          <p style="font-size: 0.8rem; color: #6b7b8a;">
            Sua conta está ativa. Redirecionando...
          </p>
        </div>

        <!-- barra de progresso animada -->
        <div style="width: 100%; height: 3px; background: rgba(61,214,140,0.1); border-radius: 99px; overflow: hidden;">
          <div style="
            height: 100%;
            background: #3dd68c;
            border-radius: 99px;
            animation: progress 2s linear forwards;
          "/>
        </div>
      </template>

      <!-- ── Erro ─────────────────────────────────────────── -->
      <template v-else>
        <div style="
          width: 64px; height: 64px;
          border-radius: 50%;
          background: rgba(239,68,68,0.08);
          display: flex; align-items: center; justify-content: center;
          border: 1.5px solid rgba(239,68,68,0.25);
        ">
          <svg style="width: 28px; height: 28px; color: #ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </div>

        <div>
          <p style="font-family: 'Cinzel', serif; font-size: 1.3rem; font-weight: 700; color: #c8d3da; margin-bottom: 6px;">
            Link inválido
          </p>
          <p style="font-size: 0.8rem; color: #6b7b8a;">
            {{ auth.error ?? 'Este link já foi utilizado ou expirou.' }}
          </p>
        </div>

        <button
          style="
            margin-top: 4px;
            width: 100%;
            padding: 11px;
            border-radius: 8px;
            border: 1px solid rgba(61,214,140,0.25);
            background: rgba(61,214,140,0.08);
            color: #3dd68c;
            font-family: 'Exo 2', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
          "
          @mouseover="($event.target as HTMLElement).style.background = 'rgba(61,214,140,0.15)'"
          @mouseleave="($event.target as HTMLElement).style.background = 'rgba(61,214,140,0.08)'"
          @click="router.push('/')"
        >
          Voltar ao início
        </button>
      </template>

    </div>
  </div>
</template>

<style scoped>
@keyframes spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}

@keyframes progress {
  from { width: 0%; }
  to   { width: 100%; }
}
</style>