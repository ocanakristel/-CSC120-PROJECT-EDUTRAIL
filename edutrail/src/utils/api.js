// src/utils/api.js

const API_BASE = import.meta.env.VITE_API_BASE || '/api'

// ---------- helpers ----------
const getCookie = (name) => {
  const value = `; ${document.cookie}`
  const parts = value.split(`; ${name}=`)
  if (parts.length === 2) return parts.pop().split(';').shift()
  return null
}

const getXsrfToken = () => {
  // Laravel sets XSRF-TOKEN cookie (URL-encoded)
  const token = getCookie('XSRF-TOKEN')
  return token ? decodeURIComponent(token) : null
}

const hasFile = (obj) => {
  if (!obj || typeof obj !== 'object') return false
  return Object.values(obj).some((v) => v instanceof File || v instanceof Blob)
}

const toFormData = (obj) => {
  const fd = new FormData()
  Object.entries(obj || {}).forEach(([k, v]) => {
    if (v === undefined || v === null) return

    // arrays/objects -> JSON string (ex: checklist)
    if (Array.isArray(v) || (typeof v === 'object' && !(v instanceof File) && !(v instanceof Blob))) {
      fd.append(k, JSON.stringify(v))
      return
    }

    fd.append(k, v)
  })
  return fd
}

const handleResponse = async (res) => {
  if (!res) {
    return {
      data: null,
      error: { message: 'No response from server', status: 0, details: null },
    }
  }

  const contentType = res.headers.get('content-type') || ''
  let json = null

  if (contentType.includes('application/json')) {
    try {
      json = await res.json()
    } catch {
      json = null
    }
  }

  if (res.ok) return { data: json, error: null }

  return {
    data: null,
    error: {
      message: json?.error?.message || json?.message || res.statusText || 'Request failed',
      details: json?.error?.details || json?.details || json || null,
      status: res.status,
    },
  }
}

const request = async (url, options = {}) => {
  const xsrf = getXsrfToken()

  const headers = {
    ...(options.headers || {}),
  }

  // Only attach XSRF header if we have it (Laravel session/web middleware)
  if (xsrf) headers['X-XSRF-TOKEN'] = xsrf

  const res = await fetch(url, {
    credentials: 'include',
    ...options,
    headers,
  })

  return handleResponse(res)
}


class QueryBuilder {
  constructor(table) {
    this.table = table
    this.filters = {}
    this.pendingMethod = null
    this.payload = null
    this._selectColumns = '*'
  }

  eq(field, value) {
    this.filters[field] = value
    return this
  }

  select(columns = '*') {
    // ✅ IMPORTANT:
    // If there is a pending insert/update/delete, calling select() will execute it.
    this._selectColumns = columns || '*'
    return this._exec()
  }

  // Make QueryBuilder thenable so it can be awaited directly (insert/update/delete)
  then(resolve, reject) {
    // Execute pending operation (or GET) and resolve the promise
    return this._exec().then(resolve, reject)
  }

  insert(payload) {
    // ✅ make it chainable: api.from('x').insert(data).select()
    this.pendingMethod = 'insert'
    this.payload = payload
    return this
  }

  update(payload) {
    // ✅ chainable: api.from('x').update(data).eq('id',1).select()
    this.pendingMethod = 'update'
    this.payload = payload
    return this
  }

  delete() {
    // ✅ chainable: api.from('x').delete().eq('id',1).select()
    this.pendingMethod = 'delete'
    return this
  }

  async _exec() {
    const params = new URLSearchParams()
    if (this._selectColumns && this._selectColumns !== '*') params.set('select', this._selectColumns)
    Object.entries(this.filters).forEach(([k, v]) => params.set(k, v))

    const baseUrl = `${API_BASE}/${this.table}`
    const urlWithParams = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl

    // ✅ no pending method = normal GET
    if (!this.pendingMethod) {
      return request(urlWithParams, { method: 'GET' })
    }

    // ✅ INSERT
    if (this.pendingMethod === 'insert') {
      const bodyPayload = Array.isArray(this.payload) ? this.payload[0] : this.payload

      // if there is an image/file, send multipart/form-data
      if (hasFile(bodyPayload)) {
        const fd = toFormData(bodyPayload)
        // DO NOT set Content-Type for FormData; browser sets boundary
        return request(baseUrl, { method: 'POST', body: fd })
      }

      return request(baseUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(bodyPayload),
      })
    }

    // ✅ UPDATE
    if (this.pendingMethod === 'update') {
      const bodyPayload = this.payload

      if (hasFile(bodyPayload)) {
        const fd = toFormData(bodyPayload)
        // Laravel usually expects PUT/PATCH. For multipart you may need _method.
        // We'll use POST + _method=PATCH to be safe.
        fd.append('_method', 'PATCH')
        return request(urlWithParams, { method: 'POST', body: fd })
      }

      return request(urlWithParams, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(bodyPayload),
      })
    }

    // ✅ DELETE
    if (this.pendingMethod === 'delete') {
      return request(urlWithParams, { method: 'DELETE' })
    }

    return { data: null, error: { message: 'No pending method', status: 0, details: null } }
  }
}

// ---------- Storage (optional; depends on your Laravel routes) ----------
const storage = {
  from(bucket) {
    return {
      async upload(path, file, opts = {}) {
        const url = `${API_BASE}/storage/${bucket}/upload`
        const fd = new FormData()
        fd.append('path', path)
        fd.append('file', file)
        Object.entries(opts || {}).forEach(([k, v]) => fd.append(k, v))

        return request(url, { method: 'POST', body: fd })
      },

      async getPublicUrl(path) {
        const url = `${API_BASE}/storage/${bucket}/public-url?path=${encodeURIComponent(path)}`
        return request(url, { method: 'GET' })
      },
    }
  },
}

// ---------- Auth ----------
const auth = {
  onAuthStateChange(cb) {
    ;(async () => {
      const session = await auth.getSession().catch(() => ({ data: null }))
      try {
        cb('INITIAL', session.data?.session ?? null)
      } catch {
        // ignore
      }
    })()
    return { data: null }
  },

  async getSession() {
    try {
      return await request(`${API_BASE}/auth/session`, { method: 'GET' })
    } catch (e) {
      return { data: null, error: { message: e.message, status: 0, details: null } }
    }
  },

  async getUser() {
    try {
      return await request(`${API_BASE}/auth/user`, { method: 'GET' })
    } catch (e) {
      return { data: null, error: { message: e.message, status: 0, details: null } }
    }
  },

  async signUp({ firstname, lastname, email, password, password_confirmation, confirmed_password }) {
    try {
      return await request(`${API_BASE}/auth/sign-up`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          firstname,
          lastname,
          email,
          password,
          password_confirmation: password_confirmation ?? confirmed_password,
        }),
      })
    } catch (e) {
      return { data: null, error: { message: e.message, status: 0, details: null } }
    }
  },

  async signInWithPassword({ email, password }) {
    try {
      return await request(`${API_BASE}/auth/sign-in`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      })
    } catch (e) {
      return { data: null, error: { message: e.message, status: 0, details: null } }
    }
  },

  async signOut() {
    try {
      return await request(`${API_BASE}/auth/sign-out`, { method: 'POST' })
    } catch (e) {
      return { data: null, error: { message: e.message, status: 0, details: null } }
    }
  },
}

const api = {
  auth,
  from(table) {
    return new QueryBuilder(table)
  },
  storage,
}

// ---------- shared exports ----------
export const formActionDefault = {
  formProcess: false,
  formStatus: 200,
  formErrorMessage: '',
  formSuccessMessage: '',
}

export const isAuthenticated = async () => {
  const { data, error } = await auth.getSession()
  if (error) return false
  return !!data?.session
}

export const tableSearch = (search) => (search ||= '')

export { api }
