export interface LoginPayload {
  username: string
  password: string
}

export interface RegisterPayload {
  username: string
  email: string
  password: string
  password_confirmation: string
}

export interface AuthUser {
  id: number
  username: string
  email: string
  token: string
  is_admin: boolean
  chave_pix: string | null 
}

export interface ApiResponse<T = unknown> {
  data: T
  message: string
  success: boolean
}