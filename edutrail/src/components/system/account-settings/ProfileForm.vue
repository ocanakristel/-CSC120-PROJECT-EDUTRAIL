<script setup>
import { requiredValidator } from '@/utils/validators'
import AlertNotification from '@/components/common/AlertNotification.vue'
import { formActionDefault } from '@/utils/api'
import { useAuthUserStore } from '@/stores/authUser'
import { ref } from 'vue'

// Use Pinia Store
const authStore = useAuthUserStore()

// Load Variables
// formData still holds firstname/lastname internally but we treat the first
// field as full name in the UI.  The payload sent to the server is built
// manually in onSubmit so we only include the validated keys.
const formDataDefault = {
  firstname: authStore.userData.firstname,
  lastname: authStore.userData.lastname,
  nickname: authStore.userData.nickname || '',
  gender: authStore.userData.gender || '',
  contact_number: authStore.userData.contact_number || authStore.userData.contact || '',
}
const formData = ref({
  ...formDataDefault,
})
const formAction = ref({
  ...formActionDefault,
})
const refVForm = ref()

// Submit Functionality
const onSubmit = async () => {
  /// Reset Form Action utils; Turn on processing at the same time
  formAction.value = { ...formActionDefault, formProcess: true }

  // build payload with only fields our API cares about; if user entered a
  // full name we split it so both firstname/lastname are always present.
  let payload = {
    firstname: formData.value.firstname,
    lastname: formData.value.lastname,
    nickname: formData.value.nickname,
    gender: formData.value.gender,
    contact_number: formData.value.contact_number,
  }

  // If the UI uses the "full name" text field we may need to split it
  // into first/last components (user may have typed both together).  This
  // also ensures the backend always sees one of the keys and avoids the
  // undefined-index crash that used to trigger the 500.
  if (payload.firstname && payload.firstname.includes(' ')) {
    const parts = payload.firstname.trim().split(/\s+/)
    payload.firstname = parts.shift()
    payload.lastname = parts.join(' ')
  }

  console.debug('profile update payload', payload)
  const { data, error } = await authStore.updateUserInformation(payload)

  if (error) {
    // Debug log the raw error object for diagnosing the failure
    console.error('profile update error object:', error)

    // Add Error Message and Status Code (use message or details if available)
    formAction.value.formErrorMessage =
      error.message || error.details || 'Unable to update account'
    formAction.value.formErrorDetails =
      error.details && error.details !== error.message ? error.details : ''
    formAction.value.formStatus = error.status
  } else if (data) {
    // Add Success Message
    formAction.value.formSuccessMessage = 'Successfully Updated Account.'
  }

  // Turn off processing
  formAction.value.formProcess = false
}

// Trigger Validators
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
    :form-error-details="formAction.formErrorDetails"
  ></AlertNotification>

  <v-form ref="refVForm" @submit.prevent="onFormSubmit">
    <v-row dense>
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formData.firstname"
          label="Full Name"
          :rules="[requiredValidator]"
        ></v-text-field>
      </v-col>

      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formData.nickname"
          label="Nick Name"
        ></v-text-field>
      </v-col>

      <v-col cols="12" sm="6">
        <v-select
          v-model="formData.gender"
          :items="['Female','Male','Other']"
          label="Gender"
          clearable
        ></v-select>
      </v-col>

      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formData.contact_number"
          label="Contact Number"
          placeholder="e.g. +63 912 345 6789"
        ></v-text-field>
      </v-col>

      <!-- Language and Time Zone fields removed per design -->

      <!-- Email address section removed per request -->

      <v-col cols="12" class="d-flex justify-end">
        <v-btn
          class="mt-2"
          type="submit"
          color="orange-darken-3"
          prepend-icon="mdi-account-box-edit-outline"
          :disabled="formAction.formProcess"
          :loading="formAction.formProcess"
        >
          Update Information
        </v-btn>
      </v-col>
    </v-row>
  </v-form>
</template>

<style scoped>
.v-form{padding:0;margin:0}
.v-row{margin:0}
.v-col{padding:4px}
.v-text-field .v-input__control{min-height:40px}
.profile-fullname{font-weight:700;font-size:1.05rem}
.profile-email{color:#666; font-size:0.9rem}
.v-select .v-input__control{min-height:40px}
.v-list-item-title{font-weight:600}
</style>
