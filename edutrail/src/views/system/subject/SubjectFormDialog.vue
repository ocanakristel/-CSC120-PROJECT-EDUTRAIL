<script setup>
import AlertNotification from '@/components/common/AlertNotification.vue'
import { requiredValidator, imageValidator } from '@/utils/validators'
import { formActionDefault } from '@/utils/api'
import { useSubjectsStore } from '@/stores/subjects'
import { fileExtract } from '@/utils/helpers'
import { useDisplay } from 'vuetify'
import { ref, watch } from 'vue'
import { logSecurityEvent } from '@/utils/securityLogs' // 📝 logging

const props = defineProps(['isDialogVisible', 'itemData', 'tableFilters'])
const emit = defineEmits(['update:isDialogVisible'])

const { mdAndDown } = useDisplay()
const subjectsStore = useSubjectsStore()

const formDataDefault = {
  name: '',
  units: '0',
  description: '',
  image: null,
}
const formData = ref({ ...formDataDefault })
const formAction = ref({ ...formActionDefault })
const refVForm = ref()
const isUpdate = ref(false)
const imgPreview = ref('/images/img-product.png')

// Monitor itemData if it has data
watch(
  () => props.itemData,
  () => {
    isUpdate.value = !!props.itemData
    formData.value = props.itemData ? { ...props.itemData } : { ...formDataDefault }
    imgPreview.value = formData.value.image_url ?? '/images/img-product.png'
  },
)

const onPreview = async event => {
  try {
    const { fileObject, fileUrl } = await fileExtract(event)

    // 🔐 Extra validation: only PNG/JPEG and 2MB max
    const file = fileObject
    const allowedTypes = ['image/png', 'image/jpeg']

    if (!allowedTypes.includes(file.type)) {
      formAction.value.formErrorMessage = 'Only PNG and JPEG images are allowed.'
      return
    }

    if (file.size > 2_000_000) {
      formAction.value.formErrorMessage = 'Image size should be less than 2 MB.'
      return
    }

    formData.value.image = fileObject
    imgPreview.value = fileUrl
  } catch (err) {
    formAction.value.formErrorMessage = 'Failed to load image. Please try again.'
  }
}

const onPreviewReset = () => {
  formData.value.image = null
  imgPreview.value = formData.value.image_url ?? '/images/img-product.png'
}

const onSubmit = async () => {
  // Debug: mark we started submit
  // eslint-disable-next-line no-console
  console.debug('SubjectFormDialog: onSubmit called', JSON.parse(JSON.stringify(formData.value)))

  formAction.value = { ...formActionDefault, formProcess: true }
  
  // Set timeout for the form submission (8 seconds)
  const timeoutId = setTimeout(() => {
    formAction.value.formErrorMessage = 'Request timeout. Please try again.'
    formAction.value.formProcess = false
  }, 8000)

  try {
    const { data, error } = isUpdate.value
      ? await subjectsStore.updateSubject(formData.value)
      : await subjectsStore.addSubject(formData.value)

    clearTimeout(timeoutId)

    if (error) {
      formAction.value.formErrorMessage = error.message || 'Operation failed'
      formAction.value.formStatus = error.status
    } else if (data) {
      // 📝 log subject created/updated
      const action = isUpdate.value ? 'subject_updated' : 'subject_created'
      const name = formData.value.name || 'No name'
      await logSecurityEvent(action, `Subject: ${name}`)

      formAction.value.formSuccessMessage = isUpdate.value
        ? 'Successfully Updated Subject Information.'
        : 'Successfully Added Subject.'

      // Refresh subject list in parent table
      try {
        await subjectsStore.getSubjects(props.tableFilters)
      } catch (e) {
        console.error('Failed to refresh subjects after add/update:', e)
      }

      setTimeout(() => {
        onFormReset()
      }, 2500)
    }
  } catch (err) {
    clearTimeout(timeoutId)
    formAction.value.formErrorMessage = err.message || 'An error occurred.'
    console.error('Subject form error:', err)
  } finally {
    formAction.value.formProcess = false
  }
}

const onFormSubmit = async () => {
  // Debug: log validate invocation
  // eslint-disable-next-line no-console
  console.debug('SubjectFormDialog: onFormSubmit - validating form')

  try {
    // If the v-form ref is missing or validate is not a function, log and proceed to submit for debugging
    if (!refVForm.value) {
      // eslint-disable-next-line no-console
      console.warn('SubjectFormDialog: refVForm is undefined. Proceeding to submit for debug.')
      await onSubmit()
      return
    }

    if (typeof refVForm.value.validate !== 'function') {
      // eslint-disable-next-line no-console
      console.warn('SubjectFormDialog: refVForm.validate is not a function. Proceeding to submit for debug.', refVForm.value)
      await onSubmit()
      return
    }

    const res = await refVForm.value?.validate()

    // Vuetify validate may return boolean or an object { valid }
    let valid = false
    if (typeof res === 'boolean') valid = res
    else if (res && typeof res === 'object' && 'valid' in res) valid = res.valid

    // Debug: log validation result
    // eslint-disable-next-line no-console
    console.debug('SubjectFormDialog: validation result', res, 'interpreted valid=', valid)

    if (valid) await onSubmit()
    else {
      // eslint-disable-next-line no-console
      console.warn('SubjectFormDialog: validation failed, not submitting')
    }
  } catch (err) {
    // eslint-disable-next-line no-console
    console.error('SubjectFormDialog: validate() threw an error', err)
    // attempt to still submit to help debugging (only if user confirms)
  }
}

const onFormReset = () => {
  formAction.value = { ...formActionDefault }
  formData.value = { ...formDataDefault }
  imgPreview.value = '/images/img-product.png'
  emit('update:isDialogVisible', false)
}
</script>

<template>
  <v-dialog
    :max-width="mdAndDown ? undefined : '800'"
    :model-value="props.isDialogVisible"
    persistent
  >
    <v-card prepend-icon="mdi-information-box" title="Subject Information">
      <AlertNotification
        :form-success-message="formAction.formSuccessMessage"
        :form-error-message="formAction.formErrorMessage"
      ></AlertNotification>

      <v-form ref="refVForm" @submit.prevent="onFormSubmit">
        <v-card-text>
          <v-row dense>
            <v-col cols="12">
              <v-text-field
                v-model="formData.name"
                label="Name"
                :rules="[requiredValidator]"
              ></v-text-field>
            </v-col>

            <v-col cols="12" sm="2">
              <v-text-field
                v-model="formData.units"
                label="Units"
                min="0"
                type="number"
                :rules="[requiredValidator]"
              ></v-text-field>
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="formData.description"
                rows="2"
                label="Description"
                :rules="[requiredValidator]"
              ></v-textarea>
            </v-col>

            <v-col cols="12" sm="6" md="4">
              <v-img
                width="55%"
                class="mx-auto rounded-circle"
                color="red-darken-4"
                aspect-ratio="1"
                :src="imgPreview"
                alt="Subject Picture Preview"
                cover
              ></v-img>
            </v-col>

            <v-col cols="12" sm="6" md="8">
              <v-file-input
                class="mt-5"
                :rules="[imageValidator]"
                accept="image/png, image/jpeg"
                label="Browse Subject Picture"
                placeholder="Browse Subject Picture"
                prepend-icon="mdi-camera"
                show-size
                chips
                @change="onPreview"
                @click:clear="onPreviewReset"
              ></v-file-input>
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn text variant="plain" prepend-icon="mdi-close" @click="onFormReset"> Close </v-btn>
          <v-btn
            prepend-icon="mdi-plus-box"
            color="red-darken-4"
            type="submit"
            :loading="formAction.formProcess"
            :disabled="formAction.formProcess"
          >
            {{ isUpdate ? 'Update Subject' : 'Add Subject' }}
          </v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-dialog>
</template>

