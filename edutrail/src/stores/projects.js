import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'
import { useAuthUserStore } from './authUser'

const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost:8000/api'

// Configure axios to always send cookies (for Laravel session)
const apiClient = axios.create({
  baseURL: API_BASE,
  withCredentials: true, // CRITICAL: Send cookies with every request
})

export const useProjectsStore = defineStore('projects', () => {
  const projects = ref([])
  const authStore = useAuthUserStore()

  // Normalize backend response: convert 'steps' to 'checklist'
  function normalizeProject(p) {
    const checklist = Array.isArray(p.steps) ? p.steps : []
    return { ...p, checklist }
  }

  function $reset() {
    projects.value = []
  }

  async function getProjects() {
    try {
      // No need to get user ID - backend queries by authenticated user
      const response = await apiClient.get('/projects')
      const data = response.data?.data?.projects || []
      projects.value = data.map(normalizeProject)
    } catch (error) {
      console.error('Error fetching projects:', error.message)
      projects.value = []
    }
  }

  async function addProjects(formData) {
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
        fd.append('file', image)
      }

      const response = await apiClient.post('/projects', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      const newProject = response.data?.data?.project
      if (newProject) {
        projects.value.push(normalizeProject(newProject))
      }

      return response.data
    } catch (error) {
      console.error('Error adding project:', error.message)
      throw error
    }
  }

  async function updateProjects(formData) {
    const { image, id, ...data } = formData

    try {
      if (image) {
        const imageUrl = await uploadProjectImage(image)
        data.image_url = imageUrl
      }

      const response = await apiClient.put(`/projects/${id}`, data)
      
      // Update local project
      const index = projects.value.findIndex(p => p.id === id)
      if (index >= 0 && response.data?.data?.project) {
        projects.value[index] = normalizeProject(response.data.data.project)
      }

      return { data: response.data }
    } catch (error) {
      console.error('Error updating project:', error.message)
      return { error }
    }
  }

  async function deleteProjects(id) {
    try {
      await apiClient.delete(`/projects/${id}`)
      projects.value = projects.value.filter(project => project.id !== id)
      return { success: true }
    } catch (error) {
      console.error('Error deleting project:', error.message)
      return { error }
    }
  }

  async function finishProject(id) {
    try {
      const response = await apiClient.put(`/projects/${id}`, { status: 'finished' })
      projects.value = projects.value.filter(project => project.id !== id)
      return { success: true, data: response.data }
    } catch (error) {
      console.error('Error finishing project:', error.message)
      return { error }
    }
  }

  // Upload image and return public URL
  async function uploadProjectImage(file) {
    if (!file) return null

    const allowedTypes = ['image/png', 'image/jpeg']
    if (!allowedTypes.includes(file.type)) {
      throw new Error('Invalid image type. Only PNG and JPEG allowed.')
    }
    if (file.size > 2_000_000) {
      throw new Error('Image too large. Max size is 2 MB.')
    }

    try {
      const fd = new FormData()
      fd.append('file', file)

      const response = await apiClient.post('/storage/edutrail/upload', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      // Backend returns publicUrl in both places for compatibility
      return response.data?.publicUrl || response.data?.data?.publicUrl
    } catch (error) {
      console.error('Error uploading image:', error.message)
      throw error
    }
  }

  // Upload image and return public URL
  async function uploadProjectImage(file) {
    if (!file) return null

    const allowedTypes = ['image/png', 'image/jpeg']
    if (!allowedTypes.includes(file.type)) {
      throw new Error('Invalid image type. Only PNG and JPEG allowed.')
    }
    if (file.size > 2_000_000) {
      throw new Error('Image too large. Max size is 2 MB.')
    }

    try {
      const fd = new FormData()
      fd.append('file', file)

      const response = await apiClient.post('/storage/edutrail/upload', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      // Backend returns publicUrl in both places for compatibility
      return response.data?.publicUrl || response.data?.data?.publicUrl
    } catch (error) {
      console.error('Error uploading image:', error.message)
      throw error
    }
  }

  return {
    finishProject,
    deleteProjects,
    updateProjects,
    addProjects,
    projects,
    getProjects,
    $reset,
  }
})

