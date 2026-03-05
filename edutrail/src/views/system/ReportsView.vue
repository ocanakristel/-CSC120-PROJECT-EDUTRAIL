<!-- src/views/system/ReportsView.vue -->
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

// ───────────────── DATE FORMATTER (for display only) ─────────────────
const formatDate = (dateString) => {
  if (!dateString) return '—'

  const d = new Date(dateString)
  if (Number.isNaN(d.getTime())) {
    // fallback if it fails to parse
    return dateString
  }

  // Example output: "Dec 5, 2025"
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
    console.error('Error loading reports data:', err)
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

// ───────────────── REPORT METRICS ─────────────────
const totalAssignments = computed(() => userAssignments.value.length)
const completedAssignments = computed(
  () => userAssignments.value.filter((a) => a.status === 'finished').length,
)
const activeAssignments = computed(
  () => userAssignments.value.filter((a) => a.status !== 'finished').length,
)

const totalProjects = computed(() => userProjects.value.length)
const completedProjects = computed(
  () => userProjects.value.filter((p) => p.status === 'finished').length,
)
const activeProjects = computed(
  () => userProjects.value.filter((p) => p.status !== 'finished').length,
)

const totalTasks = computed(() => totalAssignments.value + totalProjects.value)
const completedTasks = computed(
  () => completedAssignments.value + completedProjects.value,
)

const completionRate = computed(() => {
  if (!totalTasks.value) return 0
  return Math.round((completedTasks.value / totalTasks.value) * 100)
})

// Overdue calculation
const today = new Date()
today.setHours(0, 0, 0, 0)

const overdueAssignments = computed(() =>
  userAssignments.value.filter((a) => {
    if (!a.due_date) return false
    const date = new Date(a.due_date)
    date.setHours(0, 0, 0, 0)
    return date < today && a.status !== 'finished'
  }),
)

const overdueProjects = computed(() =>
  userProjects.value.filter((p) => {
    if (!p.due_date) return false
    const date = new Date(p.due_date)
    date.setHours(0, 0, 0, 0)
    return date < today && p.status !== 'finished'
  }),
)

const totalOverdue = computed(
  () => overdueAssignments.value.length + overdueProjects.value.length,
)

// Top 5 items by nearest deadline
const topAssignments = computed(() =>
  [...userAssignments.value]
    .filter((a) => a.due_date)
    .sort((a, b) => new Date(a.due_date) - new Date(b.due_date))
    .slice(0, 5),
)

const topProjects = computed(() =>
  [...userProjects.value]
    .filter((p) => p.due_date)
    .sort((a, b) => new Date(a.due_date) - new Date(b.due_date))
    .slice(0, 5),
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
      <v-container fluid class="reports-container">
        <!-- Header Banner -->
        <div class="header-banner mb-5">
          <v-row align="center">
            <v-col cols="12" md="8">
              <h1 class="text-h4 font-weight-bold mb-2">📊 Reports</h1>
              <p class="text-subtitle-1 banner-text">
                Analyze your academic workload, completion trends, and overdue tasks.
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
          <div class="mt-2">Generating your reports...</div>
        </div>

        <template v-else>
          <!-- Top Stats Cards -->
          <v-row>
            <v-col cols="12" md="3" sm="6">
              <v-card class="stats-card" elevation="3">
                <v-card-text class="d-flex justify-space-between align-center">
                  <div>
                    <div class="text-overline text-muted">Total Tasks</div>
                    <div class="text-h4 font-weight-bold text-primary">
                      {{ totalTasks }}
                    </div>
                    <div class="text-caption">Projects + Assignments</div>
                  </div>
                  <v-avatar color="primary" size="48">
                    <v-icon color="white">mdi-format-list-checks</v-icon>
                  </v-avatar>
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" md="3" sm="6">
              <v-card class="stats-card" elevation="3">
                <v-card-text class="d-flex justify-space-between align-center">
                  <div>
                    <div class="text-overline text-muted">Completed</div>
                    <div class="text-h4 font-weight-bold text-success">
                      {{ completedTasks }}
                    </div>
                    <div class="text-caption">Overall finished</div>
                  </div>
                  <v-avatar color="success" size="48">
                    <v-icon color="white">mdi-check-circle-outline</v-icon>
                  </v-avatar>
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" md="3" sm="6">
              <v-card class="stats-card" elevation="3">
                <v-card-text class="d-flex justify-space-between align-center">
                  <div>
                    <div class="text-overline text-muted">Overdue</div>
                    <div class="text-h4 font-weight-bold" style="color: #ff5722">
                      {{ totalOverdue }}
                    </div>
                    <div class="text-caption">Needs attention</div>
                  </div>
                  <v-avatar color="deep-orange" size="48">
                    <v-icon color="white">mdi-alert-circle-outline</v-icon>
                  </v-avatar>
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" md="3" sm="6">
              <v-card class="stats-card" elevation="3">
                <v-card-text class="d-flex justify-space-between align-center">
                  <div>
                    <div class="text-overline text-muted">Completion Rate</div>
                    <div class="text-h4 font-weight-bold text-success">
                      {{ completionRate }}%
                    </div>
                    <div class="text-caption">Overall progress</div>
                  </div>
                  <v-avatar color="success" size="48">
                    <v-icon color="white">mdi-chart-line</v-icon>
                  </v-avatar>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Progress Bar Section -->
          <v-row class="mt-4">
            <v-col cols="12">
              <v-card class="content-card" elevation="2">
                <v-card-title>
                  <v-icon left color="success">mdi-chart-arc</v-icon>
                  Overall Progress
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <div class="d-flex align-center mb-2">
                    <span class="mr-4 font-weight-medium">
                      {{ completedTasks }} of {{ totalTasks }} tasks completed
                    </span>
                    <v-chip color="success" variant="tonal" size="small">
                      <v-icon left small>mdi-trending-up</v-icon>
                      {{ completionRate }}%
                    </v-chip>
                  </div>
                  <v-progress-linear
                    :model-value="completionRate"
                    height="14"
                    rounded
                    color="success"
                  />
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Projects & Assignments Tables -->
          <v-row class="mt-4">
            <!-- Projects -->
            <v-col cols="12" md="6">
              <v-card class="content-card" elevation="2">
                <v-card-title>
                  <v-icon left color="primary">mdi-briefcase-outline</v-icon>
                  Projects Overview
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <div class="text-caption text-muted mb-2">
                    Showing up to 5 projects, sorted by nearest deadline.
                  </div>

                  <v-table density="comfortable">
                    <thead>
                      <tr>
                        <th class="text-left">Description</th>
                        <th class="text-left">Due Date</th>
                        <th class="text-left">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="!topProjects.length">
                        <td colspan="3" class="text-center text-muted py-4">
                          No projects found.
                        </td>
                      </tr>
                      <tr v-for="project in topProjects" :key="project.id">
                        <td>{{ project.description || 'No description' }}</td>
                        <!-- ⭐ formatted date here -->
                        <td>
                          {{
                            project.due_date
                              ? formatDate(project.due_date)
                              : 'N/A'
                          }}
                        </td>
                        <td>
                          <v-chip
                            size="x-small"
                            :color="
                              project.status === 'finished'
                                ? 'success'
                                : 'deep-orange'
                            "
                            label
                          >
                            {{ project.status || 'unknown' }}
                          </v-chip>
                        </td>
                      </tr>
                    </tbody>
                  </v-table>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- Assignments -->
            <v-col cols="12" md="6">
              <v-card class="content-card" elevation="2">
                <v-card-title>
                  <v-icon left color="deep-orange">mdi-notebook-outline</v-icon>
                  Assignments Overview
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <div class="text-caption text-muted mb-2">
                    Showing up to 5 assignments, sorted by nearest deadline.
                  </div>

                  <v-table density="comfortable">
                    <thead>
                      <tr>
                        <th class="text-left">Description</th>
                        <th class="text-left">Due Date</th>
                        <th class="text-left">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="!topAssignments.length">
                        <td colspan="3" class="text-center text-muted py-4">
                          No assignments found.
                        </td>
                      </tr>
                      <tr v-for="assignment in topAssignments" :key="assignment.id">
                        <td>{{ assignment.description || 'No description' }}</td>
                        <!-- ⭐ formatted date here -->
                        <td>
                          {{
                            assignment.due_date
                              ? formatDate(assignment.due_date)
                              : 'N/A'
                          }}
                        </td>
                        <td>
                          <v-chip
                            size="x-small"
                            :color="
                              assignment.status === 'finished'
                                ? 'success'
                                : 'deep-orange'
                            "
                            label
                          >
                            {{ assignment.status || 'unknown' }}
                          </v-chip>
                        </td>
                      </tr>
                    </tbody>
                  </v-table>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Insights Alert -->
          <v-row class="mt-4 mb-2">
            <v-col>
              <v-alert
                type="info"
                variant="tonal"
                border="start"
                border-color="primary"
                icon="mdi-lightbulb-on-outline"
                prominent
              >
                <v-alert-title class="text-h6">📈 Study Insight</v-alert-title>
                <div class="text-body-2">
                  Aim for a completion rate above 80%. Try to clear overdue tasks first
                  before adding new ones to your schedule.
                </div>
              </v-alert>
            </v-col>
          </v-row>
        </template>
      </v-container>
    </template>
  </AppLayout>
</template>

<style scoped>
.reports-container {
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

.stats-card,
.content-card {
  border-radius: 12px !important;
  border: 1px solid rgba(37, 71, 65, 0.12);
  background-color: #ffffff !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08) !important;
  transition: all 0.3s ease;
}

.stats-card:hover,
.content-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.14) !important;
}

.text-muted {
  color: #4b7368;
}
</style>
