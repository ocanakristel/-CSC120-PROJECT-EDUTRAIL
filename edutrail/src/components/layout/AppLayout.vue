<script setup>
import { isAuthenticated } from '@/utils/api'
import ProfileHeaderNavigation from './ProfileHeaderNavigation.vue'
import { onMounted, ref } from 'vue'

const props = defineProps(['isWithAppBarNavIcon'])
const emit = defineEmits(['isDrawerVisible'])

const isLoggedIn = ref(false)
const theme = ref('light')

const getLoggedStatus = async () => {
  isLoggedIn.value = await isAuthenticated()
}

onMounted(() => {
  getLoggedStatus()
})
</script>

<template>
  <v-responsive>
    <v-app :theme="theme">
      <!-- TOP BAR -->
      <v-app-bar class="pastel-top-bar" border app>
        <!-- LEFT: menu + logo + text grouped together -->
        <div class="d-flex align-center">
          <v-app-bar-nav-icon
            v-if="props.isWithAppBarNavIcon"
            icon="mdi-menu"
            @click="emit('isDrawerVisible')"
          />

          <!-- LOGO -->
          <v-img
            src="/images/logo.png"
            alt="EduTrail Logo"
            height="48"
            width="48"
            class="ml-2 logo-img"
            contain
          />

          <!-- TEXT -->
          <span class="logo-text ms-3">
            Edu<span class="logo-highlight">Trail</span>
          </span>
        </div>

        <!-- pushes profile nav to the right -->
        <v-spacer />

        <!-- RIGHT: profile dropdown -->
        <ProfileHeaderNavigation v-if="isLoggedIn" />
      </v-app-bar>

      <!-- SIDEBAR SLOT -->
      <slot name="navigation"></slot>

      <!-- MAIN CONTENT -->
      <v-main class="pastel-main">
        <slot name="content"></slot>
      </v-main>

      <!-- FOOTER -->
      <v-footer class="pastel-footer" border app>
        <div class="d-flex justify-center align-center w-100">
          © 2026 EduTrail Website
        </div>
      </v-footer>
    </v-app>
  </v-responsive>
</template>

<style scoped>
.pastel-top-bar {
  background-color: #4CAF50;
  color: #28361f !important;
  border-bottom: 1px solid rgba(40, 54, 31, 0.15);
}

/* Main background: soft pastel green, no gradient */
.pastel-main {
  background: #d4f7c5 !important;
}

/* Footer */
.pastel-footer {
  background-color: #4CAF50 !important;
  color: #28361f !important;
  border-top: 1px solid rgba(40, 54, 31, 0.15);
}

/* Logo image – white circle so it’s visible on yellow bar */
.logo-img {
  background-color: #ffffff;
  border-radius: 999px;
  padding: 4px;
}

/* Logo text */
.logo-text {
  font-size: 32px;
  font-weight: 900;
  color: #28361f;
}

.logo-highlight {
  color: #fcfcfa; 
}
</style>
