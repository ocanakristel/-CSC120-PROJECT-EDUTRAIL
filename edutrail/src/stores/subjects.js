import axios from 'axios'
import { defineStore } from 'pinia'
import { ref } from 'vue'

const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost:8000/api'

const apiClient = axios.create({
  baseURL: API_BASE,
  withCredentials: true,
})

export const useSubjectsStore = defineStore('subjects', () => {
  const subjectsFromApi = ref([])
  const subjects = ref([])
  const previews = ref({})

  // Load persisted previews from localStorage (if any)
  try {
    const raw = localStorage.getItem('subjects_previews')
    if (raw) {
      previews.value = JSON.parse(raw) || {}
    }
  } catch (e) {
    previews.value = {}
  }

  const savePreviews = () => {
    try {
      localStorage.setItem('subjects_previews', JSON.stringify(previews.value || {}))
    } catch (e) {
      // ignore
    }
  }

  function $reset() {
    subjectsFromApi.value = []
    subjects.value = []
  }

  // Retrieve from external API and insert to database
  async function getSubjectsFromApi() {
    try {
      const response = await axios.get('https://api.restful-api.dev/objects')
      subjectsFromApi.value = response.data

      const transformedData = subjectsFromApi.value.map(subject => ({
        name: subject.name,
        units: subject.units,
        description: subject.data?.description ?? '',
      }))

      // Insert to backend (backend will add user_id automatically)
      for (const item of transformedData) {
        await apiClient.post('/subjects', item)
      }

      // Refresh local list
      await getSubjects({ search: '' })
    } catch (error) {
      console.error('Error fetching or inserting subjects:', error.message)
    }
  }

  // Get all subjects for authenticated user
  async function getSubjects(tableFilters = {}) {
    try {
      const response = await apiClient.get('/subjects')

      // Debug: log raw response for diagnosis
      try {
        // eslint-disable-next-line no-console
        console.debug('subjects.store: raw /subjects response', response?.data)
      } catch (e) {
        /* ignore */
      }

      const data = response.data?.data?.subjects || response.data?.data || response.data || []

      // Debug: log derived data array
      try {
        // eslint-disable-next-line no-console
        console.debug('subjects.store: derived subjects array', JSON.parse(JSON.stringify(data)))
      } catch (e) {
        /* ignore */
      }

      // Apply search filter if provided
      if (tableFilters.search) {
        const search = tableFilters.search.toLowerCase()
        subjects.value = data.filter(s => (s.name || '').toLowerCase().includes(search))
      } else {
        subjects.value = data
      }
      // Re-attach any client-side previews saved for subjects so local previews persist
      try {
        for (let i = 0; i < subjects.value.length; i++) {
          const s = subjects.value[i]
          if (!s) continue
          const id = s.id
          if (id && previews.value[id] && (!s.image_url || s.image_url === '')) {
            subjects.value[i].image_url = previews.value[id]
          }
        }
      } catch (e) {
        /* ignore */
      }
    } catch (error) {
      console.error('Error fetching subjects:', error?.message || error)
      subjects.value = []
    }
  }

  // Add a new subject
  async function addSubject(formData) {
    const { image, ...data } = formData

    try {
      // Prepare form data to send with file
      const fd = new FormData()
      Object.entries(data).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          if (Array.isArray(value) || (typeof value === 'object' && !(value instanceof File))) {
            fd.append(key, JSON.stringify(value))
          } else {
            fd.append(key, value)
          }
        }
      })

      // Attach image if provided
      if (image) {
        // Append both keys to be compatible with backend expecting either 'file' or 'image'
        fd.append('file', image)
        fd.append('image', image)

        // Debug: log FormData entries (dev-only); will not run in production builds
        try {
          // eslint-disable-next-line no-console
          for (const pair of fd.entries()) {
            console.debug('FormData:', pair[0], pair[1])
          }
        } catch (e) {
          /* ignore */
        }
      }

      // Debug: log that we're about to send the request
      try {
        // eslint-disable-next-line no-console
        console.debug('subjects.store: POST /subjects payload keys:')
        for (const pair of fd.entries()) {
          // eslint-disable-next-line no-console
          console.debug(pair[0], pair[1])
        }
      } catch (e) {
        /* ignore */
      }

      const response = await apiClient.post('/subjects', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      // Debug: log server response for POST /subjects
      try {
        // eslint-disable-next-line no-console
        console.debug('subjects.store: POST /subjects response', response?.data)
      } catch (e) {
        /* ignore */
      }

      const newSubject = response.data?.data?.subject || response.data?.data || response.data

      // If backend didn't return an image URL but we uploaded one, show a local preview immediately
      if (newSubject) {
        // If server didn't return image_url, create a temporary preview URL
        let tempUrl = null
        if ((!newSubject.image_url || newSubject.image_url === '') && image) {
          try {
            tempUrl = URL.createObjectURL(image)
            newSubject.image_url = tempUrl
            if (newSubject.id) {
              previews.value[newSubject.id] = tempUrl
              savePreviews()
            }
          } catch (e) {
            // ignore if createObjectURL not available
          }
        }

        // Add to local list first for immediate feedback
        subjects.value.push(newSubject)

        // Refresh list from server to keep data in sync
        // But if we have a temp blob URL, preserve it even after refresh
        if (tempUrl && newSubject.id) {
          await getSubjects()
          // Re-attach temp URL if server didn't return image_url
          const idx = subjects.value.findIndex((s) => s.id === newSubject.id)
          if (idx >= 0 && (!subjects.value[idx].image_url || subjects.value[idx].image_url === '')) {
            subjects.value[idx].image_url = tempUrl
          }
        } else {
          await getSubjects()
        }

        // After refresh, if server data doesn't include image_url for the created subject,
        // find it and attach the temporary preview URL so the card shows the image.
        if (tempUrl) {
          let idx = -1
          if (newSubject.id) {
            idx = subjects.value.findIndex((s) => s.id === newSubject.id)
          }

          // Fallback: try to find by unique-ish combination (name + units + description)
          if (idx === -1) {
            idx = subjects.value.findIndex(
              (s) =>
                s.name === newSubject.name && String(s.units) === String(newSubject.units) &&
                (s.description || '').substring(0, 32) === (newSubject.description || '').substring(0, 32),
            )
          }

          if (idx >= 0 && (!subjects.value[idx].image_url || subjects.value[idx].image_url === '')) {
            try {
              subjects.value[idx].image_url = tempUrl
              if (subjects.value[idx].id) {
                previews.value[subjects.value[idx].id] = tempUrl
                savePreviews()
              }
            } catch (e) {
              /* ignore */
            }
          } else {
            // Debug: couldn't find matching subject to attach temp URL
            // eslint-disable-next-line no-console
            console.debug('subjects.store: could not attach temp preview; subjects list:', JSON.parse(JSON.stringify(subjects.value)))
          }
        }
      }

      return { data: newSubject }
    } catch (error) {
      console.error('Error adding subject:', error?.message, error?.response?.data || '')
      return { error: { message: error?.message || 'Failed to add subject', details: error?.response?.data } }
    }
  }

  // Update subject
  async function updateSubject(formData) {
    const { image, id, ...data } = formData

    try {
      // Prepare form data
      const fd = new FormData()
      Object.entries(data).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          if (Array.isArray(value) || (typeof value === 'object' && !(value instanceof File))) {
            fd.append(key, JSON.stringify(value))
          } else {
            fd.append(key, value)
          }
        }
      })

      // Attach image if provided
      let tempUrl = null
      if (image) {
        // Append both keys to be compatible with backend expecting either 'file' or 'image'
        fd.append('file', image)
        fd.append('image', image)
        try {
          tempUrl = URL.createObjectURL(image)
        } catch (e) {
          tempUrl = null
        }
      }

      const response = await apiClient.put(`/subjects/${id}`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      const updatedSubject = response.data?.data?.subject || response.data?.data

      // Update local list
      const index = subjects.value.findIndex(s => s.id === id)
      if (index >= 0 && updatedSubject) {
        // If server didn't return image_url but we uploaded one, attach temp preview immediately
        if (( !updatedSubject.image_url || updatedSubject.image_url === '' ) && tempUrl) {
          updatedSubject.image_url = tempUrl
          previews.value[id] = tempUrl
          savePreviews()
        }
        subjects.value[index] = updatedSubject
      } else if (index >= 0 && image && tempUrl) {
        // fallback: if update didn't return updatedSubject, at least attach temp preview
        subjects.value[index].image_url = tempUrl
        previews.value[id] = tempUrl
        savePreviews()
      }

      return { data: updatedSubject }
    } catch (error) {
      console.error('Error updating subject:', error?.message, error?.response?.data || '')
      return { error: { message: error?.message || 'Failed to update subject', details: error?.response?.data } }
    }
  }

  // Delete subject
  async function deleteSubject(id) {
    try {
      await apiClient.delete(`/subjects/${id}`)
      subjects.value = subjects.value.filter(subject => subject.id !== id)
      return { success: true }
    } catch (error) {
      console.error('Error deleting subject:', error.message)
      return { error }
    }
  }

  return {
    $reset,
    subjects,
    previews,
    getSubjectsFromApi,
    getSubjects,
    subjectsFromApi,
    addSubject,
    updateSubject,
    deleteSubject,
  }
})



