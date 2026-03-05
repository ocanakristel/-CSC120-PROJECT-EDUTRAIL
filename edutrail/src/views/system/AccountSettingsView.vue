<script setup>
import AppLayout from '@/components/layout/AppLayout.vue'
import SideNavigation from '@/components/layout/navigation/SideNavigation.vue'
import ProfileForm from '@/components/system/account-settings/ProfileForm.vue'
import PasswordForm from '@/components/system/account-settings/PasswordForm.vue'

import { useAuthUserStore } from '@/stores/authUser'
import { onMounted, ref, computed } from 'vue'
import { useDisplay } from 'vuetify'

// Utilize pre-defined Vue functions
const { mobile } = useDisplay()

// Use Pinia Store
const authStore = useAuthUserStore()

// Reactive states
const isDrawerVisible = ref(!mobile.value)

// Retrieve user information on mount
onMounted(() => {
  authStore.isAuthenticated()
  authStore.getUserInformation()
})
</script>

<template>
  <AppLayout
    :is-with-app-bar-nav-icon="true"
    @is-drawer-visible="isDrawerVisible = !isDrawerVisible"
  >
    <!-- Side Navigation -->
    <template #navigation>
      <SideNavigation :is-drawer-visible="isDrawerVisible" />
    </template>

    <!-- Main Content -->
    <template #content>
      <v-container class="py-2">
        <!-- Header Card -->
        <v-card class="mb-1">
          <template #title>
            <div style="display:flex; align-items:center; justify-content:flex-start">
              <div>
                <span class="text-h6 font-weight-bold">
                  <v-breadcrumbs :items="['Account', 'Settings']">
                    <template #prepend>
                      <v-icon icon="mdi-wrench" size="small" class="me-1" />
                    </template>
                  </v-breadcrumbs>
                </span>
              </div>
            </div>
          </template>
        </v-card>

        <v-row class="align-start">
          <!-- Forms Section (full width) -->
          <v-col cols="12">
            <!-- Profile Information Form -->
            <v-card class="mb-1 account-card">
              <template #title> Profile Information </template>
              <v-card-text>
                <ProfileForm />
              </v-card-text>
            </v-card>

            <!-- Change Password Form -->
            <v-card class="mb-1 account-card">
              <template #title> Change Password </template>
              <v-card-text>
                <PasswordForm />
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </template>
  </AppLayout>
</template>

<style scoped>
.account-card .v-card-text{padding:12px}
.account-card .v-card__title{padding:8px 12px}
.profile-email{margin-top:6px}

/* Centered profile avatar styles */

.profile-center-wrap{align-items:center}
.profile-card{background:#fff;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,0.08)}
.profile-card .v-card-text{padding:18px}
.avatar-wrap .picture-preview-wrap.compact{width:160px;height:160px;border-radius:50%;overflow:hidden;border:none;box-shadow:0 12px 30px rgba(0,0,0,0.12)}
.avatar-wrap .avatar-add-btn{position:absolute;right:-12px;bottom:-12px;background:#2196f3;color:#fff;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 12px rgba(0,0,0,0.14);z-index:10}
.profile-center-wrap .text-h5{margin-top:6px}
.profile-email{color:#6b6b6b}

/* allow the plus button to overflow the circular avatar */
.avatar-wrap{position:relative;overflow:visible}

</style>
