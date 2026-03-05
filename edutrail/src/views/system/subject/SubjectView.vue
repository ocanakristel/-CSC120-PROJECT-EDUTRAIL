<script setup>
import AppLayout from '@/components/layout/AppLayout.vue'
import SideNavigation from '@/components/layout/navigation/SideNavigation.vue'

import { ref } from 'vue'
import { useDisplay } from 'vuetify'
import SubjectList from './SubjectList.vue'
const isDrawerVisible = ref(true)
</script>

<template>
  <AppLayout
    :is-with-app-bar-nav-icon="true"
    @is-drawer-visible="isDrawerVisible = !isDrawerVisible"
  >
    <template #navigation>
      <SideNavigation :is-drawer-visible="isDrawerVisible"></SideNavigation>
    </template>
    <template #content>
      <v-container fluid>
        <v-card>
          <v-row>
            <v-col cols="12" class="pa-5 pt-10">
              <v-card title="SUBJECTS LIST:" variant="tonal"></v-card>
            </v-col>
            <SubjectList></SubjectList>
          </v-row>
        </v-card>
      </v-container>
    </template>
  </AppLayout>
</template>
