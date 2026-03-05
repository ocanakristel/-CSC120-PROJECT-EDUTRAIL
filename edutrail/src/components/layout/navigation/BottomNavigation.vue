<script setup>
import { useAuthUserStore } from '@/stores/authUser'

const props = defineProps(['theme'])

// Use Pinia Store
const authStore = useAuthUserStore()

// Filter pages base on role
const onFilterPages = (path) => {
  if (authStore.userRole === 'Super Administrator') return true

  if (authStore.authPages.includes(path)) return true

  return false
}
</script>

<template>
  <v-bottom-navigation
    :bg-color="props.theme === 'light' ? 'amber-lighten-1' : 'yellow-darken-3'"
    grow
    active
  >
    <v-btn to="/dashboard">
      <v-icon>mdi-view-dashboard</v-icon>
      Dashboard
    </v-btn>

    <v-btn v-if="onFilterPages('/inventory/sales')" to="/inventory/sales">
      <v-icon>mdi-tray-arrow-up</v-icon>
      Form
    </v-btn>

    <v-btn v-if="onFilterPages('/reports/sales')" to="/reports/sales">
      <v-icon>mdi-sale</v-icon>
      Benefits
    </v-btn>
  </v-bottom-navigation>
</template>
