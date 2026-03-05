<template>
  <v-card class="profile-card-alt">
    <v-card-text class="pa-8 d-flex flex-column align-center">
      <div class="avatar-area mb-4">
        <PictureForm compact />
      </div>

      <div class="text-center">
        <div class="name text-h6 font-weight-bold">{{ fullName || '—' }}</div>
        <div class="email text-caption mt-1">{{ userEmail || '' }}</div>
      </div>

      <div class="mt-4 w-100 d-flex justify-center">
        <v-btn small color="primary" @click="onEditProfile">
          <v-icon left>mdi-account-edit</v-icon>
          Edit Profile
        </v-btn>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import PictureForm from './PictureForm.vue'
import { useAuthUserStore } from '@/stores/authUser'
import { computed } from 'vue'

const authStore = useAuthUserStore()

const fullName = computed(() => {
  const first = authStore.userData?.firstname || authStore.userData?.first_name || ''
  const last = authStore.userData?.lastname || authStore.userData?.last_name || ''
  const combined = `${first} ${last}`.trim()
  if (combined) return combined
  if (authStore.userData?.name) return authStore.userData.name
  if (authStore.userData?.username) return authStore.userData.username
  return '—'
})

const userEmail = computed(() => authStore.userData?.email || '')

const onEditProfile = () => {
  // Placeholder: focus first input or open edit dialog if available
  // For now scroll to profile form on the page
  const el = document.querySelector('.account-card')
  if (el) el.scrollIntoView({ behavior: 'smooth' })
}
</script>

<style scoped>
.profile-card-alt{background:#fff;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,0.06)}
.avatar-area{position:relative;overflow:visible}
.name{letter-spacing:0.2px;color:#1f2d3d}
.email{color:#6b6b6b;margin-top:4px}
</style>
