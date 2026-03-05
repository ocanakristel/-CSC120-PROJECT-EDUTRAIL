import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost:8000/api'

// Configure axios to send cookies with every request (for Laravel session)
const apiClient = axios.create({
  baseURL: API_BASE,
  withCredentials: true, // CRITICAL: Send cookies with every auth request
})

export const useAuthUserStore = defineStore('authUser', () => {
  // States
  const userData = ref({
    id: null,
    email: '',
    firstname: '',
    lastname: '',
    nickname: '',
    gender: '',
    contact_number: '',
    contact: '',
    user_role: '',
    branch: '',
    image_url: '',
  })
  const authPages = ref([])
  const authBranchIds = ref([])

  // Getters
  const userRole = computed(() => {
    return userData.value.is_admin ? 'Super Administrator' : userData.value.user_role || 'User'
  })

  // Reset State Action
  function $reset() {
    userData.value = {
      id: null,
      email: '',
      firstname: '',
      lastname: '',
      nickname: '',
      gender: '',
      contact_number: '',
      contact: '',
      user_role: '',
      branch: '',
      image_url: '',
    }
    authPages.value = []
    authBranchIds.value = []
  }

  // Actions

  /**
   * Check if user is authenticated by calling Laravel session endpoint
   * Laravel checks the session cookie and returns user data if valid
   */
  async function isAuthenticated() {
    try {
      const response = await apiClient.get('/auth/user')
      const user = response.data?.data || response.data

      if (user && user.id) {
        userData.value = {
          id: user.id,
          email: user.email || '',
          firstname: user.firstname || user.first_name || '',
          lastname: user.lastname || user.last_name || '',
          nickname: user.nickname || '',
          gender: user.gender || '',
          contact_number: user.contact_number || '',
          contact: user.contact || '',
          user_role: user.user_role || 'User',
          branch: user.branch || '',
          image_url: user.image_url || '',
        }
        return true
      }
      return false
    } catch (error) {
      console.error('Error checking authentication:', error.message)
      return false
    }
  }

  /**
   * Get user information from Laravel session
   */
  async function getUserInformation() {
    try {
      const response = await apiClient.get('/auth/user')
      const user = response.data?.data || response.data

      if (user && user.id) {
        userData.value = {
          id: user.id,
          email: user.email || '',
          firstname: user.firstname || user.first_name || '',
          lastname: user.lastname || user.last_name || '',
          nickname: user.nickname || '',
          gender: user.gender || '',
          contact_number: user.contact_number || '',
          contact: user.contact || '',
          user_role: user.user_role || 'User',
          branch: user.branch || '',
          image_url: user.image_url || '',
        }
      }
    } catch (error) {
      console.error('Error getting user information:', error.message)
    }
  }

  /**
   * Update user information via Laravel REST endpoint
   */
  async function updateUserInformation(updatedData) {
    try {
      const response = await apiClient.post('/auth/update', updatedData)
      const user = response.data?.data || response.data

      if (user && user.id) {
        userData.value = {
          id: user.id,
          email: user.email || '',
          firstname: user.firstname || user.first_name || '',
          lastname: user.lastname || user.last_name || '',
          nickname: user.nickname || '',
          gender: user.gender || '',
          contact_number: user.contact_number || '',
          contact: user.contact || '',
          user_role: user.user_role || 'User',
          branch: user.branch || '',
          image_url: user.image_url || '',
        }
        return { data: userData.value }
      }
    } catch (error) {
      // capture server-provided error details when available
      const serverError = error.response?.data?.error || error.response?.data || { message: error.message }
      console.error('Error updating user information:', serverError)
      return { error: serverError }
    }
  }

  /**
   * Update user avatar image
   */
  async function updateUserImage(file) {
    if (!file) return { error: { message: 'No file provided' } }

    try {
      // Upload image to storage endpoint
      const fd = new FormData()
      fd.append('file', file)

      const uploadResponse = await apiClient.post('/storage/edutrail/upload', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      // Debug: log upload response
      try {
        // eslint-disable-next-line no-console
        console.debug('authStore: upload response', uploadResponse?.data)
      } catch (e) {}

      const publicUrl = uploadResponse.data?.publicUrl || uploadResponse.data?.data?.publicUrl

      // If no publicUrl returned but upload succeeded, construct one or use path
      let imageUrl = publicUrl
      if (!imageUrl && uploadResponse.data?.path) {
        // If backend returns a path, construct full URL
        imageUrl = `${API_BASE.replace('/api', '')}/storage/${uploadResponse.data.path}`
      }
      if (!imageUrl && uploadResponse.data?.data?.path) {
        imageUrl = `${API_BASE.replace('/api', '')}/storage/${uploadResponse.data.data.path}`
      }

      // Update user with new image URL
      if (imageUrl) {
        const result = await updateUserInformation({
          ...userData.value,
          image_url: imageUrl,
        })

        // Refresh user info from server to ensure sync
        await getUserInformation()

        return result
      }

      // If we can't find an image URL, return error
      return { error: { message: 'Image uploaded but no URL returned from server' } }
    } catch (error) {
      console.error('Error updating user image:', error.message)
      return { error: { message: error.message || 'Failed to upload image' } }
    }
  }

  /**
   * Login user with email/password
   */
  async function login(email, password) {
    try {
      const response = await apiClient.post('/auth/sign-in', { email, password })
      const user = response.data?.data || response.data

      if (user && user.id) {
        userData.value = {
          id: user.id,
          email: user.email || '',
          firstname: user.firstname || user.first_name || '',
          lastname: user.lastname || user.last_name || '',
          nickname: user.nickname || '',
          gender: user.gender || '',
          contact_number: user.contact_number || '',
          contact: user.contact || '',
          user_role: user.user_role || 'User',
          branch: user.branch || '',
          image_url: user.image_url || '',
        }
        return { success: true, data: user }
      }
    } catch (error) {
      console.error('Error logging in:', error.message)
      return { error: error.message }
    }
  }

  /**
   * Register new user
   */
  async function register(email, password, firstname = '', lastname = '') {
    try {
      const response = await apiClient.post('/auth/sign-up', {
        email,
        password,
        firstname,
        lastname,
      })
      return { success: true, data: response.data }
    } catch (error) {
      console.error('Error registering:', error.message)
      return { error: error.message }
    }
  }

  /**
   * Logout user
   */
  async function logout() {
    try {
      await apiClient.post('/auth/sign-out')
      $reset()
      return { success: true }
    } catch (error) {
      console.error('Error logging out:', error.message)
      return { error: error.message }
    }
  }

  // Stub methods for compatibility (not needed for Laravel backend)
  async function getAuthPages(name) {
    authPages.value = []
  }

  async function getAuthBranchIds() {
    authBranchIds.value = []
  }

  return {
    userData,
    userRole,
    authPages,
    authBranchIds,
    $reset,
    isAuthenticated,
    getUserInformation,
    getAuthPages,
    getAuthBranchIds,
    updateUserInformation,
    updateUserImage,
    login,
    register,
    logout,
  }
})
