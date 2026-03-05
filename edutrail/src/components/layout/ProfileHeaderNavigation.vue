<script setup>
import { api, formActionDefault } from '@/utils/api'
import { getAvatarText } from '@/utils/helpers'
import { useRouter } from 'vue-router'
import { onMounted, ref } from 'vue'

const router = useRouter()

// Load variables
const userData = ref({
  initials: '',
  email: '',
  fullname: '',
  avatarUrl: '',     
})

const formAction = ref({
  ...formActionDefault,
})

// Logout functionality
const onLogOut = async () => {
  formAction.value = { ...formActionDefault }
  formAction.value.formProcess = true

  const { error } = await api.auth.signOut()
  if (error) {
    console.error('Error during logout:', error)
    return
  }
  formAction.value.formProcess = false
  router.replace('/')
}

// Getting user information functionality
const getUser = async () => {
  try {
    const resp = await api.auth.getUser()
    if (resp?.error) {
      console.warn('getUser returned error:', resp.error)
      return
    }

    // backend may return user object in different shapes, handle multiple cases
    const userObj = resp.data?.user || resp.data?.data?.user || resp.data || null
    const metadata = userObj?.user_metadata || userObj?.metadata || (typeof userObj === 'object' ? userObj : null)

    if (!metadata || typeof metadata !== 'object') {
      // Nothing useful to show
      return
    }

    // Use optional chaining to avoid any runtime errors
    userData.value.email = metadata?.email || metadata?.email_address || ''
    const first = metadata?.firstname || metadata?.first_name || ''
    const last = metadata?.lastname || metadata?.last_name || ''
    userData.value.fullname = `${first} ${last}`.trim()
    userData.value.initials = getAvatarText(userData.value.fullname || metadata?.email || '')
    userData.value.avatarUrl = metadata?.avatar_url || metadata?.avatarUrl || metadata?.avatar || ''
  } catch (e) {
    console.error('Failed to get user:', e)
  }
}

// Load functions during rendering
onMounted(() => {
  getUser()
})
</script>

<template>
  <v-menu min-width="200px" rounded>
    <template #activator="{ props }">
      <v-btn icon v-bind="props">
        <!-- TOP-RIGHT AVATAR -->
        <v-avatar v-if="userData.avatarUrl" size="large">
          <v-img :src="userData.avatarUrl" cover />
        </v-avatar>
        <v-avatar v-else color="deep-orange-lighten-1" size="large">
          <span class="text-h5">{{ userData.initials }}</span>
        </v-avatar>
      </v-btn>
    </template>

    <v-card class="mt-1">
      <v-card-text>
        <v-list>
          <v-list-item :subtitle="userData.email" :title="userData.fullname">
            <template #prepend>
              <!-- AVATAR INSIDE DROPDOWN -->
              <v-avatar v-if="userData.avatarUrl" size="large">
                <v-img :src="userData.avatarUrl" cover />
              </v-avatar>
              <v-avatar v-else color="deep-orange-lighten-1" size="large">
                <span class="text-h5">{{ userData.initials }}</span>
              </v-avatar>
            </template>
          </v-list-item>
        </v-list>

        <v-divider class="my-3" />

        <v-btn prepend-icon="mdi mdi-cog" to="/account/settings">
          Account Settings
        </v-btn>

        <v-divider class="my-3" />

        <v-btn
          prepend-icon="mdi mdi-logout"
          variant="plain"
          @click="onLogOut"
          :loading="formAction.formProcess"
          :disabled="formAction.formProcess"
        >
          Logout
        </v-btn>
      </v-card-text>
    </v-card>
  </v-menu>
</template>
