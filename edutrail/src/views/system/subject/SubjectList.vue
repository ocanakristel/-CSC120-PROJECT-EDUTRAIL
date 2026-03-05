<script setup>
import SubjectFormDialog from './SubjectFormDialog.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import { onMounted, ref, reactive, watch, computed } from 'vue'
import { useSubjectsStore } from '@/stores/subjects'
import { formActionDefault } from '@/utils/api'

const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost:8000'

// Use Pinia Store
const subjectsStore = useSubjectsStore()

// Load variables
const tableFilters = ref({
  search: '',
})
const itemData = ref(null)
const isDialogVisible = ref(false)
const deleteId = ref(null)
const formAction = ref({
  ...formActionDefault,
})
const isConfirmDeleteDialog = ref(false)

// Add Subject
const onAdd = () => {
  itemData.value = null
  isDialogVisible.value = true
}

// Retrieve Data based on Search
const onSearchSubjects = async () => {
  if (
    tableFilters.value.search?.length >= 3 ||
    tableFilters.value.search?.length == 0 ||
    tableFilters.value.search === null
  ) {
    await subjectsStore.getSubjects(tableFilters.value)
  }
}

// Update Subject
const onUpdate = (subject) => {
  itemData.value = subject
  isDialogVisible.value = true
}

// Trigger Delete Dialog
const onDelete = (id) => {
  deleteId.value = id
  isConfirmDeleteDialog.value = true
}

// Confirm Delete
const onConfirmDelete = async () => {
  formAction.value = { ...formActionDefault, formProcess: true }

  const { error } = await subjectsStore.deleteSubject(deleteId.value)

  formAction.value.formProcess = false

  if (error) {
    formAction.value.formErrorMessage = error.message
    return
  }

  formAction.value.formSuccessMessage = 'Successfully Deleted Subject.'

  // Refresh the subjects list with tableFilters
  await subjectsStore.getSubjects(tableFilters.value)
}

onMounted(async () => {
  if (subjectsStore.subjects.length === 0) {
    await subjectsStore.getSubjects(tableFilters.value)
  }
  // Debug: log loaded subjects to help diagnose missing image URLs
  try {
    // eslint-disable-next-line no-console
    console.debug('SubjectList: subjects loaded', JSON.parse(JSON.stringify(subjectsStore.subjects)))
  } catch (e) {
    /* ignore */
  }
})

// When the subjects list changes (e.g., after an update), clear imageCandidates
// so getImageSrc rebuilds candidates from the latest server data.
watch(
  () => subjectsStore.subjects,
  (newVal) => {
    try {
      // eslint-disable-next-line no-console
      console.debug('SubjectList: subjects changed, rebuilding image candidates')
    } catch (e) {}
    if (!newVal || !Array.isArray(newVal)) return
    for (const s of newVal) {
      if (s && s.id && imageCandidates[s.id]) {
        delete imageCandidates[s.id]
      }
      if (s && s.id && brokenImages[s.id]) {
        // reset broken flag to allow reattempts
        delete brokenImages[s.id]
      }
    }
  },
  { deep: true }
)

// Maintain a list of candidate URLs per subject and current index.
const imageCandidates = reactive({})

// Local filtered view of subjects so search works instantly and reliably
const filteredSubjects = computed(() => {
  const q = String(tableFilters.value.search || '').trim().toLowerCase()
  if (!q) return subjectsStore.subjects
  try {
    return subjectsStore.subjects.filter((s) => {
      const name = (s.name || '').toString().toLowerCase()
      const desc = (s.description || '').toString().toLowerCase()
      return name.includes(q) || desc.includes(q)
    })
  } catch (e) {
    return subjectsStore.subjects
  }
})

const buildCandidates = (subject) => {
  if (!subject) return []
  const raw = [subject.image_url, subject.image, subject.public_url, subject.path, subject.url]
    .filter(Boolean)
    .filter((v) => typeof v === 'string')

  const out = []
  raw.forEach((c) => {
    if (!c) return
    // pass-throughs
    if (/^blob:/i.test(c) || /^data:/i.test(c)) out.push(c)
    else if (/^https?:\/\//i.test(c)) out.push(c)
    else if (c.startsWith('/')) out.push(`${API_BASE}${c}`)
    else {
      // try a set of likely prefixes the backend might expect
      out.push(`${API_BASE}/${c}`)
      out.push(`${API_BASE}/storage/${c}`)
      out.push(`${API_BASE}/public/${c}`)
      out.push(`${API_BASE}/storage/app/public/${c}`)
    }
  })
  // remove duplicates while preserving order
  return Array.from(new Set(out))
}

const getImageSrc = (subject) => {
  if (!subject || !subject.id) return null
  // Prefer any client-side preview saved in the store (temporary data URL/blob)
  try {
    if (subjectsStore.previews && subjectsStore.previews[subject.id]) return subjectsStore.previews[subject.id]
  } catch (e) {
    /* ignore */
  }

  if (!imageCandidates[subject.id]) {
    imageCandidates[subject.id] = {
      list: buildCandidates(subject),
      idx: 0,
    }
  }
  const info = imageCandidates[subject.id]
  if (!info.list || info.list.length === 0) return null
  return info.list[info.idx]
}

// track broken images by subject id for debugging/fallback
const brokenImages = reactive({})

const onImageError = (subject) => {
  if (!subject || !subject.id) return
  const info = imageCandidates[subject.id]
  if (!info) {
    brokenImages[subject.id] = true
    return
  }
  // advance to next candidate, if available
  if (info.idx < info.list.length - 1) {
    info.idx += 1
    // trigger reactivity by replacing the object
    imageCandidates[subject.id] = { ...info }
    try {
      // eslint-disable-next-line no-console
      console.debug('SubjectList: image fallback to next candidate for', subject.id, info.list[info.idx])
    } catch (e) {}
    return
  }
  // exhausted candidates
  brokenImages[subject.id] = true
  try {
    // eslint-disable-next-line no-console
    console.debug('SubjectList: image load error (all candidates failed) for', subject.id, info.list)
  } catch (e) {}
}

const openImageInNewTab = (subject) => {
  const src = getImageSrc(subject)
  if (!src) return
  window.open(src, '_blank')
}

// Highlight matched text in results
const escapeRegExp = (string) => {
  return String(string).replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

const escapeHtml = (unsafe) => {
  return String(unsafe)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

const highlightText = (text) => {
  if (!text) return ''
  const q = tableFilters.value.search
  if (!q || String(q).trim().length === 0) return escapeHtml(text)
  try {
    const re = new RegExp(`(${escapeRegExp(q)})`, 'ig')
    return escapeHtml(text).replace(re, '<span class="match-highlight">$1</span>')
  } catch (e) {
    return escapeHtml(text)
  }
}
</script>

<template>
  <v-container fluid>
    <!-- Search and Add Buttons -->
    <v-row align="center" class="px-8">
      <v-col cols="12" md="6">
        <v-text-field
          v-model="tableFilters.search"
          variant="outlined"
          label="Search Subject"
          density="compact"
          append-outer-icon="mdi-magnify"
          clearable
          @click:clear="onSearchSubjects"
          @click:append="onSearchSubjects"
          @input="onSearchSubjects"
        ></v-text-field>
      </v-col>

      <v-col cols="12" class="mb-6" md="6">
        <v-btn variant="tonal" @click="onAdd" block>Add Subjects</v-btn>
      </v-col>
    </v-row>

    <!-- Subject Cards -->
    <v-row dense>
      <v-col
        v-for="subject in filteredSubjects"
        :key="subject.id"
        cols="12"
        sm="6"
        md="4"
        lg="3"
      >
        <v-card class="ma-3 pa-4 subject-card" min-height="250" min-width="225" color="green">
          <!-- Image (fills entire card) -->
          <div v-if="getImageSrc(subject) && !brokenImages[subject.id]" class="subject-image-wrapper">
            <img
              :src="getImageSrc(subject)"
              :alt="`subject image ${subject.id}`"
              class="subject-image"
              @error="onImageError(subject)"
            />
            <div class="subject-image-overlay" aria-hidden="true"></div>
            <button class="subject-image-open" @click.stop="openImageInNewTab(subject)" title="Open image in new tab">
              <v-icon small>mdi-open-in-new</v-icon>
            </button>
          </div>
          <div v-else-if="getImageSrc(subject) && brokenImages[subject.id]" class="subject-image-broken">
            <div class="subject-image-broken-inner">
              <p class="broken-text">Image failed to load</p>
              <a
                :href="getImageSrc(subject)"
                target="_blank"
                rel="noopener noreferrer"
                class="broken-link"
              >
                Open URL
              </a>
            </div>
          </div>

          <!-- Card Content -->
          <!-- Footer: contains readable text panel and actions (keeps text out of the main photo) -->
          <div class="subject-footer">
            <div class="subject-footer-text">
              <h3 class="font-weight-bold mb-1" v-html="highlightText(subject.name)"></h3>
              <div class="subject-meta mb-1">
                <span class="units">Units: <strong>{{ subject.units }}</strong></span>
              </div>
              <p class="subject-desc" v-html="highlightText(subject.description)"></p>
            </div>

            <div class="subject-footer-actions">
              <v-btn
                icon
                variant="elevated"
                density="comfortable"
                color="black"
                @click.stop="onUpdate(subject)"
              >
                <v-icon size="20">mdi-pencil</v-icon>
              </v-btn>
              <v-btn
                icon
                variant="elevated"
                density="comfortable"
                color="red"
                @click.stop="onDelete(subject.id)"
              >
                <v-icon size="20">mdi-delete</v-icon>
              </v-btn>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Dialogs -->
    <SubjectFormDialog
      v-model:is-dialog-visible="isDialogVisible"
      :item-data="itemData"
      :table-filters="tableFilters"
    />
    <ConfirmDialog
      v-model:is-dialog-visible="isConfirmDeleteDialog"
      title="Confirm Delete"
      text="Are you sure you want to delete this subject?"
      @confirm="onConfirmDelete"
    />
  </v-container>
</template>
<style scoped>
.v-card {
  box-shadow: 0 10px 10px rgba(0, 0, 0, 0.1);
  border-radius: 10px;
}
.v-row {
  row-gap: 10px;
}
/* Make subject image fill the entire card */
.subject-card{
  position: relative;
  overflow: hidden;
  padding: 0; /* remove extra padding so image can reach edges */
  border-radius: 10px;
}
.subject-image-wrapper{
  position: absolute;
  inset: 0; /* top:0; right:0; bottom:0; left:0; */
  width: 100%;
  height: 100%;
}
.subject-image{
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
/* subtle overlay to help text legibility */
.subject-image-overlay{
  position: absolute;
  inset: 0;
  background: linear-gradient(rgba(0,0,0,0.0), rgba(0,0,0,0.15));
  z-index: 1;
}
/* keep card content above the image */
.subject-card-content{
  position: relative;
  z-index: 2;
  color: #fff; /* ensure text contrasts on images */
  padding: 16px;
}
.v-card-actions{
  position: relative;
  z-index: 2;
}

/* debug / open image button */
.subject-image-open{
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 3;
  background: rgba(255,255,255,0.9);
  border: none;
  border-radius: 4px;
  padding: 4px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.subject-image-broken{
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
  background: linear-gradient(180deg, rgba(34,139,34,0.95), rgba(34,139,34,0.8));
}
.subject-image-broken-inner{
  text-align: center;
  color: #fff;
}
.broken-text{font-weight:600; margin:0 0 6px 0}
.broken-link{color:#fff; text-decoration:underline; cursor:pointer}

/* highlighted match style */
.match-highlight{
  background-color: #d4f7d4;
  color: #0b5f0b;
  padding: 2px 4px;
  border-radius: 3px;
}

/* Footer panel so text does not mix into the photo */
.subject-footer{
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 3;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  padding: 12px 14px;
  background: linear-gradient(180deg, rgba(0,0,0,0.0) 0%, rgba(0,0,0,0.6) 40%, rgba(0,0,0,0.75) 100%);
  color: #fff;
}
.subject-footer-text{max-width: calc(100% - 84px);} /* leave space for actions */
.subject-meta{font-size: 13px; opacity: 0.95}
.subject-desc{
  margin: 0;
  font-size: 13px;
  color: #f1f1f1;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}
.subject-footer-actions{
  display: flex;
  gap: 8px;
  align-items: center;
}

/* make action buttons slightly larger and easier to tap */
.subject-footer-actions .v-btn{transform: translateY(0);} 
</style>
