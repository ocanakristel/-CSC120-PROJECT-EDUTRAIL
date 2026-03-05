<!-- src/views/system/CalendarView.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/components/layout/AppLayout.vue'
import SideNavigation from '@/components/layout/navigation/SideNavigation.vue'
import { api } from '@/utils/api'

import { useAssignmentsStore } from '@/stores/assignments'
import { useProjectsStore } from '@/stores/projects'

// ───────────────── STATE ─────────────────
const router = useRouter()
const isDrawerVisible = ref(true)
const isLoading = ref(true)
const currentUserId = ref(null)

const assignmentsStore = useAssignmentsStore()
const projectsStore = useProjectsStore()

// Filter mode: all / assignments / projects
const filterType = ref('all')

// ───────────────── DATE FORMATTER (for display) ─────────────────
const formatDate = (dateString) => {
  if (!dateString) return '—'

  const d = new Date(dateString)
  if (Number.isNaN(d.getTime())) {
    // fallback if parsing fails
    return dateString
  }

  // Example: "Dec 5, 2025"
  return d.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

// ───────────────── DATA LOADING ─────────────────
onMounted(async () => {
  isLoading.value = true
  try {
    const { data, error } = await api.auth.getUser()
    if (error || !data?.user) {
      console.error('No logged-in user, redirecting to login...', error?.message)
      router.replace('/')
      return
    }

    currentUserId.value = data.user.id

    await Promise.all([
      assignmentsStore.getAssignments(),
      projectsStore.getProjects(),
    ])
  } catch (err) {
    console.error('Error loading calendar data:', err)
  } finally {
    isLoading.value = false
  }
})

// ───────────────── FILTERED BY USER ─────────────────
const userAssignments = computed(() =>
  assignmentsStore.assignments.filter(
    (a) => !currentUserId.value || a.user_id === currentUserId.value,
  ),
)

const userProjects = computed(() =>
  projectsStore.projects.filter(
    (p) => !currentUserId.value || p.user_id === currentUserId.value,
  ),
)

// ───────────────── CALENDAR EVENTS ─────────────────
const allEvents = computed(() => {
  const upcomingAssignments = userAssignments.value
    .filter((a) => a.status !== 'finished')
    .map((a) => ({
      id: `A-${a.id}`,
      type: 'Assignment',
      description: a.description,
      due_date: a.due_date,
      due_time: a.due_time,
      status: a.status,
    }))

  const upcomingProjects = userProjects.value
    .filter((p) => p.status !== 'finished')
    .map((p) => ({
      id: `P-${p.id}`,
      type: 'Project',
      description: p.description,
      due_date: p.due_date,
      due_time: p.due_time,
      status: p.status,
    }))

  const combined = [...upcomingAssignments, ...upcomingProjects].filter(
    (e) => e.due_date && e.due_time,
  )

  // Use ISO due_date safely for sorting
  return combined.sort(
    (a, b) => new Date(a.due_date) - new Date(b.due_date),
  )
})

const filteredEvents = computed(() => {
  if (filterType.value === 'assignments') {
    return allEvents.value.filter((e) => e.type === 'Assignment')
  }
  if (filterType.value === 'projects') {
    return allEvents.value.filter((e) => e.type === 'Project')
  }
  return allEvents.value
})

const totalEvents = computed(() => allEvents.value.length)
const totalAssignments = computed(
  () => allEvents.value.filter((e) => e.type === 'Assignment').length,
)
const totalProjects = computed(
  () => allEvents.value.filter((e) => e.type === 'Project').length,
)
</script>

<template>
  <AppLayout
    :is-with-app-bar-nav-icon="true"
    @is-drawer-visible="isDrawerVisible = !isDrawerVisible"
  >
    <template #navigation>
      <SideNavigation :is-drawer-visible="isDrawerVisible" />
    </template>

    <template #content>
      <v-container fluid class="calendar-container">
        <!-- Header Banner -->
        <div class="header-banner mb-5">
          <v-row align="center">
            <v-col cols="12" md="8">
              <h1 class="text-h4 font-weight-bold mb-2">📅 Calendar</h1>
              <p class="text-subtitle-1 banner-text">
                View all your upcoming assignments and projects in one smart timeline.
              </p>
            </v-col>
            <v-col cols="12" md="4" class="d-flex justify-end align-center">
              <v-btn
                color="white"
                variant="flat"
                class="mr-2"
                @click="router.push({ name: 'dashboard' })"
              >
                <v-icon left>mdi-view-dashboard</v-icon>
                Back to Dashboard
              </v-btn>
            </v-col>
          </v-row>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="text-center py-10">
          <v-progress-circular indeterminate size="64" />
          <div class="mt-2">Loading your calendar...</div>
        </div>

        <template v-else>
          <v-row>
            <!-- Timeline column -->
            <v-col cols="12" md="8">
              <v-card class="calendar-card" elevation="2">
                <v-card-title class="d-flex justify-space-between align-center">
                  <div>
                    <v-icon left color="primary">mdi-calendar-month</v-icon>
                    Upcoming Schedule
                  </div>

                  <div class="d-flex align-center">
                    <span class="mr-2 text-caption text-muted">Filter:</span>
                    <v-btn-toggle
                      v-model="filterType"
                      mandatory
                      density="comfortable"
                    >
                      <v-btn value="all" size="small">All</v-btn>
                      <v-btn value="assignments" size="small">Assignments</v-btn>
                      <v-btn value="projects" size="small">Projects</v-btn>
                    </v-btn-toggle>
                  </div>
                </v-card-title>

                <v-divider />

                <v-card-text>
                  <div v-if="filteredEvents.length">
                    <div
                      v-for="event in filteredEvents"
                      :key="event.id"
                      class="event-item"
                    >
                      <div class="event-dot" />

                      <div class="event-content">
                        <div class="d-flex justify-space-between align-center mb-1">
                          <div class="font-weight-bold">
                            {{ event.description || 'No description' }}
                          </div>
                          <v-chip
                            size="x-small"
                            :color="event.type === 'Assignment' ? 'deep-orange' : 'primary'"
                            label
                          >
                            {{ event.type }}
                          </v-chip>
                        </div>

                        <div class="d-flex justify-space-between align-center">
                          <div class="text-caption text-muted">
                            <v-icon left size="16" color="grey">mdi-clock-outline</v-icon>
                            {{ formatDate(event.due_date) }} • {{ event.due_time }}
                          </div>
                          <div class="text-caption text-muted">
                            <v-icon left size="16" color="grey">mdi-flag-outline</v-icon>
                            Status: {{ event.status || 'unknown' }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-else class="text-center py-6 text-muted">
                    <v-icon size="48" color="grey-lighten-1">mdi-calendar-blank</v-icon>
                    <div class="mt-2">
                      No upcoming items found for this filter.
                    </div>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- Summary column -->
            <v-col cols="12" md="4">
              <v-card class="summary-card" elevation="2">
                <v-card-title>
                  <v-icon left color="success">mdi-chart-donut</v-icon>
                  Calendar Summary
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <div class="mb-4">
                    <div class="d-flex justify-space-between align-center mb-1">
                      <span>Total Upcoming Items</span>
                      <span class="font-weight-bold">{{ totalEvents }}</span>
                    </div>
                    <v-progress-linear
                      :model-value="totalEvents > 0 ? 100 : 0"
                      height="8"
                      color="primary"
                    />
                  </div>

                  <div class="mb-3">
                    <div class="d-flex justify-space-between align-center mb-1">
                      <span>Assignments</span>
                      <span class="font-weight-bold">{{ totalAssignments }}</span>
                    </div>
                    <v-progress-linear
                      :model-value="
                        totalEvents ? Math.round((totalAssignments / totalEvents) * 100) : 0
                      "
                      height="8"
                      color="deep-orange"
                    />
                  </div>

                  <div class="mb-3">
                    <div class="d-flex justify-space-between align-center mb-1">
                      <span>Projects</span>
                      <span class="font-weight-bold">{{ totalProjects }}</span>
                    </div>
                    <v-progress-linear
                      :model-value="
                        totalEvents ? Math.round((totalProjects / totalEvents) * 100) : 0
                      "
                      height="8"
                      color="primary"
                    />
                  </div>

                  <v-divider class="my-4" />

                  <v-alert
                    type="info"
                    variant="tonal"
                    border="start"
                    border-color="primary"
                    density="comfortable"
                  >
                    <div class="text-body-2">
                      Tip: Use your calendar daily to avoid last-minute cramming and missed
                      deadlines.
                    </div>
                  </v-alert>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </template>
      </v-container>
    </template>
  </AppLayout>
</template>

<style scoped>
.calendar-container {
  padding-block: 24px;
}

.header-banner {
  background: linear-gradient(135deg, #188221 0%, #2aa546 100%);
  color: #ffffff;
  padding: 32px;
  border-radius: 16px;
  box-shadow: 0 4px 16px rgba(24, 130, 33, 0.15);
}

.header-banner .text-h4 {
  color: #ffffff !important;
}

.banner-text {
  color: #e7ece5 !important;
}

.calendar-card,
.summary-card {
  border-radius: 12px !important;
  border: 1px solid rgba(37, 71, 65, 0.12);
  background-color: #ffffff !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08) !important;
  transition: all 0.3s ease;
}

.calendar-card:hover,
.summary-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
}

/* Timeline style */
.event-item {
  display: flex;
  position: relative;
  padding: 12px 0 12px 24px;
}

.event-item::before {
  content: '';
  position: absolute;
  left: 8px;
  top: 0;
  bottom: 0;
  width: 2px;
  background-color: #cfe5d5;
}

.event-dot {
  position: absolute;
  left: 2px;
  top: 18px;
  width: 12px;
  height: 12px;
  border-radius: 999px;
  background-color: #6ea89e;
  border: 2px solid #ffffff;
  box-shadow: 0 0 0 2px #cfe5d5;
}

.event-content {
  flex: 1;
  padding-left: 8px;
}

.text-muted {
  color: #4b7368;
}
</style>


