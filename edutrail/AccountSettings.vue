<template>
  <div class="account-settings-container">
    <!-- Header -->
    <v-card class="mb-6" elevation="0" color="green">
      <v-card-text class="white--text py-8">
        <div class="d-flex justify-space-between align-center">
          <div>
            <h1 class="text-h4 font-weight-bold mb-2">Account Settings</h1>
            <p class="text-subtitle1 white--text opacity-80">Manage your profile, security, and preferences</p>
          </div>
          <v-avatar size="80" class="bg-white">
            <span class="text-h5 green--text">{{ userInitials }}</span>
          </v-avatar>
        </div>
      </v-card-text>
    </v-card>

    <!-- Settings Tabs -->
    <v-tabs v-model="activeTab" class="mb-6" show-arrows color="green">
      <v-tab>
        <v-icon left>mdi-account</v-icon>
        Profile
      </v-tab>
      <v-tab>
        <v-icon left>mdi-lock</v-icon>
        Security
      </v-tab>
      <v-tab>
        <v-icon left>mdi-bell</v-icon>
        Notifications
      </v-tab>
    </v-tabs>

    <!-- Tab Contents -->
    <v-window v-model="activeTab">
      <!-- Profile Tab -->
      <v-window-item>
        <v-card elevation="2" class="rounded-lg">
          <v-card-title class="green--text d-flex align-center">
            <v-icon class="mr-2">mdi-account-circle</v-icon>
            Profile Information
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-6">
            <!-- Avatar Section -->
            <div class="mb-6">
              <h3 class="text-h6 mb-4">Profile Picture</h3>
              <div class="d-flex align-center gap-4">
                <v-avatar size="120" color="green" class="flex-shrink-0">
                  <span class="text-h4 white--text">{{ userInitials }}</span>
                </v-avatar>
                <div class="flex-grow-1">
                  <v-file-input
                    v-model="profilePicture"
                    accept="image/*"
                    prepend-icon="mdi-camera"
                    label="Upload new profile picture"
                    show-size
                    @change="onProfilePictureChange"
                  ></v-file-input>
                  <p class="text-caption grey--text mt-2">
                    Recommended: 600x600px PNG or JPG, max 2MB
                  </p>
                </div>
              </div>
            </div>

            <v-divider class="my-6"></v-divider>

            <!-- Profile Form -->
            <div class="mb-6">
              <h3 class="text-h6 mb-4">Personal Information</h3>
              <v-row>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="form.firstName"
                    label="First Name"
                    outlined
                    dense
                    prepend-icon="mdi-human"
                    :error="!!errors.firstName"
                    :error-messages="errors.firstName"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="form.lastName"
                    label="Last Name"
                    outlined
                    dense
                    prepend-icon="mdi-human"
                    :error="!!errors.lastName"
                    :error-messages="errors.lastName"
                  ></v-text-field>
                </v-col>
              </v-row>

              <v-row>
                <v-col cols="12">
                  <v-text-field
                    v-model="form.email"
                    label="Email Address"
                    type="email"
                    outlined
                    dense
                    prepend-icon="mdi-email"
                    readonly
                  ></v-text-field>
                  <p class="text-caption grey--text mt-2">Email cannot be changed</p>
                </v-col>
              </v-row>

              <v-row>
                <v-col cols="12">
                  <v-textarea
                    v-model="form.bio"
                    label="Bio (Optional)"
                    outlined
                    dense
                    rows="4"
                    prepend-icon="mdi-text"
                    placeholder="Tell us about yourself..."
                    :error="!!errors.bio"
                    :error-messages="errors.bio"
                  ></v-textarea>
                </v-col>
              </v-row>

              <v-row>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="form.phone"
                    label="Phone Number (Optional)"
                    outlined
                    dense
                    prepend-icon="mdi-phone"
                    :error="!!errors.phone"
                    :error-messages="errors.phone"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="form.location"
                    label="Location (Optional)"
                    outlined
                    dense
                    prepend-icon="mdi-map-marker"
                    :error="!!errors.location"
                    :error-messages="errors.location"
                  ></v-text-field>
                </v-col>
              </v-row>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2">
              <v-btn
                large
                color="green"
                dark
                @click="saveProfile"
                :loading="isSaving"
              >
                <v-icon left>mdi-check</v-icon>
                Save Changes
              </v-btn>
              <v-btn
                large
                outlined
                color="grey"
                @click="resetForm"
              >
                <v-icon left>mdi-refresh</v-icon>
                Cancel
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Security Tab -->
      <v-window-item>
        <v-card elevation="2" class="rounded-lg">
          <v-card-title class="green--text d-flex align-center">
            <v-icon class="mr-2">mdi-shield-account</v-icon>
            Security Settings
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-6">
            <!-- Change Password -->
            <div class="mb-8">
              <h3 class="text-h6 mb-4">Change Password</h3>
              
              <v-text-field
                v-model="passwordForm.currentPassword"
                label="Current Password"
                type="password"
                outlined
                dense
                prepend-icon="mdi-lock"
                class="mb-4"
                :error="!!passwordErrors.currentPassword"
                :error-messages="passwordErrors.currentPassword"
              ></v-text-field>

              <v-text-field
                v-model="passwordForm.newPassword"
                label="New Password"
                type="password"
                outlined
                dense
                prepend-icon="mdi-lock-plus"
                class="mb-4"
                :error="!!passwordErrors.newPassword"
                :error-messages="passwordErrors.newPassword"
              ></v-text-field>

              <v-text-field
                v-model="passwordForm.confirmPassword"
                label="Confirm New Password"
                type="password"
                outlined
                dense
                prepend-icon="mdi-lock-check"
                class="mb-4"
                :error="!!passwordErrors.confirmPassword"
                :error-messages="passwordErrors.confirmPassword"
              ></v-text-field>

              <v-alert type="info" outlined class="mb-4">
                <v-icon small left>mdi-information</v-icon>
                Password must be at least 8 characters with uppercase, lowercase, and numbers.
              </v-alert>

              <v-btn
                large
                color="green"
                dark
                @click="changePassword"
                :loading="isChangingPassword"
              >
                <v-icon left>mdi-check</v-icon>
                Update Password
              </v-btn>
            </div>

            <v-divider class="my-8"></v-divider>

            <!-- Active Sessions -->
            <div class="mb-8">
              <h3 class="text-h6 mb-4">Active Sessions</h3>
              <v-list class="transparent">
                <v-list-item>
                  <v-list-item-avatar>
                    <v-icon color="green">mdi-laptop-windows</v-icon>
                  </v-list-item-avatar>
                  <v-list-item-content>
                    <v-list-item-title>This Device</v-list-item-title>
                    <v-list-item-subtitle>Last active: Just now</v-list-item-subtitle>
                  </v-list-item-content>
                </v-list-item>
              </v-list>
              <v-btn
                outlined
                color="red"
                @click="logoutAllDevices"
              >
                <v-icon left>mdi-logout</v-icon>
                Log Out All Other Sessions
              </v-btn>
            </div>

            <v-divider class="my-8"></v-divider>

            <!-- Two-Factor Authentication -->
            <div>
              <h3 class="text-h6 mb-4">Two-Factor Authentication</h3>
              <v-alert type="warning" outlined class="mb-4">
                <v-icon small left>mdi-alert</v-icon>
                Two-factor authentication is currently disabled. Enable it for enhanced security.
              </v-alert>
              <v-btn
                large
                outlined
                color="green"
              >
                <v-icon left>mdi-shield-check</v-icon>
                Enable 2FA
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Notifications Tab -->
      <v-window-item>
        <v-card elevation="2" class="rounded-lg">
          <v-card-title class="green--text d-flex align-center">
            <v-icon class="mr-2">mdi-bell</v-icon>
            Notification Preferences
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-6">
            <h3 class="text-h6 mb-4">Email Notifications</h3>

            <v-list class="transparent">
              <v-list-item class="px-0">
                <v-list-item-content>
                  <v-list-item-title>Assignment Updates</v-list-item-title>
                  <v-list-item-subtitle>Get notified when assignments are created or updated</v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action>
                  <v-switch
                    v-model="notifications.assignmentUpdates"
                    color="green"
                  ></v-switch>
                </v-list-item-action>
              </v-list-item>

              <v-list-item class="px-0">
                <v-list-item-content>
                  <v-list-item-title>Project Due Dates</v-list-item-title>
                  <v-list-item-subtitle>Receive reminders for upcoming project deadlines</v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action>
                  <v-switch
                    v-model="notifications.projectReminders"
                    color="green"
                  ></v-switch>
                </v-list-item-action>
              </v-list-item>

              <v-list-item class="px-0">
                <v-list-item-content>
                  <v-list-item-title>Weekly Summary</v-list-item-title>
                  <v-list-item-subtitle>Receive a weekly digest of your activities</v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action>
                  <v-switch
                    v-model="notifications.weeklySummary"
                    color="green"
                  ></v-switch>
                </v-list-item-action>
              </v-list-item>

              <v-list-item class="px-0">
                <v-list-item-content>
                  <v-list-item-title>System Alerts</v-list-item-title>
                  <v-list-item-subtitle>Important notifications about your account security</v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action>
                  <v-switch
                    v-model="notifications.systemAlerts"
                    color="green"
                    disabled
                  ></v-switch>
                </v-list-item-action>
              </v-list-item>
            </v-list>

            <v-divider class="my-6"></v-divider>

            <v-btn
              large
              color="green"
              dark
              @click="saveNotifications"
              :loading="isSavingNotifications"
            >
              <v-icon left>mdi-check</v-icon>
              Save Preferences
            </v-btn>
          </v-card-text>
        </v-card>
      </v-window-item>
    </v-window>

    <!-- Alerts -->
    <v-snackbar
      v-model="alert.show"
      :color="alert.type"
      timeout="4000"
      top
    >
      {{ alert.message }}
      <template v-slot:action="{ attrs }">
        <v-btn color="white" text v-bind="attrs" @click="alert.show = false">
          Close
        </v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script>
export default {
  name: 'AccountSettings',
  data() {
    return {
      activeTab: 0,
      isSaving: false,
      isChangingPassword: false,
      isSavingNotifications: false,
      profilePicture: null,

      form: {
        firstName: 'Kristel',
        lastName: 'Mae Ocana',
        email: 'kristel@example.com',
        bio: '',
        phone: '',
        location: '',
      },

      passwordForm: {
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
      },

      notifications: {
        assignmentUpdates: true,
        projectReminders: true,
        weeklySummary: false,
        systemAlerts: true,
      },

      errors: {
        firstName: '',
        lastName: '',
        bio: '',
        phone: '',
        location: '',
      },

      passwordErrors: {
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
      },

      alert: {
        show: false,
        message: '',
        type: 'success',
      },
    };
  },

  computed: {
    userInitials() {
      return `${this.form.firstName.charAt(0)}${this.form.lastName.charAt(0)}`.toUpperCase();
    },
  },

  methods: {
    onProfilePictureChange() {
      if (this.profilePicture) {
        this.showAlert('Profile picture updated successfully!', 'success');
      }
    },

    saveProfile() {
      // Validate
      if (!this.form.firstName.trim()) {
        this.errors.firstName = 'First name is required';
        return;
      }
      if (!this.form.lastName.trim()) {
        this.errors.lastName = 'Last name is required';
        return;
      }

      this.isSaving = true;

      // Simulate API call
      setTimeout(() => {
        this.isSaving = false;
        this.showAlert('Profile updated successfully!', 'success');
      }, 2000);
    },

    resetForm() {
      this.form = {
        firstName: 'Kristel',
        lastName: 'Mae Ocana',
        email: 'kristel@example.com',
        bio: '',
        phone: '',
        location: '',
      };
      this.errors = {
        firstName: '',
        lastName: '',
        bio: '',
        phone: '',
        location: '',
      };
    },

    changePassword() {
      this.passwordErrors = {
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
      };

      if (!this.passwordForm.currentPassword) {
        this.passwordErrors.currentPassword = 'Current password is required';
        return;
      }

      if (!this.passwordForm.newPassword) {
        this.passwordErrors.newPassword = 'New password is required';
        return;
      }

      if (this.passwordForm.newPassword.length < 8) {
        this.passwordErrors.newPassword = 'Password must be at least 8 characters';
        return;
      }

      if (this.passwordForm.newPassword !== this.passwordForm.confirmPassword) {
        this.passwordErrors.confirmPassword = 'Passwords do not match';
        return;
      }

      this.isChangingPassword = true;

      // Simulate API call
      setTimeout(() => {
        this.isChangingPassword = false;
        this.passwordForm = {
          currentPassword: '',
          newPassword: '',
          confirmPassword: '',
        };
        this.showAlert('Password changed successfully!', 'success');
      }, 2000);
    },

    logoutAllDevices() {
      if (confirm('Are you sure? You will be logged out of all other devices.')) {
        this.showAlert('All other sessions have been logged out', 'success');
      }
    },

    saveNotifications() {
      this.isSavingNotifications = true;

      // Simulate API call
      setTimeout(() => {
        this.isSavingNotifications = false;
        this.showAlert('Notification preferences saved!', 'success');
      }, 1500);
    },

    showAlert(message, type = 'success') {
      this.alert = {
        show: true,
        message,
        type,
      };
    },
  },
};
</script>

<style scoped>
.account-settings-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}

.gap-4 {
  gap: 16px;
}

.gap-2 {
  gap: 8px;
}

.rounded-lg {
  border-radius: 12px;
}

.opacity-80 {
  opacity: 0.8;
}

::v-deep .v-tabs__nav {
  background-color: #f5f5f5;
  border-radius: 12px;
}

::v-deep .v-tab {
  text-transform: none;
  font-size: 16px;
  font-weight: 500;
}

::v-deep .v-window__container {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
</style>
