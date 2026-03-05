import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'
import { logSecurityEvent } from '@/utils/securityLogs'

const http = axios.create({
  baseURL: '/api', 
  withCredentials: true,
})

export const useAssignmentsStore = defineStore('assignments', () => {
  const assignments = ref([])
  const currentUser = ref(null)

  async function getCurrentUser() {
    // IMPORTANT: your backend may return {user: {...}} OR just user object
    const res = await http.get('/auth/user')
    const user = res.data?.user ?? res.data
    currentUser.value = user
    return user
  }

  function normalizeAssignment(a) {
    // backend field is "steps" but UI uses "checklist"
    const checklist = Array.isArray(a.steps) ? a.steps : []
    return { ...a, checklist }
  }

  async function getAssignments() {
    await getCurrentUser()
    const res = await http.get('/assignments')
    assignments.value = (res.data || []).map(normalizeAssignment)
  }

  async function uploadAssignmentImage(file) {
    const fd = new FormData()
    fd.append('file', file)

    const res = await http.post('/storage/edutrail/upload', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    const url = res.data?.publicUrl
    if (!url) throw new Error('Upload succeeded but no publicUrl returned.')
    return url
  }

  async function addAssignments(formData) {
    await getCurrentUser()

    try {
      let image_url = null
      if (formData.image) {
        image_url = await uploadAssignmentImage(formData.image)
      }

      const payload = {
        description: formData.description ?? '',
        additional_notes: formData.additional_notes ?? '',
        due_date: formData.due_date ?? null,
        due_time: formData.due_time ?? null,
        status: formData.status ?? 'in_progress',
        steps: Array.isArray(formData.checklist) ? formData.checklist : [],
        image_url,
      }

      const res = await http.post('/assignments', payload)

      await logSecurityEvent('assignment_created', `Assignment: ${payload.description || '—'}`)
      await getAssignments()

      return res.data
    } catch (error) {
      console.error('Failed saving assignment:', error.response?.data || error.message)
      throw error
    }
  }

  async function updateAssignments(formData) {
    await getCurrentUser()
    if (!formData?.id) throw new Error('Missing assignment id.')

    try {
      let image_url = formData.image_url ?? null
      if (formData.image) {
        image_url = await uploadAssignmentImage(formData.image)
      }

      const payload = {
        description: formData.description ?? '',
        additional_notes: formData.additional_notes ?? '',
        due_date: formData.due_date ?? null,
        due_time: formData.due_time ?? null,
        status: formData.status ?? 'in_progress',
        steps: Array.isArray(formData.checklist) ? formData.checklist : [],
        image_url,
      }

      const res = await http.put(`/assignments/${formData.id}`, payload)

      await logSecurityEvent('assignment_updated', `Assignment ID: ${formData.id}`)
      await getAssignments()

      return res.data
    } catch (error) {
      console.error('Failed updating assignment:', error.response?.data || error.message)
      throw error
    }
  }

  async function deleteAssignments(id) {
    await getCurrentUser()
    await http.delete(`/assignments/${id}`)
    await logSecurityEvent('assignment_deleted', `Assignment ID: ${id}`)
    await getAssignments()
  }

  return {
    assignments,
    getAssignments,
    addAssignments,
    updateAssignments,
    deleteAssignments,
  }
})
