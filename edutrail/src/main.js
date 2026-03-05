// src/main.js
import '@/assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

// Vuetify
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

import App from './App.vue'
import router from './router'

// API client
import { api } from './utils/api'

const app = createApp(App)

// Vuetify config
const vuetify = createVuetify({
  icons: { defaultSet: 'mdi' },
  components,
  directives,
})

// Global plugins
app.use(createPinia())
app.use(router)
app.use(vuetify)

// Optional: make API injectable
app.provide('$api', api)

// Debug auth events only in dev
if (import.meta.env.DEV) {
  api.auth.onAuthStateChange((event, session) => {
    console.log('Auth event:', event)
    console.log('Current session:', session)
  })
}

// Bootstrap (don’t block mount if session check fails)
async function bootstrap() {
  try {
    const { data, error } = await api.auth.getSession()
    if (import.meta.env.DEV) {
      console.log('Initial API session on app load:', data?.session)
      if (error) console.warn('Initial session error:', error)
    }
  } catch (e) {
    if (import.meta.env.DEV) console.warn('bootstrap getSession failed:', e)
  } finally {
    app.mount('#app')
  }
}

bootstrap()

// --- Suppress noisy extension runtime.lastError console spam ---
// Some browser extensions log: "A listener indicated an asynchronous response by returning true, but the message channel closed before a response was received"
// This originates from extensions and not from app code; to avoid distracting red errors during development,
// filter those specific messages from console.error/warn while still preserving other errors.
try {
  const originalConsoleError = console.error.bind(console)
  const originalConsoleWarn = console.warn.bind(console)

  const filterText = 'A listener indicated an asynchronous response by returning true, but the message channel closed before a response was received'

  console.error = (...args) => {
    try {
      if (args && args.length && String(args[0]).includes(filterText)) return
    } catch (e) {
      // fallthrough
    }
    originalConsoleError(...args)
  }

  console.warn = (...args) => {
    try {
      if (args && args.length && String(args[0]).includes(filterText)) return
    } catch (e) {
      // fallthrough
    }
    originalConsoleWarn(...args)
  }

  // Catch unhandled promise rejections and optionally suppress the noisy runtime.lastError message
  window.addEventListener('unhandledrejection', (ev) => {
    try {
      const reason = ev?.reason
      if (String(reason).includes(filterText)) {
        ev.preventDefault()
        return
      }
    } catch (e) {
      // ignore
    }
    // leave other rejections to default handling
  })
} catch (e) {
  // If anything fails, don't block app startup
}