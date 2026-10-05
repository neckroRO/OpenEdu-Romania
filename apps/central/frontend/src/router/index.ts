import { createRouter, createWebHistory } from 'vue-router'

import AppShell from '../components/layout/AppShell.vue'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: AppShell,
      children: [
        {
          path: '',
          name: 'home',
          component: () => import('../views/HomeView.vue'),
        },
        {
          path: 'resources',
          name: 'resources',
          component: () => import('../views/ResourcesView.vue'),
        },
        {
          path: 'resources/:resourceId',
          name: 'resource-detail',
          component: () => import('../views/ResourceDetailView.vue'),
        },
        {
          path: 'teacher',
          name: 'teacher',
          component: () => import('../views/TeacherView.vue'),
          meta: {
            requiresAuth: true,
            requiresContributor: true,
          },
        },
      ],
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
    },
  ],
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  await authStore.initialize()

  if (
    to.meta.requiresAuth &&
    !authStore.isAuthenticated
  ) {
    return {
      name: 'login',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  if (
    to.meta.requiresContributor &&
    !authStore.canContribute
  ) {
    return {
      name: 'home',
    }
  }

  if (
    to.name === 'login' &&
    authStore.isAuthenticated
  ) {
    return {
      name: authStore.canContribute
        ? 'teacher'
        : 'home',
    }
  }

  return true
})

export default router
