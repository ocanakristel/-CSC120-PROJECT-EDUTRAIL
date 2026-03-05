<script setup>
import { ref } from 'vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import LoginForm from '@/components/auth/LoginForm.vue'

const theme = ref('light')
const visible = ref(false)
</script>

<template>
  <AppLayout>
    <template #content>
      <v-container fluid class="csu-bg">
        <v-row class="d-flex align-center justify-center" style="width: 100%">
          <!-- Logo & Description -->
          <v-col cols="12" md="6" class="mx-auto text-center">
            <v-img
              class="bounce-animation mb-4"
              src="/images/logo.png"
              alt="EduTrail Logo"
              height="350"
            />
            <h3 class="csu-desc">
              It is your academic buddy. It will help you to organize tasks, assignments, and
              projects, keeping them on track and focused throughout your academic journey.
            </h3>
          </v-col>

          <!-- Login Card -->
          <v-col cols="12" md="6" class="mx-auto pt-10">
            <v-card class="mx-auto pa-12 pb-8 csu-card" elevation="8" max-width="448" rounded="lg">
              <h1 class="text-center csu-title">Login</h1>
              <v-divider class="csu-divider my-4"></v-divider>
              <LoginForm />
              <v-divider class="csu-divider my-4"></v-divider>
              <h5 class="text-center my-3">
                Don't have an account? Click here to
                <RouterLink to="/register">
                  <a class="csu-link text-decoration-none" href="#">
                    Register <v-icon icon="mdi-chevron-right"></v-icon>
                  </a>
                </RouterLink>
              </h5>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </template>
  </AppLayout>
</template>

<style scoped>
/* ✅ Make background match the Register green theme */
.csu-bg {
  /* light green gradient */
  background: linear-gradient(
    135deg,
    #d6f9c6 0%,
    #c2f0b3 50%,
    #b0e7a5 100%
  ) !important;
  min-height: 100vh;
}

/* Card */
.csu-card {
  background: #ffffff !important;
  /* softer green border instead of teal */
  border: 1.5px solid #a5d6a7;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
}

/* Title */
.csu-title {
  color: #254741;
  font-family: 'Open Sans', sans-serif;
  font-weight: 700;
}

/* Divider - green gradient instead of teal */
.csu-divider {
  background: linear-gradient(90deg, #66bb6a 0%, #d6f9c6 100%) !important;
  height: 3px;
  border-radius: 2px;
}

/* Inputs */
.v-input:deep(.v-field__outline) {
  border-color: #66bb6a !important;
}

/* ✅ Buttons: solid green like register page */
.v-btn,
.v-btn[tabindex] {
  background-color: #43a047 !important; /* green */
  color: #ffffff !important;
  font-weight: 700;
  font-size: 17px;
  border-radius: 999px;
  letter-spacing: 1px;
  margin-top: 12px;
}

/* Text */
input,
.v-label {
  color: #254741 !important;
}

.csu-link {
  color: #388e3c !important;
  font-weight: 500;
}

h3,
h5 {
  font-family: 'Lora', serif;
  font-weight: bold;
  color: #254741;
}
</style>

