<script setup>
import AppLayout from '@/components/layout/AppLayout.vue'
import SideNavigation from '@/components/layout/navigation/SideNavigation.vue'

import { ref } from 'vue'
import ProjectList from './ProjectList.vue'

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
        <ProjectList></ProjectList>
      </v-container>
    </template>
  </AppLayout>
</template>

<style scoped>
/* Background of the whole page – same gradient as dashboard */
.background {
  background: #f8f8f8(
    120deg,
    #fff07c 0%,
    #80ff72 25%,
    #7ee8fa 60%,
    #eec0c6 100%
  );
  min-height: 100vh;
  padding: 24px;
}

/* HEADER: same as dashboard "Good Afternoon" */
.header-card {
  background: linear-gradient(135deg, #7ee8fa 0%, #80ff72 100%);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  margin-bottom: 24px;
  color: #ffffff;
}

.header-title {
  color: #ffffff;
  font-weight: 700;
  font-size: 2rem;
  letter-spacing: -0.5px;
}

/* Tabs inside header */
.custom-tabs {
  background: transparent;
}

.custom-tab {
  color: rgba(255, 255, 255, 0.9) !important;
  font-weight: 600;
  font-size: 0.9rem;
  text-transform: none;
  padding: 0 24px;
  min-height: 48px;
}

.custom-tab.v-tab--selected {
  color: #fff07c !important;
  border-bottom: 3px solid #fff07c;
  border-radius: 0;
}

.tab-label {
  font-weight: 600;
}

.tab-chip {
  font-weight: 700;
  min-width: 32px;
  height: 24px;
}

/* Create Project button */
.create-btn {
  background: #fff07c !important;
  color: #358600 !important;
  font-weight: 700;
  padding: 0 24px !important;
  border-radius: 10px;
  text-transform: none;
  font-size: 0.95rem;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

.create-btn:hover {
  background: #ffe95a !important;
  transform: translateY(-2px);
}

/* PROJECT CARDS */
.modern-project-card {
  border-radius: 14px;
  background: #ffffff !important;
  color: #1a1a1a;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border: 1px solid #dcdcdc;
}

.modern-project-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
}

.project-title {
  font-size: 1.15rem;
  font-weight: 600;
  color: #358600;
  line-height: 1.3;
}

.text-muted {
  color: #757575;
}

.no-checklist-msg {
  display: flex;
  align-items: center;
  padding: 10px 14px;
  background: #fff3e0;
  border-radius: 8px;
  border-left: 4px solid #ff9800;
}

.checklist-section {
  background: #f8f9fa;
  padding: 12px;
  border-radius: 10px;
  border: 1px solid #e0e0e0;
}

.finished-checklist {
  background: #f1f8f4;
  border: 1px solid #c8e6c9;
}

.progress-section {
  background: #fafafa;
  padding: 12px 14px;
  border-radius: 10px;
  border: 1px solid #eeeeee;
}

.detail-item {
  display: flex;
  align-items: center;
  margin-bottom: 4px;
}

.detail-item:last-child {
  margin-bottom: 0;
}
</style>
