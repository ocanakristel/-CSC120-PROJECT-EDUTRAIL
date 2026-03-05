<script setup>
import { ref } from 'vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import RegisterForm from '@/components/auth/RegisterForm.vue'

// Not really used, but kept in case other parts rely on them
const theme = ref('light')
const visible = ref(false)

// We no longer call api.auth.signOut() here.
// Registration will just work without forcing a logout first.
</script>

<template>
  <AppLayout>
    <template #content>
      <v-container fluid class="csu-bg">
        <v-row class="d-flex align-center justify-center" style="width: 100%">
          <!-- Registration Card -->
          <v-col cols="12" md="6" class="mx-auto pt-10">
            <v-card
              class="mx-auto pa-12 pb-8 csu-card"
              elevation="8"
              max-width="448"
              rounded="lg"
            >
              <h1 class="text-center csu-title">Register</h1>
              <v-divider class="csu-divider my-4"></v-divider>

              <RegisterForm />

              <v-divider class="csu-divider my-4"></v-divider>

              <h5 class="text-center my-3">
                Already have an account? Click here to
                <RouterLink
                  to="/"
                  class="csu-link text-decoration-none d-inline-flex align-center"
                >
                  Login
                  <v-icon icon="mdi-chevron-right" class="ms-1" />
                </RouterLink>
              </h5>
            </v-card>
          </v-col>

          <!-- Feature/Logo Section -->
          <v-col cols="12" md="6" class="mx-auto text-center">
            <v-img
              class="bounce-animation mb-4"
              src="/images/logo.png"
              alt="EduTrail Logo"
              height="350"
            />
            <h3 class="csu-desc">
              It is your academic buddy. It will help you to organize tasks,
              assignments, and projects, keeping them on track and focused
              throughout your academic journey.
            </h3>
          </v-col>
        </v-row>
      </v-container>
    </template>
  </AppLayout>
</template>

<style scoped>
</style>
