<script setup>
import AlertNotification from '../common/AlertNotification.vue'
import { api } from '@/utils/api'
import { requiredValidator, emailValidator } from '@/utils/validators'
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { logSecurityEvent } from '@/utils/securityLogs'

const router = useRouter()
const visible = ref(false)
const refVForm = ref()
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms))

const formDataDefault = {
  email: '',
  password: '',
}

const formData = ref({ ...formDataDefault })

const formActionDefault = {
  formProcess: false,
  formErrorMessage: '',
  formSuccessMessage: '',
  formStatus: null,
}

const formAction = ref({ ...formActionDefault })

const failedAttempts = ref(0)
const lockUntil = ref(0)
const isLoginLocked = ref(false)

const updateLockState = () => {
  if (!lockUntil.value) {
    isLoginLocked.value = false
    return
  }

  if (Date.now() < lockUntil.value) {
    isLoginLocked.value = true
  } else {
    lockUntil.value = 0
    isLoginLocked.value = false
    localStorage.removeItem('login_lock_until')
  }
}

onMounted(() => {
  const saved = localStorage.getItem('login_lock_until')
  if (saved) {
    lockUntil.value = Number(saved)
    updateLockState()
  }
})

const onSubmit = async () => {
  updateLockState()

  if (isLoginLocked.value) {
    formAction.value.formErrorMessage =
      'Too many failed attempts. Please try again later.'
    return
  }

  formAction.value = { ...formActionDefault, formProcess: true }

  const { error } = await api.auth.signInWithPassword({
    email: formData.value.email,
    password: formData.value.password,
  })

  if (error) {
    await logSecurityEvent('login_failed', `Email: ${formData.value.email}`)

    failedAttempts.value++
    if (failedAttempts.value >= 5) {
      lockUntil.value = Date.now() + 5 * 60 * 1000
      localStorage.setItem('login_lock_until', String(lockUntil.value))
      updateLockState()
    }

    if (error.status === 401 || error.status === 422) {
      formAction.value.formErrorMessage = 'Invalid email or password.'
    } else {
      formAction.value.formErrorMessage =
        'Something went wrong. Please try again later.'
    }

    formAction.value.formStatus = error.status
    formAction.value.formProcess = false
    return
  }

  // 🔥 NEW: confirm session really exists
  const check = await api.auth.getUser()

  if (check.error || !check.data?.user) {
    formAction.value.formErrorMessage = 'Login failed. Please try again.'
    formAction.value.formProcess = false
    return
  }

  // ✅ REAL success
  await logSecurityEvent('login_success', 'User logged in.')

  failedAttempts.value = 0
  lockUntil.value = 0
  localStorage.removeItem('login_lock_until')
  updateLockState()

  formAction.value.formSuccessMessage = 'Successfully Logged'
  formAction.value.formProcess = false

  formData.value.password = ''

  await sleep(500)
  router.replace('/system/dashboard')
}

const onFormSubmit = () => {
  refVForm.value?.validate().then(({ valid }) => {
    if (valid) onSubmit()
  })
}
</script>

<template>
  <AlertNotification
    :form-success-message="formAction.formSuccessMessage"
    :form-error-message="formAction.formErrorMessage"
  />

  <v-form ref="refVForm" @submit.prevent="onFormSubmit">
    <div class="pt-2 text-subtitle-1 font-weight-bold">Email</div>

    <v-text-field
      v-model="formData.email"
      class="font-weight-bold"
      density="compact"
      placeholder="Email address"
      prepend-inner-icon="mdi-email-outline"
      variant="outlined"
      :rules="[requiredValidator, emailValidator]"
    />

    <div class="text-subtitle-1 font-weight-bold">Password</div>

    <v-text-field
      v-model="formData.password"
      :append-inner-icon="visible ? 'mdi-eye-off' : 'mdi-eye'"
      :type="visible ? 'text' : 'password'"
      class="font-weight-bold"
      density="compact"
      placeholder="Enter your password"
      prepend-inner-icon="mdi-lock-outline"
      variant="outlined"
      @click:append-inner="visible = !visible"
      :rules="[requiredValidator]"
    />

    <p v-if="isLoginLocked" class="text-caption text-red mt-1">
      Your account is temporarily locked due to multiple failed login attempts.
      Please try again after a few minutes.
    </p>

    <v-btn
      type="submit"
      class="csu-login-btn mb-4"
      size="large"
      block
      :disabled="formAction.formProcess || isLoginLocked"
    >
      Log In
    </v-btn>
  </v-form>
</template>

<style scoped>
.csu-login-btn {
  background-color: #006400 !important;
  color: #ffd700 !important;
  font-weight: 700;
  font-size: 17px;
  border-radius: 10px;
  letter-spacing: 1px;
  margin-top: 8px;
  box-shadow: 0 3px 14px rgba(0, 64, 32, 0.08);
  transition: background 0.2s;
}
.csu-login-btn:hover,
.csu-login-btn:focus {
  background-color: #034d15 !important;
}
.text-red {
  color: #d32f2f;
}
</style>