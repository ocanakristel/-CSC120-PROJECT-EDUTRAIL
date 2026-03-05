<script setup>
import { useDisplay } from 'vuetify'
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/utils/api'
import { useAuthUserStore } from '@/stores/authUser'

const props = defineProps(['isDrawerVisible'])
const { mobile } = useDisplay()
const isDrawerVisible = ref(props.isDrawerVisible)

const router = useRouter()
const authStore = useAuthUserStore()

// Sync prop changes
watch(
  () => props.isDrawerVisible,
  () => {
    isDrawerVisible.value = props.isDrawerVisible
  },
)

// LOGOUT HANDLER
const handleLogout = async () => {
  try {
    await api.auth.signOut()
    authStore.$reset()
    router.push('/')              
  } catch (err) {
    console.error('Error logging out:', err)
  }
}

</script>

<template>
  <v-navigation-drawer
    v-model="isDrawerVisible"
    :temporary="mobile"
    :permanent="!mobile"
    app
    width="250"
    class="side-nav"
  >
    <v-list density="compact" nav>
      <!-- Dashboard -->
      <v-list-item prepend-icon="mdi-view-dashboard" title="Dashboard" to="/system/dashboard" />
      <v-divider />

      <!-- Your Tasks Dropdown -->
      <v-list-group>
        <template #activator="{ props }">
          <v-list-item v-bind="props" prepend-icon="mdi-notebook-multiple" title="Your Tasks" />
        </template>
        <v-list-item
          v-for="([title, icon, to], i) in [
            ['Assignments', 'mdi-pencil-box-outline', '/assignments'],
            ['Projects', 'mdi-book-multiple', '/projects'],
          ]"
          :key="i"
          :prepend-icon="icon"
          :title="title"
          :to="to"
        />
      </v-list-group>

      <v-divider />

      <!-- Subjects -->
      <v-list-item prepend-icon="mdi-bookshelf" title="List of Subjects" to="/subjects" />

      <v-divider />

      <!-- Logout (BOTTOM ITEM) -->
      <v-list-item
        prepend-icon="mdi-logout"
        title="Logout"
        @click="handleLogout"
      />
    </v-list>
  </v-navigation-drawer>
</template>

<style scoped>
/* Entire Sidebar Background */
.side-nav {
 background-color: #3b973e !important;
  color: #333333 !important;
  border-right: 1px solid rgba(0, 0, 0, 0.05);
  backdrop-filter: blur(8px);
}

/* Menu section titles */
.section-title {
  color: #0c0c0c !important;
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 6px;
}

/* List items */
.v-list-item {
  color: #0a0a0a !important;
  border-radius: 10px;
  margin: 4px 8px;
  transition: 0.25s ease;
}

/* Hover effect */
.v-list-item:hover {
  background-color: #d1e55f !important;
}

/* Active (selected) menu item */
.v-list-item--active {
  background-color: #f6f7f2 !important;
  color: #23311e !important;
  font-weight: 700 !important;
}

/* Icons */
.v-icon {
  color: #555555 !important;
  transition: 0.25s ease;
}

.v-list-item:hover .v-icon,
.v-list-item--active .v-icon {
  color: #358600 !important;
}

/* Submenu arrow */
.v-list-group__header .v-icon {
  color: #555555 !important;
}

/* Divider line */
.v-divider {
  border-color: rgba(0, 0, 0, 0.08) !important;
}

/* Scrollbar Styling (optional but beautiful) */
::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 6px;
}
</style>

