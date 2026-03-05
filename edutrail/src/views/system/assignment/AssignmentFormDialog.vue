<script setup>
import AlertNotification from '@/components/common/AlertNotification.vue'
import { requiredValidator, imageValidator } from '@/utils/validators'
import { formActionDefault } from '@/utils/api'
import { useAuthUserStore } from '@/stores/authUser'
import { useDisplay } from 'vuetify'
import { ref, watch } from 'vue'
import { useAssignmentsStore } from '@/stores/assignments'
import { fileExtract } from '@/utils/helpers'
import { logSecurityEvent } from '@/utils/securityLogs' // 📝 security logging

const props = defineProps(['isDialogVisible', 'itemData'])
const emit = defineEmits(['update:isDialogVisible'])

const { mdAndDown } = useDisplay()
const assignmentsStore = useAssignmentsStore()
const authStore = useAuthUserStore()

const formDataDefault = {
  description: '',
  additional_notes: '',
  due_date: '',
  due_time: '',
  image: null,
  checklist: [],
}



const formData = ref({ ...formDataDefault })
const formAction = ref({ ...formActionDefault })
const refVForm = ref()
const isUpdate = ref(false)
const imgPreview = ref('/images/img-product.png')

watch(
  () => props.itemData,
  () => {
    isUpdate.value = !!props.itemData
    formData.value = props.itemData
      ? { ...props.itemData, checklist: props.itemData.checklist || [] }
      : { ...formDataDefault }
    imgPreview.value = formData.value.image_url ?? '/images/img-product.png'
  },
)

const onPreview = async event => {
  try {
    const { fileObject, fileUrl } = await fileExtract(event)

    // 🔐 Extra security: validate type + size
    const file = fileObject
    const allowedTypes = ['image/png', 'image/jpeg']

    if (!allowedTypes.includes(file.type)) {
      formAction.value.formErrorMessage = 'Only PNG and JPEG images are allowed.'
      return
    }

    // 2 MB limit (also enforced by imageValidator, but we double-check)
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
  imgPreview.value = '/images/img-product.png'
}

const onSubmit = async () => {
  formAction.value = { ...formActionDefault, formProcess: true }

  try {

    const result = isUpdate.value
      ? await assignmentsStore.updateAssignments(formData.value)
      : await assignmentsStore.addAssignments(formData.value)

    // 📝 Log security event (create/update)
    const action = isUpdate.value ? 'assignment_updated' : 'assignment_created'
    const desc = formData.value.description || 'No description'
    await logSecurityEvent(action, `Assignment: ${desc}`)

    formAction.value.formSuccessMessage = isUpdate.value
      ? 'Successfully updated assignment.'
      : 'Successfully added assignment.'

    await assignmentsStore.getAssignments()

    setTimeout(() => {
      onFormReset()
    }, 2500)
  } catch (error) {
    formAction.value.formErrorMessage = error.message || 'An error occurred.'
  } finally {
    formAction.value.formProcess = false
  }
}

const onFormSubmit = () => {
  refVForm.value?.validate().then(({ valid }) => {
    if (valid) onSubmit()
  })
}

const onFormReset = () => {
  formAction.value = { ...formActionDefault }
  formData.value = { ...formDataDefault }
  imgPreview.value = '/images/img-product.png'
  emit('update:isDialogVisible', false)
}
</script>

<template>
  <v-dialog :max-width="mdAndDown ? '60vw' : '430'" :model-value="props.isDialogVisible" persistent>
    <v-card prepend-icon="mdi-information-box" title="Assignment Information">
      <AlertNotification
        :form-success-message="formAction.formSuccessMessage"
        :form-error-message="formAction.formErrorMessage"
      ></AlertNotification>

      <v-form ref="refVForm" @submit.prevent="onFormSubmit">
        <v-card-text>
          <v-row dense>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="formData.description"
                label="Description"
                :rules="[requiredValidator]"
                dense
              ></v-text-field>
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="formData.additional_notes"
                label="Additional Notes"
                :rules="[requiredValidator]"
                dense
              ></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field
                v-model="formData.due_date"
                label="Due Date"
                :rules="[requiredValidator]"
                type="date"
                dense
              ></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field
                v-model="formData.due_time"
                label="Due Time"
                :rules="[requiredValidator]"
                type="time"
                dense
              ></v-text-field>
            </v-col>

            <!-- Checklist Section: Steps/Milestones -->
            <v-col cols="12">
              <label class="mb-1" style="font-weight: 600">Steps / Milestones</label>
              <div class="steps-milestone-wrapper">
                <div
                  v-for="(item, idx) in formData.checklist"
                  :key="idx"
                  class="d-flex align-center mb-2"
                >
                  <v-text-field
                    v-model="item.label"
                    :rules="[requiredValidator]"
                    dense
                    placeholder="Step description"
                    class="mr-2"
                    hide-details
                  ></v-text-field>
                  <v-btn icon @click="formData.checklist.splice(idx, 1)" color="red" size="x-small">
                    <v-icon>mdi-close</v-icon>
                  </v-btn>
                </div>
                <v-btn
                  outlined
                  color="primary"
                  class="mt-1 add-step-btn"
                  @click="formData.checklist.push({ label: '', checked: false })"
                >
                  <v-icon left small>mdi-plus</v-icon>ADD STEP
                </v-btn>
              </div>
            </v-col>

            <v-col cols="12" sm="6">
              <v-img
                width="100"
                class="mx-auto rounded-circle"
                :src="imgPreview"
                alt="Assignment Picture Preview"
                cover
              ></v-img>
            </v-col>
            <v-col cols="12" sm="6">
              <v-file-input
                class="mt-2"
                :rules="[imageValidator]"
                accept="image/png, image/jpeg"
                label="Browse Assignment Picture"
                prepend-icon="mdi-camera"
                dense
                show-size
                chips
                @change="onPreview"
                @click:clear="onPreviewReset"
              ></v-file-input>
            </v-col>
          </v-row>
        </v-card-text>
        <v-divider></v-divider>
        <v-card-actions class="pa-2">
          <v-spacer></v-spacer>
          <v-btn text variant="plain" prepend-icon="mdi-close" @click="onFormReset">Close</v-btn>
          <v-btn prepend-icon="mdi-plus-box" color="red-darken-4" type="submit" variant="elevated">
            {{ isUpdate ? 'Update Assignment' : 'Add Assignment' }}
          </v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.steps-milestone-wrapper {
  display: flex;
  flex-direction: column;
  gap: 7px;
  margin-bottom: 15px;
}
.add-step-btn {
  width: max-content;
  align-self: flex-start;
  font-weight: 600;
}
</style>

