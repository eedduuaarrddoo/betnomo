import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { AuthUser, RegisterPayload } from '../types/auth'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'

const defaultHeaders = {
  'Content-Type': 'application/json',
  'Accept': 'application/json',
}

function parseApiError(err: any): string {
  if (err.errors) {
    const first = Object.values(err.errors as Record<string, string[]>)[0]
    return Array.isArray(first) ? first[0] : String(first)
  }
  if (err.error)   return err.error
  if (err.message) return err.message
  return 'Erro desconhecido.'
}

export const useAuthStore = defineStore('auth', () => {
  const user      = ref<AuthUser | null>(null)
  const token     = ref<string | null>(localStorage.getItem('auth_token'))
  const isLoading = ref(false)
  const error     = ref<string | null>(null)

  const isAuthenticated = () => !!token.value

  async function login(username: string, password: string): Promise<string> {
    isLoading.value = true
    error.value     = null
    try {
      const res  = await fetch(`${API_BASE_URL}/login`, {
        method:  'POST',
        headers: defaultHeaders,
        body:    JSON.stringify({ username, password }),
      })
      const data = await res.json()
      if (!res.ok) {
        error.value = parseApiError(data)
        throw new Error(error.value ?? 'Erro ao fazer login')
      }
      token.value = data.token
      user.value  = data.user
      localStorage.setItem('auth_token', data.token)
      return data.is_admin ? '/admin' : '/dashboard'
    } catch (e: any) {
      if (!error.value) error.value = e.message
      throw e
    } finally {
      isLoading.value = false
    }
  }

  
  async function register(payload: RegisterPayload): Promise<boolean> {
    isLoading.value = true
    error.value     = null
    try {
      const res  = await fetch(`${API_BASE_URL}/register`, {
        method:  'POST',
        headers: defaultHeaders,
        body:    JSON.stringify(payload),
      })
      const data = await res.json()
      if (!res.ok) {
        error.value = parseApiError(data)
        return false
      }
      return true
    } catch {
      error.value = 'Erro ao conectar com o servidor.'
      return false
    } finally {
      isLoading.value = false
    }
  }

  
  async function verifyEmail(verificationToken: string): Promise<string> {
    isLoading.value = true
    error.value     = null
    try {
      const res  = await fetch(`${API_BASE_URL}/verify-email/${verificationToken}`, {
        method:  'GET',
        headers: defaultHeaders,
      })
      const data = await res.json()
      if (!res.ok) {
        error.value = parseApiError(data)
        throw new Error(error.value ?? 'Token inválido')
      }
      token.value = data.token
      localStorage.setItem('auth_token', data.token)
      // Busca os dados do usuário logo após verificar
      await fetchMe()
      return user.value?.is_admin ? '/admin' : '/dashboard'
    } catch (e: any) {
      if (!error.value) error.value = e.message
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function fetchMe(): Promise<void> {
    if (!token.value) return
    try {
      const res = await fetch(`${API_BASE_URL}/me`, {
        headers: {
          ...defaultHeaders,
          Authorization: `Bearer ${token.value}`,
        },
      })
      if (res.ok) user.value = await res.json()
    } catch {
      // silencioso
    }
  }

  async function logout(): Promise<void> {
    if (token.value) {
      try {
        await fetch(`${API_BASE_URL}/logout`, {
          method:  'POST',
          headers: {
            Accept:        'application/json',
            Authorization: `Bearer ${token.value}`,
          },
        })
      } catch {
        // silencioso
      }
    }
    user.value  = null
    token.value = null
    localStorage.removeItem('auth_token')
  }

  function clearError() { error.value = null }

  return {
    user, token, isLoading, error,
    isAuthenticated, login, register, verifyEmail,
    logout, clearError, fetchMe,
  }
})