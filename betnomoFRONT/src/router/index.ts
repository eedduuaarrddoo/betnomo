import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/about',
      name: 'about',
      component: () => import('../views/AboutView.vue'),
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/UserHomeView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/admin',
      name: 'admin',
      component: () => import('../views/AdminHomeView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
  path: '/verify-email/:token',
  component: () => import('../views/VerifyEmailPage.vue'),
}
  ],
})

// Guard de navegação
router.beforeEach((to) => {
  const auth = useAuthStore()

  
  if (to.meta.requiresAuth && !auth.token) {
    return { name: 'home' }
  }

  
  if (to.meta.requiresAdmin && !auth.user?.is_admin) {
    return { name: 'dashboard' }
  }
})

export default router