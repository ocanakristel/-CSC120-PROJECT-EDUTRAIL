// src/router/index.js
import { isAuthenticated } from '@/utils/api'
import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'
import DashBoardView from '@/views/system/DashBoardView.vue'
import NotFoundView from '@/views/errors/NotFoundView.vue'
import SubjectView from '@/views/system/subject/SubjectView.vue'
import AssignmentView from '@/views/system/assignment/AssignmentView.vue'
import ProjectView from '@/views/system/project/ProjectView.vue'
import AccountSettingsView from '@/views/system/AccountSettingsView.vue'
import CalendarView from '@/views/system/CalendarView.vue'
import ReportsView from '@/views/system/ReportsView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // LOGIN (HOME PAGE)
    {
      path: '/',
      name: 'login',
      component: LoginView,
    },

    // REGISTER
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
    },

    // DASHBOARD
    {
      path: '/system/dashboard',
      name: 'dashboard',
      component: DashBoardView,
    },

    // SUBJECTS
    {
      path: '/subjects',
      name: 'subjects',
      component: SubjectView,
    },

    // ASSIGNMENTS
    {
      path: '/assignments',
      name: 'assignments',
      component: AssignmentView,
    },

    // PROJECTS
    {
      path: '/projects',
      name: 'projects',
      component: ProjectView,
    },

    // ACCOUNT SETTINGS
    {
      path: '/account/settings',
      name: 'account-settings',
      component: AccountSettingsView,
    },

    //  CALENDAR
    {
      path: '/calendar',
      name: 'calendar',
      component: CalendarView,
    },

    //  REPORTS
    {
      path: '/reports',
      name: 'reports',
      component: ReportsView,
    },

    // 404
    {
      path: '/:catchAll(.*)',
      component: NotFoundView,
    },
  ],
})

/*
 |--------------------------------------------------------------
 | GLOBAL AUTH GUARD
 |--------------------------------------------------------------
 */
router.beforeEach(async (to, from) => {
  const loggedIn = await isAuthenticated()

  // If logged in → prevent access to login & register pages
  if (loggedIn && (to.name === 'login' || to.name === 'register')) {
    return { name: 'dashboard' }
  }

  // If NOT logged in → block access to protected pages
  const protectedRoutes = [
    'dashboard',
    'subjects',
    'assignments',
    'projects',
    'account-settings',
    'calendar', 
    'reports',  
  ]

  if (!loggedIn && protectedRoutes.includes(to.name)) {
    return { name: 'login' } // redirect to login page
  }

  return true
})

export default router
