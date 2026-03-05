<script setup>
const props = defineProps(['formSuccessMessage', 'formErrorMessage', 'formErrorDetails'])
</script>

<template>
  <v-alert
    v-if="props.formSuccessMessage"
    :text="props.formSuccessMessage"
    title="Success!"
    type="success"
    variant="tonal"
    density="compact"
    border="start"
    closable
  ></v-alert>

  <!-- Error Alert -->
  <v-alert
    v-if="props.formErrorMessage"
    title="Oooops!"
    type="error"
    variant="tonal"
    density="compact"
    border="start"
    closable
  >
    <div>{{ props.formErrorMessage }}</div>
    <div v-if="props.formErrorDetails" style="white-space:pre-wrap; margin-top:4px; font-size:0.85rem;">
      {{ props.formErrorDetails }}
    </div>
  </v-alert>
</template>
