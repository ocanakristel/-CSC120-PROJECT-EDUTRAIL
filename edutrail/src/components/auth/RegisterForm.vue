<script setup>
import {
  requiredValidator,
  emailValidator,
  passwordValidator,
  confirmedValidator,
} from '@/utils/validators'
import { ref } from 'vue'
import { api, formActionDefault } from '@/utils/api'
import AlertNotification from '../common/AlertNotification.vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const refVForm = ref()

// Default form data
const formData = ref({
  firstname: '',
  lastname: '',
  email: '',
  password: '',
  confirmed_password: '',
})

// Form action state
const formAction = ref({ ...formActionDefault })

// Password visibility
const visible = ref(false)

// Submit handler
const onSubmit = async () => {
  formAction.value = { ...formActionDefault }
  formAction.value.formProcess = true

  try {
    const { data, error } = await api.auth.signUp({
      firstname: formData.value.firstname,
      lastname: formData.value.lastname,
      email: formData.value.email,
      password: formData.value.password,
      confirmed_password: formData.value.confirmed_password,
    })

    if (error) {
      formAction.value.formErrorMessage = error.message
      formAction.value.formStatus = error.status
    } else if (data) {
      formAction.value.formSuccessMessage =
        'Successfully registered. Redirecting to login...'

      // ✅ keep message visible for 2 seconds
      setTimeout(() => {
        router.replace('/')
      }, 4000)

      // optional: clear fields after success (but keep message visible)
      refVForm.value?.reset()
    }
  } catch (err) {
    console.error(err)
    formAction.value.formErrorMessage = 'Unexpected error during registration.'
  } finally {
    // ✅ allow user to see message, don't instantly remove loading
    setTimeout(() => {
      formAction.value.formProcess = false
    }, 300)
  }
}

// Validate then submit
const onFormSubmit = () => {
  refVForm.value?.validate().then(({ valid }) => {
    if (valid) onSubmit()
  })
}
</script>

<template>
  <!-- Alerts -->
  <AlertNotification
    :form-success-message="formAction.formSuccessMessage"
    :form-error-message="formAction.formErrorMessage"
  />

  <!-- Register Form -->
  <v-form ref="refVForm" @submit.prevent="onFormSubmit">
    <v-row class="pt-4">
      <v-col cols="6">
        <v-text-field
          v-model="formData.firstname"
          label="Firstname"
          variant="outlined"
          density="compact"
          :rules="[requiredValidator]"
        />
      </v-col>

      <v-col cols="6">
        <v-text-field
          v-model="formData.lastname"
          label="Lastname"
          variant="outlined"
          density="compact"
          :rules="[requiredValidator]"
        />
      </v-col>
    </v-row>

    <div class="text-subtitle-1 font-weight-bold">Email</div>
    <v-text-field
      v-model="formData.email"
      placeholder="Enter your email"
      prepend-inner-icon="mdi-email-outline"
      variant="outlined"
      density="compact"
      :rules="[requiredValidator, emailValidator]"
    />

    <v-row>
      <v-col cols="6">
        <div class="text-subtitle-1 font-weight-bold">Password</div>
        <v-text-field
          v-model="formData.password"
          :type="visible ? 'text' : 'password'"
          :append-inner-icon="visible ? 'mdi-eye-off' : 'mdi-eye'"
          prepend-inner-icon="mdi-lock-outline"
          variant="outlined"
          density="compact"
          @click:append-inner="visible = !visible"
          :rules="[requiredValidator, passwordValidator]"
        />
      </v-col>

      <v-col cols="6">
        <div class="text-subtitle-1 font-weight-bold">Confirm Password</div>
        <v-text-field
          v-model="formData.confirmed_password"
          :type="visible ? 'text' : 'password'"
          :append-inner-icon="visible ? 'mdi-eye-off' : 'mdi-eye'"
          prepend-inner-icon="mdi-lock-outline"
          variant="outlined"
          density="compact"
          @click:append-inner="visible = !visible"
          :rules="[
            requiredValidator,
            confirmedValidator(formData.confirmed_password, formData.password),
          ]"
        />
      </v-col>
    </v-row>

    <v-btn
      type="submit"
      block
      size="large"
      class="csu-register-btn mb-4"
      :loading="formAction.formProcess"
      :disabled="formAction.formProcess"
    >
      Register
    </v-btn>
  </v-form>
</template>

<style scoped>
.csu-register-btn {
  background-color: #006400 !important;
  color: #ffd700 !important;
  font-weight: 700;
  font-size: 17px;
  border-radius: 10px;
  letter-spacing: 1px;
  margin-top: 8px;
}
</style>
