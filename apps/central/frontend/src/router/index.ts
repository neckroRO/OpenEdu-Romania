import { createRouter, createWebHistory } from 'vue-router'

import AppShell from '../components/layout/AppShell.vue'

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

export default router
