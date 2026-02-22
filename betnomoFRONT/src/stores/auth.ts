import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { AuthUser, LoginPayload, RegisterPayload } from '../types/auth'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'

const defaultHeaders = {
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

function parseApiError(err: any): string {
  if (err.errors) {
    const first = Object.values(err.errors as Record<string, string[]>)[0]
    return Array.isArray(first) ? first[0] : String(first)
  }
  if (err.error) return err.error
  if (err.message) return err.message
  return 'Erro desconhecido.'
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = () => !!token.value

  async function login(payload: LoginPayload): Promise<boolean> {
    isLoading.value = true
    error.value = null
    try {
      const response = await fetch(`${API_BASE_URL}/login`, {
        method: 'POST',
        headers: defaultHeaders,
        body: JSON.stringify(payload)
      })

      const data = await response.json()

      if (!response.ok) {
        error.value = parseApiError(data)
        return false
      }

      token.value = data.token
      localStorage.setItem('auth_token', data.token)

      await fetchMe()

      return true
    } catch {
      error.value = 'Erro ao conectar com o servidor.'
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function register(payload: RegisterPayload): Promise<boolean> {
    isLoading.value = true
    error.value = null
    try {
      const response = await fetch(`${API_BASE_URL}/register`, {
        method: 'POST',
        headers: defaultHeaders,
        body: JSON.stringify(payload)
      })

      const data = await response.json()

      if (!response.ok) {
        error.value = parseApiError(data)
        return false
      }

      user.value = data.user
      token.value = data.token
      localStorage.setItem('auth_token', data.token)

      return true
    } catch {
      error.value = 'Erro ao conectar com o servidor.'
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function fetchMe(): Promise<void> {
    if (!token.value) return
    try {
      const response = await fetch(`${API_BASE_URL}/me`, {
        headers: {
          ...defaultHeaders,
          'Authorization': `Bearer ${token.value}`
        }
      })

      if (response.ok) {
        user.value = await response.json()
      }
    } catch {
      // erro silencioso
    }
  }

  async function logout(): Promise<void> {
    if (token.value) {
      try {
        await fetch(`${API_BASE_URL}/logout`, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token.value}`
          }
        })
      } catch {
        // ignora erro de rede
      }
    }

    user.value = null
    token.value = null
    localStorage.removeItem('auth_token')
  }

  function clearError() {
    error.value = null
  }

  return {
    user,
    token,
    isLoading,
    error,
    isAuthenticated,
    login,
    register,
    logout,
    clearError,
    fetchMe
  }
})