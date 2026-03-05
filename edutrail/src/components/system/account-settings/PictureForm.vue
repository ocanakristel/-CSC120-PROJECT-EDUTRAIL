<script setup>
import { defineProps } from 'vue'
import AlertNotification from '@/components/common/AlertNotification.vue'
import { useAuthUserStore } from '@/stores/authUser'
import { formActionDefault } from '@/utils/api'
import { imageValidator, requiredValidator } from '@/utils/validators'
import { fileExtract } from '@/utils/helpers'
import { ref, toRef, nextTick } from 'vue'

// Props
const props = defineProps({
  compact: { type: Boolean, default: false },
})
const compact = toRef(props, 'compact')
const hiddenFileInput = ref(null)

const openFilePicker = () => {
  hiddenFileInput.value?.click()
}

const handleHiddenFileChange = async (event) => {
  await onPreview(event)
  // auto-submit after selection
  await onSubmit()
}

// Use Pinia Store
const authStore = useAuthUserStore()

// Load Variables
const formDataDefault = {
  image: null,
}
const formData = ref({
  ...formDataDefault,
})
const formAction = ref({
  ...formActionDefault,
})
const refVForm = ref()
const imgPreview = ref(authStore.userData.image_url || '/images/img-profile.png')
const isFileSelected = ref(false)

// Function to handle file change and show image preview
const onPreview = async (event) => {
  const { fileObject, fileUrl } = await fileExtract(event)
  // Update formData
  formData.value.image = fileObject
  // Update imgPreview state
  imgPreview.value = fileUrl
  isFileSelected.value = true
  formAction.value.formErrorMessage = ''
}

// Function to reset preview if file-input clear is clicked
const onPreviewReset = () => {
  formData.value.image = null
  imgPreview.value = authStore.userData.image_url || '/images/img-profile.png'
  isFileSelected.value = false
  formAction.value = { ...formActionDefault }
}

// Submit Functionality
const onSubmit = async () => {
  /// Reset Form Action utils; Turn on processing at the same time
  formAction.value = { ...formActionDefault, formProcess: true }

  // Keep current data URL preview while uploading
  const localPreview = imgPreview.value

  const { data, error } = await authStore.updateUserImage(formData.value.image)

  if (error) {
    // Add Error Message and Status Code
    formAction.value.formErrorMessage = error.message || error
    formAction.value.formStatus = error.status
    // Keep the local preview visible even if upload failed
    imgPreview.value = localPreview
  } else if (data) {
    // Add Success Message
    formAction.value.formSuccessMessage = 'Successfully Updated Profile Image.'
    // Keep local preview and try to refresh server data
    imgPreview.value = localPreview
    try {
      await authStore.getUserInformation()
      // If server returns image_url, use it; otherwise keep local preview
      if (authStore.userData?.image_url && !authStore.userData.image_url.startsWith('data:')) {
        imgPreview.value = authStore.userData.image_url
      }
    } catch (e) {
      // ignore refresh errors, keep local preview
    }
    // Reset file selection after success
    setTimeout(() => {
      onPreviewReset()
    }, 1500)
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
  ></AlertNotification>

  <v-form ref="refVForm" @submit.prevent="onFormSubmit">
    <div v-if="compact" class="d-flex flex-column align-center">
      <div class="picture-preview-wrap compact mb-2" style="position:relative">
        <v-img
          v-if="imgPreview"
          class="profile-preview-img"
          :src="imgPreview"
          alt="Profile Picture Preview"
          aspect-ratio="1"
          cover
        />
        <div v-else class="profile-preview-fallback">
          <v-icon size="48" class="text-grey-lighten-1">mdi-account-circle</v-icon>
        </div>

        <!-- plus overlay button -->
        <v-btn
          class="avatar-add-btn"
          icon
          color="primary"
          elevation="2"
          @click="openFilePicker"
        >
          <v-icon>mdi-plus</v-icon>
        </v-btn>

        <!-- hidden native file input triggered by the plus button -->
        <input
          ref="hiddenFileInput"
          type="file"
          accept="image/png, image/jpeg, image/bmp"
          style="display:none"
          @change="handleHiddenFileChange"
        />
      </div>
    </div>

    <div v-else>
      <v-row>
        <v-col cols="12" sm="6" md="5" class="d-flex justify-center">
          <div class="picture-preview-wrap mx-auto mb-4">
            <v-img
              v-if="imgPreview"
              class="profile-preview-img"
              :src="imgPreview"
              alt="Profile Picture Preview"
              aspect-ratio="1"
              cover
            />
            <div v-else class="profile-preview-fallback">
              <v-icon size="80" class="text-grey-lighten-1">mdi-account-circle</v-icon>
            </div>
          </div>
        </v-col>

        <v-col cols="12" sm="6" md="7">
          <v-file-input
            class="mt-5"
            :rules="[requiredValidator, imageValidator]"
            accept="image/png, image/jpeg, image/bmp"
            label="Browse Profile Picture"
            placeholder="Browse Profile Picture"
            prepend-icon="mdi-camera"
            show-size
            chips
            @change="onPreview"
            @click:clear="onPreviewReset"
          ></v-file-input>

          <div class="d-flex gap-2 mt-3 flex-wrap picture-action-row">
            <v-btn
              type="submit"
              color="blue"
              prepend-icon="mdi-upload"
              :disabled="!isFileSelected || formAction.formProcess"
              :loading="formAction.formProcess"
            >
              Upload Picture
            </v-btn>

            <v-btn
              v-if="isFileSelected"
              color="orange"
              prepend-icon="mdi-close"
              :disabled="formAction.formProcess"
              @click="onPreviewReset"
            >
              Cancel
            </v-btn>
          </div>
        </v-col>
      </v-row>
    </div>
  </v-form>
</template>

  <style scoped>
  .picture-preview-wrap{width:150px;height:150px;border-radius:50%;overflow:hidden;border:none;box-shadow:0 8px 18px rgba(0,0,0,0.08)}
  .picture-preview-wrap.compact{width:160px;height:160px;border-radius:50%;overflow:hidden;border:none;box-shadow:0 12px 30px rgba(0,0,0,0.12)}
  .profile-preview-img{width:100%;height:100%;object-fit:cover}
  .profile-preview-fallback{width:150px;height:150px;display:flex;align-items:center;justify-content:center;background:#ffffff;border-radius:50%}
  .profile-preview-fallback.compact{width:160px;height:160px}
  .picture-action-row v-btn{min-width:140px}
  .avatar-add-btn{position:absolute;right:-12px;bottom:-12px;border-radius:50%;background:#2196f3;color:#fff;width:44px;height:44px;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 12px rgba(0,0,0,0.14);z-index:20}
  .picture-preview-wrap.compact .profile-preview-fallback{width:160px;height:160px}
  </style>
