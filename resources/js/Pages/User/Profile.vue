<template>
    <v-app class="bg-user-gradient-light">
        <!-- App Bar -->
        <UserAppBar 
            title="Profile" 
            subtitle="Manage your account"
            :show-back="true"
            :notification-count="0"
            @go-back="goBack"
        />

        <v-main class="pb-20">
            <!-- Loading State -->
            <div v-if="loading" class="d-flex justify-center align-center" style="min-height: 60vh;">
                <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
            </div>

            <v-container v-else fluid class="profile-container">
                <!-- Profile Header Card -->
                <v-card 
                    class="mb-3 mb-sm-4 rounded-xl overflow-hidden" 
                    elevation="0"
                    color="white"
                >
                    <div class="profile-header-bg pa-4 pa-sm-6 text-center">
                        <!-- Avatar with Edit Button -->
                        <div class="position-relative d-inline-block">
                            <v-avatar size="88" class="elevation-4 avatar-ring avatar-mobile">
                                <v-img 
                                    v-if="profilePictureUrl" 
                                    :src="profilePictureUrl" 
                                    cover
                                ></v-img>
                                <span v-else class="text-h4 font-weight-bold text-white">
                                    {{ getInitials }}
                                </span>
                            </v-avatar>
                            <v-btn
                                icon
                                size="32"
                                color="primary"
                                class="position-absolute edit-avatar-btn elevation-3"
                                @click="openPhotoDialog"
                            >
                                <v-icon size="18">mdi-camera</v-icon>
                            </v-btn>
                        </div>
                        
                        <h2 class="text-h6 text-sm-h5 font-weight-bold mt-3 text-white">{{ fullName }}</h2>
                        <p class="text-caption text-sm-body-2 mb-0" style="color: #ffffff !important;">
                            {{ user?.email }}
                        </p>
                        
                        
                    </div>

                    <!-- Stats Row -->
                    <v-row no-gutters class="text-center py-2 py-sm-3 stats-row">
                        <v-col cols="4">
                            <div class="text-h6 text-sm-h5 font-weight-bold text-success">{{ stats.rescued }}</div>
                            <div class="text-caption text-grey">Rescued</div>
                        </v-col>
                        <v-divider vertical></v-divider>
                        <v-col cols="4">
                            <div class="text-h6 text-sm-h5 font-weight-bold text-warning">{{ stats.pending }}</div>
                            <div class="text-caption text-grey">Pending</div>
                        </v-col>
                        <v-divider vertical></v-divider>
                        <v-col cols="4">
                            <div class="text-h6 text-sm-h5 font-weight-bold text-primary">{{ stats.total }}</div>
                            <div class="text-caption text-grey">Total</div>
                        </v-col>
                    </v-row>
                </v-card>

                <!-- Personal Information -->
                <v-card class="mb-3 mb-sm-4 rounded-xl section-card" elevation="0">
                    <div class="pa-4">
                        <div class="d-flex align-center mb-4">
                            <v-avatar color="primary" size="32" class="mr-3">
                                <v-icon color="white" size="18">mdi-account</v-icon>
                            </v-avatar>
                            <span class="text-body-1 text-sm-subtitle-1 font-weight-bold">Personal Information</span>
                            <v-spacer />
                            <span class="text-caption text-grey-darken-1">* Required fields</span>
                        </div>
                        
                        <v-form ref="formRef" v-model="formValid">
                            <v-row dense>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editData.first_name"
                                        :class="{ 'required-error': missingMap.first_name }"
                                        label="First Name *"
                                        :rules="[rules.requiredField('First Name'), rules.nameOnly]"
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details="auto"
                                        class="mb-3 mobile-input"
                                        @keypress="preventInvalidNameChars"
                                        @input="(e) => { sanitizeNameField('first_name'); onFieldInput('first_name'); }"
                                        required
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editData.last_name"
                                        :class="{ 'required-error': missingMap.last_name }"
                                        label="Last Name *"
                                        :rules="[rules.requiredField('Last Name'), rules.nameOnly]"
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details="auto"
                                        class="mb-3 mobile-input"
                                        @keypress="preventInvalidNameChars"
                                        @input="(e) => { sanitizeNameField('last_name'); onFieldInput('last_name'); }"
                                        required
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="user.email"
                                        label="Email"
                                        readonly
                                        disabled
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details="auto"
                                        class="mb-3 non-editable-field mobile-input"
                                        prepend-inner-icon="mdi-email-outline"
                                        bg-color="grey-lighten-3"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="editData.phone_number"
                                        :class="{ 'required-error': missingMap.phone_number }"
                                        label="Phone Number *"
                                        :rules="[rules.requiredField('Phone Number'), rules.phoneNumber]"
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details="auto"
                                        class="mb-3 mobile-input"
                                        prepend-inner-icon="mdi-phone-outline"
                                        placeholder="09171234567"
                                        hint="Mobile number (e.g., 09171234567)"
                                        persistent-hint
                                        @input="(e) => { formatPhoneNumber('phone_number'); onFieldInput('phone_number'); }"
                                        required
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="editData.id_number"
                                        :class="{ 'required-error': missingMap.id_number }"
                                        label="ID Number *"
                                        :rules="[rules.requiredField('ID Number'), rules.idNumber]"
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details="auto"
                                        class="mb-3 mobile-input"
                                        prepend-inner-icon="mdi-card-account-details-outline"
                                        placeholder="Enter 9-digit ID number"
                                        :hint="isValidIdNumber ? (editData.id_number.startsWith('20') ? '✓ Student ID detected' : '✓ Faculty/Staff ID detected') : '9-digit ID number'"
                                        persistent-hint
                                        maxlength="9"
                                        @input="(e) => { formatIdNumber(); onFieldInput('id_number'); }"
                                        required
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-select
                                        v-model="editData.gender"
                                        :class="{ 'required-error': missingMap.gender }"
                                        label="Gender *"
                                        :items="genderOptions"
                                        :rules="[rules.requiredField('Gender')]"
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details="auto"
                                        class="mb-3 mobile-input"
                                        prepend-inner-icon="mdi-gender-male-female"
                                        @update:modelValue="() => onFieldInput('gender')"
                                        required
                                    ></v-select>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editData.date_of_birth"
                                        :class="{ 'required-error': missingMap.date_of_birth }"
                                        label="Date of Birth *"
                                        type="date"
                                        :rules="[rules.dateOfBirth]"
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details="auto"
                                        class="mb-3 mobile-input"
                                        prepend-inner-icon="mdi-calendar"
                                        @input="() => onFieldInput('date_of_birth')"
                                        required
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </v-form>
                        
                        <div v-if="hasPersonalChanges" class="d-flex flex-column flex-sm-row gap-2 mt-3">
                            <v-btn
                                color="grey"
                                variant="outlined"
                                @click="cancelEdit"
                                class="rounded-lg flex-grow-1 mobile-btn"
                                size="large"
                            >
                                Cancel
                            </v-btn>
                            <v-btn
                                color="primary"
                                variant="flat"
                                :loading="saving"
                                :disabled="!formValid"
                                @click="saveProfile"
                                class="rounded-lg flex-grow-1 mobile-btn"
                                size="large"
                            >
                                Save Changes
                            </v-btn>
                        </div>
                    </div>
                </v-card>

                <!-- Emergency Contact -->
                <v-card class="mb-3 mb-sm-4 rounded-xl section-card" elevation="0">
                    <v-expansion-panels flat v-model="emergencyPanel">
                        <v-expansion-panel>
                            <v-expansion-panel-title class="panel-title-mobile">
                                <div class="d-flex align-center w-100">
                                    <v-avatar color="error" size="32" class="mr-3">
                                        <v-icon color="white" size="18">mdi-phone-in-talk</v-icon>
                                    </v-avatar>
                                    <span class="text-body-1 text-sm-subtitle-1 font-weight-bold">Emergency Contact</span>
                                    <v-spacer />
                                    <span class="text-caption text-grey-darken-1">* Required fields</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text class="panel-content-mobile">
                                <v-row dense>
                                    <v-col cols="12">
                                        <v-text-field
                                            v-model="editData.emergency_contact_name"
                                            label="Contact Name *"
                                            :rules="[rules.requiredField('Contact Name'), rules.nameOnly]"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details="auto"
                                            class="mb-3 mobile-input"
                                            prepend-inner-icon="mdi-account-heart-outline"
                                            placeholder="Enter emergency contact name"
                                            @keypress="preventInvalidNameChars"
                                            @input="sanitizeNameField('emergency_contact_name')"
                                            required
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="editData.emergency_contact_phone"
                                            label="Contact Phone *"
                                            :rules="[rules.requiredField('Contact Phone'), rules.phoneNumber]"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details="auto"
                                            class="mb-3 mobile-input"
                                            prepend-inner-icon="mdi-phone-outline"
                                            placeholder="09171234567"
                                            hint="Mobile number (e.g., 09171234567)"
                                            persistent-hint
                                            @input="formatPhoneNumber('emergency_contact_phone')"
                                            required
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="editData.emergency_contact_relation"
                                            label="Relationship"
                                            :rules="[rules.nameOnly]"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details="auto"
                                            class="mb-3 mobile-input"
                                            prepend-inner-icon="mdi-account-tie-outline"
                                            placeholder="e.g., Parent, Spouse, Sibling"
                                            @keypress="preventInvalidNameChars"
                                            @input="sanitizeNameField('emergency_contact_relation')"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                                
                                <div v-if="hasEmergencyChanges" class="d-flex flex-column flex-sm-row gap-2 mt-2">
                                    <v-btn
                                        color="grey"
                                        variant="outlined"
                                        @click="cancelEditEmergency"
                                        class="rounded-lg flex-grow-1 mobile-btn"
                                        size="large"
                                    >
                                        Cancel
                                    </v-btn>
                                    <v-btn
                                        color="primary"
                                        variant="flat"
                                        :loading="savingEmergency"
                                        @click="saveEmergencyContact"
                                        class="rounded-lg flex-grow-1 mobile-btn"
                                        size="large"
                                    >
                                        Save Changes
                                    </v-btn>
                                </div>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card>

                <!-- Medical Information -->
                <v-card class="mb-3 mb-sm-4 rounded-xl section-card" elevation="0">
                    <v-expansion-panels flat v-model="medicalPanel">
                        <v-expansion-panel>
                            <v-expansion-panel-title class="panel-title-mobile">
                                <div class="d-flex align-center w-100">
                                    <v-avatar color="red" size="32" class="mr-3">
                                        <v-icon color="white" size="18">mdi-medical-bag</v-icon>
                                    </v-avatar>
                                    <span class="text-body-1 text-sm-subtitle-1 font-weight-bold">Medical Information</span>
                                    <v-spacer />
                                    <span class="text-caption text-grey-darken-1">* Required fields</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text class="panel-content-mobile">
                                <v-row dense>
                                    <v-col cols="12" sm="6">
                                        <v-select
                                            v-model="editData.blood_type"
                                            label="Blood Type"
                                            :items="['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown']"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details="auto"
                                            class="mb-3 mobile-input"
                                            clearable
                                            prepend-inner-icon="mdi-blood-bag"
                                        ></v-select>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-textarea
                                            v-model="editData.allergies"
                                            label="Allergies *"
                                            :rules="[rules.requiredField('Allergies')]"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details="auto"
                                            class="mb-3 mobile-input"
                                            rows="2"
                                            placeholder="List any known allergies..."
                                            prepend-inner-icon="mdi-allergy"
                                            required
                                        ></v-textarea>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-textarea
                                            v-model="editData.medical_conditions"
                                            label="Medical Conditions"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details="auto"
                                            rows="2"
                                            placeholder="List any medical conditions..."
                                            prepend-inner-icon="mdi-pill"
                                            class="mobile-input"
                                        ></v-textarea>
                                    </v-col>
                                </v-row>
                                
                                <div v-if="hasMedicalChanges" class="d-flex flex-column flex-sm-row gap-2 mt-2">
                                    <v-btn
                                        color="grey"
                                        variant="outlined"
                                        @click="cancelEditMedical"
                                        class="rounded-lg flex-grow-1 mobile-btn"
                                        size="large"
                                    >
                                        Cancel
                                    </v-btn>
                                    <v-btn
                                        color="primary"
                                        variant="flat"
                                        :loading="savingMedical"
                                        @click="saveMedicalInfo"
                                        class="rounded-lg flex-grow-1 mobile-btn"
                                        size="large"
                                    >
                                        Save Changes
                                    </v-btn>
                                </div>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card>

                <!-- System Feedback Section -->
                <v-card class="mb-3 mb-sm-4 rounded-xl section-card" elevation="0">
                    <v-expansion-panels flat v-model="systemFeedbackPanel">
                        <v-expansion-panel>
                            <v-expansion-panel-title class="panel-title-mobile">
                                <div class="d-flex align-center">
                                    <v-avatar color="deep-orange" size="32" class="mr-3">
                                        <v-icon color="white" size="18">mdi-message-alert-outline</v-icon>
                                    </v-avatar>
                                    <span class="text-body-1 text-sm-subtitle-1 font-weight-bold">Help Improve</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text class="panel-content-mobile">
                                <p class="text-body-2 text-grey-darken-1 mb-3">Report a bug or suggest an improvement to help us make the app better for everyone.</p>

                                <!-- Previous submissions -->
                                <div v-if="userSystemFeedbacks.length > 0" class="mb-3">
                                    <p class="text-caption font-weight-bold text-grey-darken-2 mb-2">Your Reports</p>
                                    <div
                                        v-for="fb in userSystemFeedbacks.slice(0, 3)"
                                        :key="fb.id"
                                        class="sf-history-item mb-2 pa-3 rounded-lg"
                                    >
                                        <div class="d-flex align-center justify-space-between">
                                            <div class="d-flex align-center">
                                                <v-icon size="16" :color="fb.category === 'bug' ? '#C62828' : '#1565C0'" class="mr-2">
                                                    {{ fb.category === 'bug' ? 'mdi-bug' : 'mdi-lightbulb-on-outline' }}
                                                </v-icon>
                                                <span class="text-caption font-weight-medium text-truncate" style="max-width: 180px;">{{ fb.area || fb.category }}</span>
                                            </div>
                                            <v-chip
                                                size="x-small"
                                                :color="getSfStatusColor(fb.status)"
                                                variant="tonal"
                                            >{{ getSfStatusLabel(fb.status) }}</v-chip>
                                        </div>
                                        <p class="text-caption text-grey mt-1 text-truncate">{{ fb.description }}</p>
                                    </div>
                                </div>

                                <v-btn
                                    block
                                    color="#3674B5"
                                    variant="tonal"
                                    @click="openSystemFeedbackDialog"
                                    class="rounded-lg mobile-btn"
                                    size="large"
                                >
                                    <v-icon start>mdi-send-outline</v-icon>
                                    Submit a Report
                                </v-btn>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card>

                <!-- Settings Section -->
                <v-card class="mb-3 mb-sm-4 rounded-xl section-card" elevation="0">
                    <v-expansion-panels flat v-model="settingsPanel">
                        <v-expansion-panel>
                            <v-expansion-panel-title class="panel-title-mobile">
                                <div class="d-flex align-center">
                                    <v-avatar color="grey" size="32" class="mr-3">
                                        <v-icon color="white" size="18">mdi-cog-outline</v-icon>
                                    </v-avatar>
                                    <span class="text-body-1 text-sm-subtitle-1 font-weight-bold">Settings</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text class="panel-content-mobile">
                                <v-list class="bg-transparent pa-0 settings-list">
                                    <!-- Security Dropdown -->
                                    <v-list-item class="px-2 py-3 rounded-lg mb-2 setting-item">
                                        <template v-slot:prepend>
                                            <v-avatar color="indigo" variant="tonal" size="36" class="mr-3">
                                                <v-icon size="20">mdi-shield-lock</v-icon>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title class="text-body-2 font-weight-medium">Security</v-list-item-title>
                                        <v-list-item-subtitle class="text-caption">Manage your account security</v-list-item-subtitle>
                                        <template v-slot:append>
                                            <v-btn
                                                size="small"
                                                color="primary"
                                                variant="tonal"
                                                @click="openChangePasswordDialog"
                                                class="text-caption"
                                            >
                                                <v-icon size="16" start>mdi-lock-outline</v-icon>
                                                Change Password
                                            </v-btn>
                                        </template>
                                    </v-list-item>
                                    <v-list-item class="px-2 py-3 rounded-lg setting-item">
                                        <template v-slot:prepend>
                                            <v-avatar color="grey-darken-1" variant="tonal" size="36" class="mr-3">
                                                <v-icon size="20">mdi-theme-light-dark</v-icon>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title class="text-body-2 font-weight-medium">Dark Mode</v-list-item-title>
                                        <v-list-item-subtitle class="text-caption">Easier on eyes at night</v-list-item-subtitle>
                                        <template v-slot:append>
                                            <v-switch
                                                v-model="settings.darkMode"
                                                color="primary"
                                                hide-details
                                                inset
                                                @change="updateSetting('DarkMode')"
                                            />
                                        </template>
                                    </v-list-item>
                                </v-list>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card>

                <!-- Logout Button -->
                <v-btn
                    block
                    color="#D32F2F"
                    variant="flat"
                    size="x-large"
                    class="rounded-xl mb-4 logout-btn text-white"
                    @click="showLogoutDialog = true"
                >
                    <v-icon start color="white">mdi-logout</v-icon>
                    <span style="color: white !important;">Logout</span>
                </v-btn>

               
            </v-container>

        </v-main>

        <!-- Bottom Navigation -->
        <UserBottomNav :notification-count="0" :message-count="unreadCount" />

        <!-- Logout Confirmation Dialog -->
        <v-dialog v-model="showLogoutDialog" max-width="340" persistent>
            <v-card class="rounded-xl">
                <v-card-text class="text-center pa-6">
                    <v-icon size="64" color="error" class="mb-4">mdi-logout-variant</v-icon>
                    <h3 class="text-h6 font-weight-bold mb-2">Logout</h3>
                    <p class="text-body-2 text-grey mb-0">Are you sure you want to logout?</p>
                </v-card-text>
                <v-card-actions class="pa-4 pt-0">
                    <v-row dense>
                        <v-col cols="6">
                            <v-btn
                                block
                                variant="outlined"
                                color="grey"
                                @click="showLogoutDialog = false"
                                class="rounded-lg"
                            >
                                Cancel
                            </v-btn>
                        </v-col>
                        <v-col cols="6">
                            <v-btn
                                block
                                color="error"
                                variant="flat"
                                :loading="loggingOut"
                                @click="handleLogout"
                                class="rounded-lg"
                            >
                                Logout
                            </v-btn>
                        </v-col>
                    </v-row>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Photo Upload Dialog -->
        <v-dialog v-model="photoDialog" max-width="340" persistent>
            <v-card class="rounded-xl">
                <v-card-title class="d-flex align-center pa-4">
                    <span class="text-subtitle-1 font-weight-bold">Profile Photo</span>
                    <v-spacer></v-spacer>
                    <v-btn icon size="small" variant="text" @click="cancelPhotoUpload">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-divider></v-divider>
                
                <v-card-text class="text-center pa-4">
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        style="display: none;"
                        @change="handleFileSelect"
                    />
                    
                    <v-avatar size="120" class="mb-4 elevation-2">
                        <v-img 
                            v-if="previewUrl || profilePictureUrl"
                            :src="previewUrl || profilePictureUrl" 
                            cover
                        ></v-img>
                        <span v-else class="text-h3 font-weight-bold">{{ getInitials }}</span>
                    </v-avatar>
                    
                    <v-btn
                        block
                        variant="outlined"
                        color="primary"
                        class="mb-2 rounded-lg"
                        @click="triggerFileInput"
                    >
                        <v-icon start>mdi-camera</v-icon>
                        {{ previewUrl ? 'Change Photo' : 'Select Photo' }}
                    </v-btn>
                    
                    <v-btn
                        v-if="profilePictureUrl && !previewUrl"
                        block
                        variant="outlined"
                        color="error"
                        class="rounded-lg"
                        @click="confirmDeletePhoto"
                    >
                        <v-icon start>mdi-delete</v-icon>
                        Remove Photo
                    </v-btn>
                </v-card-text>
                
                <v-card-actions v-if="selectedFile" class="pa-4 pt-0">
                    <v-btn
                        block
                        color="primary"
                        variant="flat"
                        :loading="uploadingPhoto"
                        @click="uploadPhoto"
                        class="rounded-lg"
                    >
                        <v-icon start>mdi-check</v-icon>
                        Save Photo
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Photo Confirmation -->
        <v-dialog v-model="deletePhotoDialog" max-width="320" persistent>
            <v-card class="rounded-xl">
                <v-card-text class="text-center pa-6">
                    <v-icon size="48" color="error" class="mb-3">mdi-delete-alert</v-icon>
                    <h3 class="text-subtitle-1 font-weight-bold mb-2">Remove Photo?</h3>
                    <p class="text-body-2 text-grey mb-0">This action cannot be undone.</p>
                </v-card-text>
                <v-card-actions class="pa-4 pt-0">
                    <v-row dense>
                        <v-col cols="6">
                            <v-btn
                                block
                                variant="outlined"
                                color="grey"
                                @click="deletePhotoDialog = false"
                                class="rounded-lg"
                            >
                                Cancel
                            </v-btn>
                        </v-col>
                        <v-col cols="6">
                            <v-btn
                                block
                                color="error"
                                variant="flat"
                                :loading="deletingPhoto"
                                @click="deletePhoto"
                                class="rounded-lg"
                            >
                                Remove
                            </v-btn>
                        </v-col>
                    </v-row>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Change Password Dialog - OTP Based (ChangePassword.vue style) -->
        <v-dialog v-model="passwordDialog" max-width="440" persistent>
            <v-card rounded="xl" class="pwd-dialog-card" elevation="8">
                <!-- Gradient Header -->
                <div class="pwd-card-header">
                    <div class="pwd-header-icon-wrap">
                        <v-icon size="36" color="white">mdi-shield-lock</v-icon>
                    </div>
                    <h1 class="pwd-header-title">Change Password</h1>
                    <p class="pwd-header-subtitle">Verify your identity to update your password</p>
                    <v-btn icon variant="text" size="small" class="pwd-close-btn" @click="closePasswordDialog" :disabled="sendingOtp || verifyingOtp || changingPassword">
                        <v-icon color="white" size="20">mdi-close</v-icon>
                    </v-btn>
                </div>

                <v-card-text class="pwd-card-body pa-6 pa-sm-8">
                    <!-- Success State -->
                    <div v-if="passwordComplete" class="text-center py-4">
                        <div class="pwd-success-check-wrap mb-4">
                            <div class="pwd-success-check-circle">
                                <v-icon size="48" color="white">mdi-check</v-icon>
                            </div>
                        </div>
                        <h2 class="text-h5 font-weight-bold mb-2" style="color: #13294B;">Password Updated!</h2>
                        <p class="text-body-2 text-medium-emphasis mb-6">
                            Your password has been changed successfully.
                        </p>
                        <v-btn
                            size="large"
                            color="#3674B5"
                            @click="closePasswordDialog"
                            class="pwd-action-btn px-10"
                            rounded="lg"
                            block
                        >
                            <v-icon start>mdi-check-circle</v-icon>
                            Done
                        </v-btn>
                    </div>

                    <!-- Step 1: Request OTP -->
                    <div v-else-if="passwordStep === 1" class="text-center">
                        <v-icon size="56" color="primary" class="mb-3">mdi-email-send-outline</v-icon>
                        <p class="text-body-2 text-medium-emphasis mb-1">We'll send a verification code to</p>
                        <p class="text-body-2 font-weight-bold mb-6" style="color: #3674B5;">{{ user.email }}</p>

                        <v-alert v-if="passwordError" type="error" variant="tonal" class="mb-4 text-left" closable @click:close="passwordError = ''">
                            {{ passwordError }}
                        </v-alert>

                        <v-btn
                            block
                            size="large"
                            color="#3674B5"
                            :loading="sendingOtp"
                            @click="sendPasswordOtp"
                            class="pwd-action-btn"
                            rounded="lg"
                        >
                            <v-icon start>mdi-send</v-icon>
                            Send Verification Code
                        </v-btn>
                        <v-btn variant="text" color="grey" block size="small" class="mt-3 text-none" @click="closePasswordDialog">
                            Cancel
                        </v-btn>
                    </div>

                    <!-- Step 2: Enter OTP -->
                    <div v-else-if="passwordStep === 2">
                        <div class="text-center mb-5">
                            <v-icon size="48" color="primary" class="mb-2">mdi-email-check-outline</v-icon>
                            <p class="text-body-2 text-medium-emphasis">Enter the 6-digit code sent to</p>
                            <p class="text-body-2 font-weight-bold" style="color: #3674B5;">{{ user.email }}</p>
                        </div>

                        <v-alert v-if="passwordError" type="error" variant="tonal" class="mb-4 text-left" closable @click:close="passwordError = ''">
                            {{ passwordError }}
                        </v-alert>

                        <div class="pwd-otp-container mb-4">
                            <v-otp-input
                                v-model="otpCode"
                                length="6"
                                variant="outlined"
                                :disabled="verifyingOtp"
                                class="pwd-otp-input-custom"
                            />
                        </div>

                        <div class="text-center mb-5">
                            <span class="text-caption text-medium-emphasis">Didn't receive it? </span>
                            <v-btn
                                variant="text"
                                color="#3674B5"
                                size="small"
                                density="compact"
                                @click="sendPasswordOtp"
                                :disabled="resendCountdown > 0 || sendingOtp"
                                class="text-none"
                            >
                                {{ resendCountdown > 0 ? `Resend in ${resendCountdown}s` : 'Resend Code' }}
                            </v-btn>
                        </div>

                        <v-btn
                            block
                            size="large"
                            color="#3674B5"
                            :loading="verifyingOtp"
                            :disabled="otpCode.length !== 6"
                            @click="verifyPasswordOtp"
                            class="pwd-action-btn"
                            rounded="lg"
                        >
                            <v-icon start>mdi-check-circle</v-icon>
                            Verify Code
                        </v-btn>
                        <v-btn variant="text" color="grey" block size="small" class="mt-3 text-none" @click="passwordStep = 1; otpCode = ''">
                            <v-icon start size="16">mdi-arrow-left</v-icon>
                            Go Back
                        </v-btn>
                    </div>

                    <!-- Step 3: Enter New Password -->
                    <div v-else-if="passwordStep === 3">
                        <v-alert v-if="passwordError" type="error" variant="tonal" class="mb-4" closable @click:close="passwordError = ''">
                            {{ passwordError }}
                        </v-alert>

                        <v-form ref="passwordFormRef" v-model="passwordFormValid">
                            <label class="pwd-field-label">New Password</label>
                            <v-text-field
                                v-model="passwordData.new_password"
                                :type="showNewPassword ? 'text' : 'password'"
                                placeholder="Enter new password"
                                variant="outlined"
                                :append-inner-icon="showNewPassword ? 'mdi-eye' : 'mdi-eye-off'"
                                @click:append-inner="showNewPassword = !showNewPassword"
                                class="mb-1 pwd-password-field"
                                hide-details
                                density="comfortable"
                                rounded="lg"
                                :disabled="changingPassword"
                            />

                            <!-- Password Strength Bar -->
                            <div class="pwd-strength-bar-wrap mt-2 mb-4" v-if="passwordData.new_password.length > 0">
                                <div class="pwd-strength-bar-track">
                                    <div
                                        class="pwd-strength-bar-fill"
                                        :style="{ width: pwdStrength + '%', background: pwdStrengthGradient }"
                                    ></div>
                                </div>
                                <div class="d-flex justify-space-between align-center mt-1">
                                    <span class="text-caption" :style="{ color: pwdStrengthColor }">{{ pwdStrengthText }}</span>
                                    <span class="text-caption text-medium-emphasis">{{ pwdStrength }}%</span>
                                </div>
                            </div>

                            <!-- Password Requirements (2-col grid) -->
                            <div class="pwd-requirements-list mb-5" v-if="passwordData.new_password.length > 0">
                                <div class="pwd-req-item" :class="{ met: passwordChecks.length }">
                                    <v-icon size="16" :color="passwordChecks.length ? '#3674B5' : '#ccc'">
                                        {{ passwordChecks.length ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                    </v-icon>
                                    <span>At least 8 characters</span>
                                </div>
                                <div class="pwd-req-item" :class="{ met: passwordChecks.uppercase }">
                                    <v-icon size="16" :color="passwordChecks.uppercase ? '#3674B5' : '#ccc'">
                                        {{ passwordChecks.uppercase ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                    </v-icon>
                                    <span>One uppercase letter</span>
                                </div>
                                <div class="pwd-req-item" :class="{ met: passwordChecks.lowercase }">
                                    <v-icon size="16" :color="passwordChecks.lowercase ? '#3674B5' : '#ccc'">
                                        {{ passwordChecks.lowercase ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                    </v-icon>
                                    <span>One lowercase letter</span>
                                </div>
                                <div class="pwd-req-item" :class="{ met: passwordChecks.number }">
                                    <v-icon size="16" :color="passwordChecks.number ? '#3674B5' : '#ccc'">
                                        {{ passwordChecks.number ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                    </v-icon>
                                    <span>One number</span>
                                </div>
                                <div class="pwd-req-item" :class="{ met: passwordChecks.special }">
                                    <v-icon size="16" :color="passwordChecks.special ? '#3674B5' : '#ccc'">
                                        {{ passwordChecks.special ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                    </v-icon>
                                    <span>One special character</span>
                                </div>
                            </div>

                            <label class="pwd-field-label">Confirm Password</label>
                            <v-text-field
                                v-model="passwordData.confirm_password"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                placeholder="Confirm new password"
                                variant="outlined"
                                :append-inner-icon="showConfirmPassword ? 'mdi-eye' : 'mdi-eye-off'"
                                @click:append-inner="showConfirmPassword = !showConfirmPassword"
                                class="pwd-password-field"
                                hide-details
                                density="comfortable"
                                rounded="lg"
                                :disabled="changingPassword"
                            />

                            <!-- Password match indicator -->
                            <div v-if="passwordData.confirm_password && passwordData.confirm_password.length > 0" class="mt-2 mb-5">
                                <div class="pwd-req-item" :class="{ met: passwordData.confirm_password === passwordData.new_password }">
                                    <v-icon size="16" :color="passwordData.confirm_password === passwordData.new_password ? '#3674B5' : '#ef5350'">
                                        {{ passwordData.confirm_password === passwordData.new_password ? 'mdi-check-circle' : 'mdi-close-circle' }}
                                    </v-icon>
                                    <span :style="{ color: passwordData.confirm_password === passwordData.new_password ? '#3674B5' : '#ef5350' }">
                                        {{ passwordData.confirm_password === passwordData.new_password ? 'Passwords match' : 'Passwords do not match' }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="mb-5"></div>

                            <v-btn
                                block
                                size="large"
                                color="#3674B5"
                                :loading="changingPassword"
                                :disabled="!isPasswordValid || !passwordData.confirm_password || passwordData.confirm_password !== passwordData.new_password"
                                @click="changePassword"
                                class="pwd-action-btn"
                                rounded="lg"
                            >
                                <v-icon start>mdi-lock-check</v-icon>
                                Update Password
                            </v-btn>
                            <v-btn variant="text" color="grey" block size="small" class="mt-3 text-none" @click="passwordStep = 2; otpCode = ''" :disabled="changingPassword">
                                <v-icon start size="16">mdi-arrow-left</v-icon>
                                Go Back
                            </v-btn>
                        </v-form>
                    </div>
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- System Feedback Dialog -->
        <v-dialog v-model="systemFeedbackDialog" max-width="460" persistent>
            <v-card class="sf-dialog-card" elevation="4">
                <!-- Header -->
                <div class="sf-dialog-header">
                    <div class="sf-dialog-header-content">
                        <v-icon size="22" color="white" class="mr-2">mdi-message-alert-outline</v-icon>
                        <div>
                            <h3 class="sf-dialog-title">Report &amp; Feedback</h3>
                            <p class="sf-dialog-subtitle">Help us improve PinPointMe</p>
                        </div>
                    </div>
                    <v-btn icon variant="text" size="x-small" @click="closeSystemFeedbackDialog">
                        <v-icon size="18" color="rgba(255,255,255,0.8)">mdi-close</v-icon>
                    </v-btn>
                </div>

                <div class="sf-dialog-body">
                    <v-form ref="sfFormRef" v-model="sfFormValid">
                        <!-- Category Toggle -->
                        <p class="sf-field-label">What would you like to do?</p>
                        <v-btn-toggle
                            v-model="sfForm.category"
                            mandatory
                            color="#3674B5"
                            rounded="lg"
                            density="comfortable"
                            class="sf-category-toggle mb-4"
                        >
                            <v-btn value="bug" class="sf-cat-btn">
                                <v-icon start size="18">mdi-bug</v-icon>
                                Report a Bug
                            </v-btn>
                            <v-btn value="improvement" class="sf-cat-btn">
                                <v-icon start size="18">mdi-lightbulb-on-outline</v-icon>
                                Suggest Improvement
                            </v-btn>
                        </v-btn-toggle>

                        <!-- Area Dropdown -->
                        <p class="sf-field-label">Related area</p>
                        <v-select
                            v-model="sfForm.area"
                            :items="sfAreaOptions"
                            item-title="label"
                            item-value="value"
                            :placeholder="sfForm.category === 'bug' ? 'Where did the issue occur?' : 'Which feature?'"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            color="#3674B5"
                            hide-details="auto"
                            :rules="[v => !!v || 'Please select an area']"
                            class="mb-4"
                            clearable
                        >
                            <template v-slot:item="{ item, props }">
                                <v-list-item v-bind="props">
                                    <template v-slot:prepend>
                                        <v-icon size="18" color="#3674B5">{{ getSfAreaIcon(item.value) }}</v-icon>
                                    </template>
                                </v-list-item>
                            </template>
                        </v-select>

                        <!-- Description -->
                        <p class="sf-field-label">{{ sfForm.category === 'bug' ? 'Describe the issue' : 'Describe your suggestion' }}</p>
                        <v-textarea
                            v-model="sfForm.description"
                            :placeholder="sfForm.category === 'bug' ? 'What happened? What did you expect to happen instead?' : 'How would your suggestion improve the app?'"
                            variant="outlined"
                            density="comfortable"
                            rows="4"
                            auto-grow
                            max-rows="8"
                            maxlength="3000"
                            counter="3000"
                            rounded="lg"
                            color="#3674B5"
                            hide-details="auto"
                            :rules="[v => !!v || 'Description is required', v => (v && v.length >= 10) || 'Min 10 characters']"
                            class="mb-4"
                        />

                        <!-- Upload Media -->
                        <p class="sf-field-label">Attach screenshot or proof <span class="text-grey">(optional)</span></p>
                        <div
                            class="sf-upload-area mb-4"
                            @click="triggerSfFileInput"
                            @dragover.prevent
                            @drop.prevent="handleSfFileDrop"
                        >
                            <input
                                ref="sfFileInputRef"
                                type="file"
                                accept="image/*,video/mp4,video/mov,application/pdf"
                                style="display: none;"
                                @change="handleSfFileSelect"
                            />
                            <div v-if="!sfPreviewUrl" class="sf-upload-placeholder">
                                <v-icon size="36" color="#9E9E9E">mdi-cloud-upload-outline</v-icon>
                                <p class="text-caption text-grey mt-1">Tap to upload or drag & drop</p>
                                <p class="text-caption text-grey-lighten-1" style="font-size: 0.7rem;">JPG, PNG, GIF, MP4, PDF — max 10MB</p>
                            </div>
                            <div v-else class="sf-upload-preview">
                                <v-img v-if="sfPreviewIsImage" :src="sfPreviewUrl" max-height="120" contain class="rounded-lg" />
                                <div v-else class="d-flex align-center">
                                    <v-icon size="24" color="#3674B5" class="mr-2">mdi-file-check</v-icon>
                                    <span class="text-caption font-weight-medium">{{ sfSelectedFile?.name }}</span>
                                </div>
                                <v-btn
                                    icon
                                    size="x-small"
                                    color="error"
                                    variant="tonal"
                                    class="sf-upload-remove"
                                    @click.stop="removeSfFile"
                                >
                                    <v-icon size="14">mdi-close</v-icon>
                                </v-btn>
                            </div>
                        </div>

                        <!-- Submit -->
                        <v-btn
                            block
                            variant="flat"
                            color="#3674B5"
                            size="large"
                            rounded="lg"
                            :loading="submittingSf"
                            :disabled="!sfFormValid"
                            class="sf-submit-btn"
                            @click="submitSystemFeedbackForm"
                        >
                            <v-icon start>mdi-send</v-icon>
                            Submit Report
                        </v-btn>
                    </v-form>
                </div>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar
            v-model="snackbar.show"
            :color="snackbar.color"
            :timeout="3000"
            location="top"
        >
            {{ snackbar.message }}
        </v-snackbar>


    </v-app>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { getCurrentUser, updateUser, uploadProfilePicture, deleteProfilePicture, getProfilePictureUrl, getUserRescueHistory, submitSystemFeedback, getUserSystemFeedbacks } from '@/Composables/useApi';
import { useUnreadMessages } from '@/Composables/useUnreadMessages';
import { useDarkMode } from '@/Composables/useDarkMode';
import { setUserActiveStatus } from '@/Utilities/firebase';
import UserAppBar from '@/Components/Pages/User/Menu/UserAppBar.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';


const { isDark, set: setDarkMode } = useDarkMode();

// Get Inertia page for auth
const page = usePage();
const authUser = computed(() => page.props?.auth?.user);

// Unread messages count for bottom nav
const { unreadCount } = useUnreadMessages();

// Refs
const formRef = ref(null);
const passwordFormRef = ref(null);
const fileInput = ref(null);

// State
const loading = ref(true);
const saving = ref(false);
const changingPassword = ref(false);
const uploadingPhoto = ref(false);
const deletingPhoto = ref(false);
const loggingOut = ref(false);
const isEditing = ref(false);
const formValid = ref(true);
const passwordFormValid = ref(false);
const showLogoutDialog = ref(false);
const photoDialog = ref(false);
const deletePhotoDialog = ref(false);

// Password change OTP flow
const passwordDialog = ref(false);
const passwordStep = ref(1); // 1, 2, 3 for request, otp, password
const passwordComplete = ref(false);
const passwordError = ref('');
const otpCode = ref('');
const verificationToken = ref('');
const sendingOtp = ref(false);
const verifyingOtp = ref(false);
const resendCountdown = ref(0);
let countdownInterval = null;

// Separate editing states for Emergency Contact and Medical sections
const isEditingEmergency = ref(false);
const isEditingMedical = ref(false);
const savingEmergency = ref(false);
const savingMedical = ref(false);

// Panel states
const emergencyPanel = ref(null);
const medicalPanel = ref(null);
const historyPanel = ref(null);
const settingsPanel = ref(null);
const securityPanel = ref(null);
const systemFeedbackPanel = ref(null);

// System Feedback state
const systemFeedbackDialog = ref(false);
const sfFormRef = ref(null);
const sfFormValid = ref(false);
const submittingSf = ref(false);
const sfFileInputRef = ref(null);
const sfSelectedFile = ref(null);
const sfPreviewUrl = ref(null);
const sfPreviewIsImage = ref(false);
const userSystemFeedbacks = ref([]);

const sfForm = reactive({
    category: 'bug',
    area: null,
    description: '',
});

const sfAreaOptions = [
    { label: 'QR Scanner', value: 'Scanner' },
    { label: 'Voice Command', value: 'Voice Command' },
    { label: 'Account / Profile', value: 'Account' },
    { label: 'Notifications', value: 'Notifications' },
    { label: 'Rescue Request', value: 'Rescue Request' },
    { label: 'Chat / Messaging', value: 'Chat' },
    { label: 'Location Tracking', value: 'Location' },
    { label: 'Map / Navigation', value: 'Map' },
    { label: 'Login / Registration', value: 'Login' },
    { label: 'Other', value: 'Other' },
];

const genderOptions = ['Male', 'Female', 'Other', 'Prefer not to say'];

const getSfAreaIcon = (area) => {
    const icons = {
        'Scanner': 'mdi-qrcode-scan',
        'Voice Command': 'mdi-microphone',
        'Account': 'mdi-account-cog',
        'Notifications': 'mdi-bell-outline',
        'Rescue Request': 'mdi-lifebuoy',
        'Chat': 'mdi-chat-outline',
        'Location': 'mdi-crosshairs-gps',
        'Map': 'mdi-map-marker',
        'Login': 'mdi-login',
        'Other': 'mdi-dots-horizontal',
    };
    return icons[area] || 'mdi-help-circle-outline';
};

const getSfStatusColor = (status) => {
    const colors = { open: 'warning', in_review: 'info', resolved: 'success', closed: 'grey' };
    return colors[status] || 'grey';
};

const getSfStatusLabel = (status) => {
    const labels = { open: 'Open', in_review: 'In Review', resolved: 'Resolved', closed: 'Closed' };
    return labels[status] || status;
};

const openSystemFeedbackDialog = () => {
    sfForm.category = 'bug';
    sfForm.area = null;
    sfForm.description = '';
    sfSelectedFile.value = null;
    sfPreviewUrl.value = null;
    sfPreviewIsImage.value = false;
    systemFeedbackDialog.value = true;
};

const closeSystemFeedbackDialog = () => {
    systemFeedbackDialog.value = false;
    if (sfPreviewUrl.value) URL.revokeObjectURL(sfPreviewUrl.value);
    sfSelectedFile.value = null;
    sfPreviewUrl.value = null;
};

const triggerSfFileInput = () => {
    sfFileInputRef.value?.click();
};

const handleSfFileSelect = (event) => {
    const file = event.target.files[0];
    processSfFile(file);
};

const handleSfFileDrop = (event) => {
    const file = event.dataTransfer?.files[0];
    processSfFile(file);
};

const processSfFile = (file) => {
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) {
        showSnackbar('File too large. Max 10MB.', 'error');
        return;
    }
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/quicktime', 'application/pdf'];
    if (!validTypes.includes(file.type)) {
        showSnackbar('Unsupported file type.', 'error');
        return;
    }
    sfSelectedFile.value = file;
    sfPreviewIsImage.value = file.type.startsWith('image/');
    if (sfPreviewUrl.value) URL.revokeObjectURL(sfPreviewUrl.value);
    sfPreviewUrl.value = sfPreviewIsImage.value ? URL.createObjectURL(file) : null;
};

const removeSfFile = () => {
    if (sfPreviewUrl.value) URL.revokeObjectURL(sfPreviewUrl.value);
    sfSelectedFile.value = null;
    sfPreviewUrl.value = null;
    sfPreviewIsImage.value = false;
};

const submitSystemFeedbackForm = async () => {
    if (!sfFormValid.value || !user.value?.id) return;
    submittingSf.value = true;
    try {
        const formData = new FormData();
        formData.append('user_id', user.value.id);
        formData.append('category', sfForm.category);
        if (sfForm.area) formData.append('area', sfForm.area);
        formData.append('description', sfForm.description);
        if (sfSelectedFile.value) formData.append('attachment', sfSelectedFile.value);

        const result = await submitSystemFeedback(formData);
        if (result.success) {
            showSnackbar(result.message || 'Report submitted! Thank you.', 'success');
            closeSystemFeedbackDialog();
            await loadUserSystemFeedbacks();
        } else {
            showSnackbar(result.message || 'Failed to submit.', 'error');
        }
    } catch (err) {
        console.error('System feedback submission failed:', err);
        showSnackbar('Failed to submit report. Please try again.', 'error');
    } finally {
        submittingSf.value = false;
    }
};

const loadUserSystemFeedbacks = async () => {
    if (!user.value?.id) return;
    try {
        const result = await getUserSystemFeedbacks(user.value.id);
        if (result.success) {
            userSystemFeedbacks.value = result.data || [];
        }
    } catch (err) {
        // Silent fail
    }
};

// Password visibility
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

// Photo upload
const selectedFile = ref(null);
const previewUrl = ref(null);

// User data
const user = ref({
    id: null,
    first_name: '',
    last_name: '',
    email: '',
    phone_number: '',
    id_number: '',
    profile_picture: null,
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_relation: '',
    blood_type: '',
    allergies: '',
    medical_conditions: '',
    gender: '',
    date_of_birth: '',
    is_verified: false,
    created_at: null,
});

// Edit data
const editData = reactive({
    first_name: '',
    last_name: '',
    phone_number: '',
    id_number: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_relation: '',
    blood_type: '',
    allergies: '',
    medical_conditions: '',
    gender: '',
    date_of_birth: '',
});

// Password data
const passwordData = reactive({
    new_password: '',
    confirm_password: '',
});

// Settings
const settings = reactive({
    pushNotifications: true,
    darkMode: false,
});

// Location History
const locationHistory = ref([]);
const loadingHistory = ref(false);

// Stats
const stats = ref({
    rescued: 0,
    pending: 0,
    total: 0,
});

const snackbar = ref({
    show: false,
    message: '',
    color: 'success',
});

// Missing-fields UX helpers
const missingFields = ref([]);
const missingMap = reactive({
    first_name: false,
    last_name: false,
    phone_number: false,
    id_number: false,
    gender: false,
    date_of_birth: false,
});

const requiredFieldLabels = {
    first_name: 'First Name',
    last_name: 'Last Name',
    phone_number: 'Phone Number',
    id_number: 'ID Number',
    gender: 'Gender',
    date_of_birth: 'Date of Birth',
};

const computeMissingFields = () => {
    const list = [];
    Object.keys(requiredFieldLabels).forEach((k) => {
        const val = (editData[k] ?? user.value[k] ?? '').toString().trim();
        if (!val) list.push(requiredFieldLabels[k]);
    });
    missingFields.value = list;
    return list;
};

const markMissingFields = (labels) => {
    Object.keys(missingMap).forEach(k => missingMap[k] = false);
    labels.forEach(label => {
        const key = Object.keys(requiredFieldLabels).find(k => requiredFieldLabels[k] === label);
        if (key) missingMap[key] = true;
    });
};

const onFieldInput = (field) => {
    if (missingMap[field]) missingMap[field] = false;
    // remove label from missingFields if present
    missingFields.value = missingFields.value.filter(l => l !== requiredFieldLabels[field]);
};

const showMissingFieldsToast = (origin = 'save') => {
    const list = computeMissingFields();
    if (!list.length) return;
    const msg = `Please complete required fields: ${list.join(', ')}`;
    showSnackbar(msg, 'warning');
    markMissingFields(list);
    // Scroll to first missing element (uses class applied to inputs)
    setTimeout(() => {
        const el = document.querySelector('.required-error');
        if (el && el.scrollIntoView) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 150);
};

// Validation rules
const rules = {
    required: (v) => !!v || 'Required',
    requiredField: (fieldName) => (v) => !!v || `${fieldName} is required`,
    minLength: (v) => (v && v.length >= 8) || 'Min 8 characters',
    hasUppercase: (v) => /[A-Z]/.test(v) || 'Must contain uppercase letter',
    hasLowercase: (v) => /[a-z]/.test(v) || 'Must contain lowercase letter',
    hasNumber: (v) => /[0-9]/.test(v) || 'Must contain a number',
    hasSpecial: (v) => /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(v) || 'Must contain special character',
    passwordMatch: (v) => v === passwordData.new_password || 'Passwords do not match',
    dateOfBirth: (v) => {
        if (!v) return 'Date of birth is required';
        const date = new Date(v);
        if (Number.isNaN(date.getTime())) return 'Invalid date';
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (date >= today) return 'Date of birth must be in the past';
        return true;
    },
    // Name validation - only letters and spaces allowed (no numbers, special chars, emojis)
    nameOnly: (v) => {
        if (!v) return true; // Optional field
        if (!/^[a-zA-Z\s]+$/.test(v)) {
            return 'Only letters and spaces are allowed';
        }
        return true;
    },
    // Phone number validation (used for all phone fields)
    phoneNumber: (v) => {
        if (!v) return true; // Optional field
        // Remove spaces, dashes, and parentheses
        const cleaned = v.replace(/[\s\-\(\)]/g, '');
        
        // Must start with 09 and have exactly 11 digits
        if (!/^09[0-9]{9}$/.test(cleaned)) {
            return 'Please enter a valid number';
        }
        
        return true;
    },
    // ID Number validation - exactly 9 digits, no letters, and enhanced security
    idNumber: (v) => {
        if (!v) return 'ID Number is required';
        
        // Must be exactly 9 digits, no letters or special characters
        const idRegex = /^\d{9}$/;
        if (!idRegex.test(v)) {
            return 'ID Number must be exactly 9 digits (numbers only)';
        }
        
        // Check for all zeros
        if (v === '000000000') {
            return 'ID Number cannot be all zeros';
        }
        
        // Check for repeating digits (111111111, 222222222, etc.)
        const firstDigit = v[0];
        if (v.split('').every(digit => digit === firstDigit)) {
            return 'ID Number cannot be all the same digit';
        }
        
        // Check for simple sequential patterns
        const sequences = [
            '123456789', '987654321', // ascending/descending
            '012345678', '876543210'  // starting from 0
        ];
        if (sequences.includes(v)) {
            return 'Please enter a valid ID number';
        }
        
        return true;
    },
};

// Real-time prevention - blocks special chars, numbers, and emojis in name fields
const preventInvalidNameChars = (event) => {
    const char = event.key || String.fromCharCode(event.keyCode || event.which);
    // Allow control keys (Backspace, Delete, Tab, arrows, etc.)
    if (char.length > 1) return;
    // Only allow letters and spaces
    if (!/^[a-zA-Z\s]$/.test(char)) {
        event.preventDefault();
        showSnackbar('Special characters are not allowed in this field', 'warning');
    }
};

// Sanitize pasted/input content - strips invalid characters from name fields
const sanitizeNameField = (field) => {
    const val = editData[field];
    if (!val) return;
    const sanitized = val.replace(/[^a-zA-Z\s]/g, '');
    if (sanitized !== val) {
        editData[field] = sanitized;
        showSnackbar('Special characters are not allowed in this field', 'warning');
    }
};

// Password validation checks for visual feedback
const passwordChecks = computed(() => {
    const pwd = passwordData.new_password || '';
    return {
        length: pwd.length >= 8,
        uppercase: /[A-Z]/.test(pwd),
        lowercase: /[a-z]/.test(pwd),
        number: /[0-9]/.test(pwd),
        special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd),
    };
});

// Password validation computed
const isPasswordValid = computed(() => {
    const checks = passwordChecks.value;
    return checks.length && checks.uppercase && checks.lowercase && checks.number && checks.special;
});

// Password strength bar computeds (matching ChangePassword.vue)
const pwdStrength = computed(() => {
    let s = 0;
    if (passwordChecks.value.length) s += 20;
    if (passwordChecks.value.uppercase) s += 20;
    if (passwordChecks.value.lowercase) s += 20;
    if (passwordChecks.value.number) s += 20;
    if (passwordChecks.value.special) s += 20;
    return s;
});

const pwdStrengthColor = computed(() => {
    if (pwdStrength.value <= 20) return '#ef5350';
    if (pwdStrength.value <= 40) return '#FFA726';
    if (pwdStrength.value <= 60) return '#DFA92C';
    if (pwdStrength.value <= 80) return '#66BB6A';
    return '#4CAF50';
});

const pwdStrengthGradient = computed(() => {
    if (pwdStrength.value <= 20) return '#ef5350';
    if (pwdStrength.value <= 40) return 'linear-gradient(90deg, #ef5350, #FFA726)';
    if (pwdStrength.value <= 60) return 'linear-gradient(90deg, #FFA726, #DFA92C)';
    if (pwdStrength.value <= 80) return 'linear-gradient(90deg, #DFA92C, #66BB6A)';
    return 'linear-gradient(90deg, #66BB6A, #4CAF50)';
});

const pwdStrengthText = computed(() => {
    if (pwdStrength.value <= 20) return 'Weak';
    if (pwdStrength.value <= 40) return 'Fair';
    if (pwdStrength.value <= 60) return 'Good';
    if (pwdStrength.value <= 80) return 'Strong';
    return 'Very Strong';
});

// Computed
const fullName = computed(() => {
    return `${user.value.first_name} ${user.value.last_name}`.trim() || 'User';
});

const getInitials = computed(() => {
    const first = user.value.first_name?.[0] || '';
    const last = user.value.last_name?.[0] || '';
    return (first + last).toUpperCase() || 'U';
});

const profilePictureUrl = computed(() => {
    const picturePath = user.value.profile_picture;
    if (!picturePath) return null;
    if (picturePath.startsWith('http') || picturePath.startsWith('data:')) return picturePath;
    return getProfilePictureUrl(picturePath);
});

const displayedHistory = computed(() => {
    return locationHistory.value.slice(0, 3);
});

// Computed properties to detect changes in each section
const hasPersonalChanges = computed(() => {
    return (
        editData.first_name !== (user.value.first_name || '') ||
        editData.last_name !== (user.value.last_name || '') ||
        editData.phone_number !== (user.value.phone_number || '') ||
        editData.id_number !== (user.value.id_number || '') ||
        editData.gender !== (user.value.gender || '') ||
        editData.date_of_birth !== normalizeDateInput(user.value.date_of_birth)
    );
});

// Computed property to check if ID number is valid
const isValidIdNumber = computed(() => {
    const id = editData.id_number;
    return id && /^\d{9}$/.test(id);
});

// Computed property to determine user role based on ID number
const userRoleFromId = computed(() => {
    const id = editData.id_number;
    if (!id || !/^\d{9}$/.test(id)) return null;
    
    // If starts with "20", it's a student
    if (id.startsWith('20')) {
        return 'student';
    }
    // Otherwise, it's faculty
    return 'faculty';
});

const hasEmergencyChanges = computed(() => {
    return (
        editData.emergency_contact_name !== (user.value.emergency_contact_name || '') ||
        editData.emergency_contact_phone !== (user.value.emergency_contact_phone || '') ||
        editData.emergency_contact_relation !== (user.value.emergency_contact_relation || '')
    );
});

const hasMedicalChanges = computed(() => {
    return (
        editData.blood_type !== (user.value.blood_type || '') ||
        editData.allergies !== (user.value.allergies || '') ||
        editData.medical_conditions !== (user.value.medical_conditions || '')
    );
});

// Load settings from localStorage
const loadSettings = () => {
    const savedSettings = localStorage.getItem('userSettings');
    if (savedSettings) {
        const parsed = JSON.parse(savedSettings);
        settings.pushNotifications = parsed.pushNotifications ?? true;
        settings.darkMode = parsed.darkMode ?? false;
    }
    // Sync dark mode composable with loaded setting
    setDarkMode(settings.darkMode);
};

// Save settings to localStorage when they change
watch(settings, (newSettings) => {
    localStorage.setItem('userSettings', JSON.stringify(newSettings));
}, { deep: true });

// Methods
const loadUser = async () => {
    try {
        if (!authUser.value) {
            router.visit('/login');
            return;
        }

        const inertiaUser = authUser.value;
        user.value = {
            id: inertiaUser.id,
            email: inertiaUser.email,
            first_name: inertiaUser.first_name || '',
            last_name: inertiaUser.last_name || '',
            phone_number: inertiaUser.phone_number || inertiaUser.contact_number || '',
            id_number: inertiaUser.id_number || '',
            profile_picture: inertiaUser.profile_picture || null,
            emergency_contact_name: inertiaUser.emergency_contact_name || '',
            emergency_contact_phone: inertiaUser.emergency_contact_phone || '',
            emergency_contact_relation: inertiaUser.emergency_contact_relation || '',
            blood_type: inertiaUser.blood_type || '',
            allergies: inertiaUser.allergies || '',
            medical_conditions: inertiaUser.medical_conditions || '',
            gender: inertiaUser.gender || '',
            date_of_birth: normalizeDateInput(inertiaUser.date_of_birth),
            is_verified: inertiaUser.is_verified || false,
            created_at: inertiaUser.created_at || null,
        };

        // Sync edit data
        syncEditData();

        localStorage.setItem('userData', JSON.stringify(user.value));

        // Try to refresh from API
        try {
            const data = await getCurrentUser();
            if (data) {
                user.value = { ...user.value, ...data };
                syncEditData();
                localStorage.setItem('userData', JSON.stringify(user.value));
            }
        } catch (apiErr) {
            console.warn('API call failed, using Inertia auth data:', apiErr.message);
        }
    } catch (err) {
        console.error('Error loading user:', err);
        router.visit('/login');
    } finally {
        loading.value = false;
    }
};

const syncEditData = () => {
    editData.first_name = user.value.first_name || '';
    editData.last_name = user.value.last_name || '';
    editData.phone_number = user.value.phone_number || '';
    editData.id_number = user.value.id_number || '';
    editData.emergency_contact_name = user.value.emergency_contact_name || '';
    editData.emergency_contact_phone = user.value.emergency_contact_phone || '';
    editData.emergency_contact_relation = user.value.emergency_contact_relation || '';
    editData.blood_type = user.value.blood_type || '';
    editData.allergies = user.value.allergies || '';
    editData.medical_conditions = user.value.medical_conditions || '';
    editData.gender = user.value.gender || '';
    editData.date_of_birth = normalizeDateInput(user.value.date_of_birth);
};

// Format ID number input to only allow digits
const formatIdNumber = () => {
    // Remove any non-digit characters
    if (editData.id_number) {
        editData.id_number = editData.id_number.replace(/\D/g, '').substring(0, 9);
    }
};

// Format phone number for  s (used for all phone fields)
const formatPhoneNumber = (field) => {
    let value = editData[field] || '';
    
    // Remove all non-digit characters except + at the beginning
    if (value.startsWith('+')) {
        value = '+' + value.substring(1).replace(/\D/g, '');
    } else {
        value = value.replace(/\D/g, '');
    }
    
    // If it starts with +63, limit to 13 characters
    if (value.startsWith('+63')) {
        value = value.substring(0, 13);
    }
    // If it starts with 63 (without +), limit to 12 characters  
    else if (value.startsWith('63')) {
        value = value.substring(0, 12);
    }
    // If it starts with 09, limit to 11 characters
    else if (value.startsWith('09') || value.startsWith('9')) {
        value = value.substring(0, 11);
    }
    // For other cases, limit to 11 characters max (standard PH mobile)
    else {
        value = value.substring(0, 11);
    }
    
    editData[field] = value;
};

const fetchLocationHistory = async () => {
    if (!user.value.id) return;

    loadingHistory.value = true;
    try {
        const data = await getUserRescueHistory(user.value.id);
        const records = Array.isArray(data) ? data : (data?.data || []);

        locationHistory.value = records.map((record) => ({
            id: record.id,
            location: formatLocationFromRecord(record),
            isRescued: ['rescued', 'safe'].includes(record.status),
            status: record.status,
            timestamp: record.created_at,
            rescue_code: record.rescue_code,
        }));

        // Calculate stats
        const rescued = locationHistory.value.filter(l => l.isRescued).length;
        const pending = locationHistory.value.filter(l => !l.isRescued && !['cancelled'].includes(l.status)).length;
        stats.value = {
            rescued,
            pending,
            total: locationHistory.value.length,
        };
    } catch (err) {
        console.error('Failed to fetch location history:', err);
    } finally {
        loadingHistory.value = false;
    }
};

const formatLocationFromRecord = (record) => {
    const parts = [];
    if (record.building_name) parts.push(record.building_name);
    if (record.floor_name) parts.push(record.floor_name);
    if (record.room_name) parts.push(record.room_name);
    return parts.join(' > ') || 'Unknown Location';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const normalizeDateInput = (value) => {
    if (!value) return '';
    if (typeof value === 'string') {
        const match = value.match(/^\d{4}-\d{2}-\d{2}/);
        if (match) return match[0];
    }
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return '';
    return parsed.toISOString().slice(0, 10);
};

const formatHistoryDate = (dateString) => {
    if (!dateString) return 'Unknown';
    const date = new Date(dateString);
    const now = new Date();

    if (date.toDateString() === now.toDateString()) {
        return 'Today ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    if (date.getFullYear() === now.getFullYear()) {
        return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }

    return date.toLocaleDateString();
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'warning',
        open: 'info',
        assigned: 'primary',
        en_route: 'secondary',
        on_scene: 'success',
        rescued: 'success',
        safe: 'success',
        cancelled: 'grey',
    };
    return colors[status] || 'grey';
};

const formatStatus = (status) => {
    const labels = {
        pending: 'Pending',
        open: 'Open',
        assigned: 'Assigned',
        en_route: 'En Route',
        on_scene: 'On Scene',
        rescued: 'Rescued',
        safe: 'Safe',
        cancelled: 'Cancelled',
    };
    return labels[status] || status;
};

const viewHistoryItem = (item) => {
    if (item.rescue_code) {
        router.visit(`/user/help-coming/${item.rescue_code}`);
    }
};

const cancelEdit = () => {
    editData.first_name = user.value.first_name || '';
    editData.last_name = user.value.last_name || '';
    editData.phone_number = user.value.phone_number || '';
    editData.id_number = user.value.id_number || '';
    editData.gender = user.value.gender || '';
    editData.date_of_birth = normalizeDateInput(user.value.date_of_birth);
};

const saveProfile = async () => {
    // First, compute missing required fields and show toast/highlight if any
    const missing = computeMissingFields();
    if (missing.length) {
        showMissingFieldsToast('save');
        return;
    }

    if (!formValid.value) return;

    // Validate phone number before saving
    if (editData.phone_number) {
        const phoneValidation = rules.phoneNumber(editData.phone_number);
        if (phoneValidation !== true) {
            showSnackbar(phoneValidation, 'error');
            return;
        }
    }

    // Validate ID number before saving
    if (editData.id_number) {
        const idValidation = rules.idNumber(editData.id_number);
        if (idValidation !== true) {
            showSnackbar(idValidation, 'error');
            return;
        }
    }

    saving.value = true;
    try {
        // Only save personal information fields
        const updateData = {
            first_name: editData.first_name,
            last_name: editData.last_name,
            phone_number: editData.phone_number,
            id_number: editData.id_number,
            gender: editData.gender,
            date_of_birth: editData.date_of_birth,
        };

        // Include role determination based on ID number
        if (editData.id_number && isValidIdNumber.value) {
            updateData.role = userRoleFromId.value;
        }

        console.log('Updating personal info with data:', updateData);
        
        const updatedUser = await updateUser(user.value.id, updateData);
        const savedUser = updatedUser?.data || updatedUser || {};

        // Update local user state from the server response so the saved values persist immediately
        user.value = {
            ...user.value,
            ...savedUser,
            first_name: savedUser.first_name ?? editData.first_name,
            last_name: savedUser.last_name ?? editData.last_name,
            phone_number: savedUser.phone_number ?? savedUser.phone ?? editData.phone_number,
            id_number: savedUser.id_number ?? editData.id_number,
            gender: savedUser.gender ?? editData.gender,
            date_of_birth: normalizeDateInput(savedUser.date_of_birth ?? editData.date_of_birth),
        };
        syncEditData();
        
        // Update localStorage
        localStorage.setItem('userData', JSON.stringify(user.value));

        showSnackbar('Personal information updated successfully', 'success');

        // Reload Inertia page props to refresh auth user data immediately
        router.reload({ only: ['auth'] });
    } catch (err) {
        console.error('Error updating profile:', err);
        const errorMsg = err?.data?.message || err?.message || 'Failed to update profile';
        showSnackbar(errorMsg, 'error');
    } finally {
        saving.value = false;
    }
};

// Save Emergency Contact
const saveEmergencyContact = async () => {
    // Validate required fields first
    if (!editData.emergency_contact_name || !editData.emergency_contact_name.trim()) {
        showSnackbar('Contact Name is required', 'error');
        return;
    }
    if (!editData.emergency_contact_phone || !editData.emergency_contact_phone.trim()) {
        showSnackbar('Contact Phone is required', 'error');
        return;
    }

    // Validate emergency contact phone number format
    if (editData.emergency_contact_phone) {
        const phoneValidation = rules.phoneNumber(editData.emergency_contact_phone);
        if (phoneValidation !== true) {
            showSnackbar(phoneValidation, 'error');
            return;
        }
    }

    savingEmergency.value = true;
    try {
        // Always include all emergency contact fields (allow clearing optional ones)
        const updateData = {
            emergency_contact_name: (editData.emergency_contact_name && editData.emergency_contact_name.trim()) ? editData.emergency_contact_name.trim() : '',
            emergency_contact_phone: (editData.emergency_contact_phone && editData.emergency_contact_phone.trim()) ? editData.emergency_contact_phone.trim() : '',
            emergency_contact_relation: (editData.emergency_contact_relation && editData.emergency_contact_relation.trim()) ? editData.emergency_contact_relation.trim() : null
        };

        console.log('Updating emergency contact with data:', updateData);
        
        await updateUser(user.value.id, updateData);
        
        // Update local user state to sync with editData (hides save button)
        user.value.emergency_contact_name = editData.emergency_contact_name;
        user.value.emergency_contact_phone = editData.emergency_contact_phone;
        user.value.emergency_contact_relation = editData.emergency_contact_relation;
        
        // Update localStorage
        localStorage.setItem('userData', JSON.stringify(user.value));

        showSnackbar('Emergency contact updated successfully', 'success');

        // Reload Inertia page props to refresh auth user data immediately
        router.reload({ only: ['auth'] });
    } catch (err) {
        console.error('Error updating emergency contact:', err);
        const errorMsg = err?.data?.message || err?.message || 'Failed to update emergency contact';
        showSnackbar(errorMsg, 'error');
    } finally {
        savingEmergency.value = false;
    }
};

// Cancel Emergency Contact Edit
const cancelEditEmergency = () => {
    editData.emergency_contact_name = user.value.emergency_contact_name || '';
    editData.emergency_contact_phone = user.value.emergency_contact_phone || '';
    editData.emergency_contact_relation = user.value.emergency_contact_relation || '';
};

// Save Medical Information
const saveMedicalInfo = async () => {
    // Validate required fields first
    if (!editData.allergies || !editData.allergies.trim()) {
        showSnackbar('Allergies is required', 'error');
        return;
    }

    savingMedical.value = true;
    try {
        // Always include all medical fields (allow clearing optional ones)
        const updateData = {
            blood_type: editData.blood_type || null,
            allergies: (editData.allergies && editData.allergies.trim()) ? editData.allergies.trim() : '',
            medical_conditions: (editData.medical_conditions && editData.medical_conditions.trim()) ? editData.medical_conditions.trim() : null
        };

        console.log('Updating medical info with data:', updateData);
        
        await updateUser(user.value.id, updateData);
        
        // Update local user state to sync with editData (hides save button)
        user.value.blood_type = editData.blood_type;
        user.value.allergies = editData.allergies;
        user.value.medical_conditions = editData.medical_conditions;
        
        // Update localStorage
        localStorage.setItem('userData', JSON.stringify(user.value));

        showSnackbar('Medical information updated successfully', 'success');

        // Reload Inertia page props to refresh auth user data immediately
        router.reload({ only: ['auth'] });
    } catch (err) {
        console.error('Error updating medical info:', err);
        const errorMsg = err?.data?.message || err?.message || 'Failed to update medical information';
        showSnackbar(errorMsg, 'error');
    } finally {
        savingMedical.value = false;
    }
};

// Cancel Medical Info Edit
const cancelEditMedical = () => {
    editData.blood_type = user.value.blood_type || '';
    editData.allergies = user.value.allergies || '';
    editData.medical_conditions = user.value.medical_conditions || '';
};

// Password Change OTP Flow
const startResendCountdown = () => {
    resendCountdown.value = 60;
    if (countdownInterval) clearInterval(countdownInterval);
    countdownInterval = setInterval(() => {
        if (resendCountdown.value > 0) {
            resendCountdown.value--;
        } else {
            clearInterval(countdownInterval);
        }
    }, 1000);
};

const sendPasswordOtp = async () => {
    sendingOtp.value = true;
    passwordError.value = '';
    try {
        const response = await fetch('/api/auth/send-password-change-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ email: user.value.email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            passwordStep.value = 2;
            startResendCountdown();
            showSnackbar('Verification code sent to your email', 'success');
        } else {
            passwordError.value = data.message || 'Failed to send code';
        }
    } catch (error) {
        console.error('Error sending OTP:', error);
        passwordError.value = 'Failed to send verification code';
    } finally {
        sendingOtp.value = false;
    }
};

const verifyPasswordOtp = async () => {
    const otpValue = Array.isArray(otpCode.value) 
        ? otpCode.value.join('') 
        : String(otpCode.value).trim();
    
    if (otpValue.length !== 6) return;
    
    verifyingOtp.value = true;
    passwordError.value = '';
    try {
        const response = await fetch('/api/auth/verify-password-change-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                email: user.value.email,
                otp: otpValue
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            verificationToken.value = data.token;
            passwordStep.value = 3;
            showSnackbar('Code verified! Set your new password.', 'success');
        } else {
            passwordError.value = data.message || 'Invalid code';
        }
    } catch (error) {
        console.error('Error verifying OTP:', error);
        passwordError.value = 'Failed to verify code';
    } finally {
        verifyingOtp.value = false;
    }
};

const changePassword = async () => {
    if (!isPasswordValid.value) return;

    changingPassword.value = true;
    passwordError.value = '';
    try {
        const response = await fetch('/api/auth/complete-password-change', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                email: user.value.email,
                token: verificationToken.value,
                password: passwordData.new_password,
                password_confirmation: passwordData.confirm_password
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            passwordComplete.value = true;
            showSnackbar('Password changed successfully!', 'success');
        } else {
            passwordError.value = data.message || 'Failed to change password';
        }
    } catch (error) {
        console.error('Error changing password:', error);
        passwordError.value = 'Failed to change password';
    } finally {
        changingPassword.value = false;
    }
};

const resetPasswordFlow = () => {
    passwordStep.value = 1;
    passwordComplete.value = false;
    passwordError.value = '';
    otpCode.value = '';
    verificationToken.value = '';
    passwordData.new_password = '';
    passwordData.confirm_password = '';
    if (passwordFormRef.value) passwordFormRef.value.reset();
};

const openChangePasswordDialog = () => {
    resetPasswordFlow();
    passwordDialog.value = true;
};

const closePasswordDialog = () => {
    passwordDialog.value = false;
    resetPasswordFlow();
};

const updateSetting = async (setting) => {
    // Save settings to localStorage immediately
    localStorage.setItem('userSettings', JSON.stringify({
        pushNotifications: settings.pushNotifications,
        darkMode: settings.darkMode,
    }));
    
    if (setting === 'Notifications') {
        if (settings.pushNotifications) {
            if ('Notification' in window) {
                const permission = await Notification.requestPermission();
                if (permission === 'granted') {
                    showSnackbar('Push notifications enabled', 'success');
                } else if (permission === 'denied') {
                    settings.pushNotifications = false;
                    showSnackbar('Notification permission denied', 'warning');
                }
            }
        } else {
            showSnackbar('Push notifications disabled', 'info');
        }
    } else if (setting === 'DarkMode') {
        setDarkMode(settings.darkMode);
        showSnackbar(settings.darkMode ? 'Dark mode enabled' : 'Dark mode disabled', 'info');
    }
};



// Navigate to location history
const goToLocationHistory = () => {
    router.visit('/user/history');
};

// Photo handling
const openPhotoDialog = () => {
    selectedFile.value = null;
    previewUrl.value = null;
    photoDialog.value = true;
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        showSnackbar('Max file size is 5MB', 'error');
        return;
    }

    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        showSnackbar('Invalid file type', 'error');
        return;
    }

    selectedFile.value = file;
    previewUrl.value = URL.createObjectURL(file);
};

const cancelPhotoUpload = () => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    selectedFile.value = null;
    previewUrl.value = null;
    photoDialog.value = false;
};

const uploadPhoto = async () => {
    if (!selectedFile.value || !user.value.id) return;

    uploadingPhoto.value = true;
    try {
        const result = await uploadProfilePicture(user.value.id, selectedFile.value);

        user.value.profile_picture = result.profile_picture;
        localStorage.setItem('userData', JSON.stringify(user.value));

        showSnackbar('Photo updated', 'success');

        if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
        selectedFile.value = null;
        previewUrl.value = null;
        photoDialog.value = false;
    } catch (err) {
        console.error('Error uploading photo:', err);
        showSnackbar('Failed to upload', 'error');
    } finally {
        uploadingPhoto.value = false;
    }
};

const confirmDeletePhoto = () => {
    deletePhotoDialog.value = true;
};

const deletePhoto = async () => {
    if (!user.value.id) return;

    deletingPhoto.value = true;
    try {
        await deleteProfilePicture(user.value.id);

        user.value.profile_picture = null;
        localStorage.setItem('userData', JSON.stringify(user.value));

        showSnackbar('Photo removed', 'success');
        deletePhotoDialog.value = false;
        photoDialog.value = false;
    } catch (err) {
        console.error('Error deleting photo:', err);
        showSnackbar('Failed to remove', 'error');
    } finally {
        deletingPhoto.value = false;
    }
};

const handleLogout = async () => {
    loggingOut.value = true;

    // 1. Clear ALL local storage & session data FIRST to ensure clean state
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    localStorage.removeItem('userData');
    localStorage.removeItem('authToken');
    localStorage.removeItem('token');
    localStorage.removeItem('userSettings');
    localStorage.removeItem('rescuerSettings');
    localStorage.removeItem('lastRescueCode');
    localStorage.removeItem('lastRescueRequestId');
    localStorage.removeItem('lastRescueRequestTime');
    localStorage.removeItem('conversationId');
    localStorage.removeItem('chatId');
    localStorage.removeItem('activeRescue');
    sessionStorage.clear();

    // 2. Fire-and-forget: set user inactive in Firebase (don't block logout)
    if (userData.id) {
        setUserActiveStatus(userData.id, false).catch(e => 
            console.error('[Logout] Firebase inactive error:', e)
        );
    }

    // 3. Call backend logout with timeout - don't let it block the redirect
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3000);

        await fetch('/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
            },
            credentials: 'include',
            signal: controller.signal
        });
        clearTimeout(timeoutId);
    } catch (error) {
        console.warn('[Logout] Backend logout error (proceeding anyway):', error.message);
    }

    // 4. Clear cookies manually as fallback
    document.cookie.split(';').forEach(c => {
        document.cookie = c.replace(/^ +/, '').replace(/=.*/, '=;expires=' + new Date().toUTCString() + ';path=/');
    });

    // 5. Force hard redirect to login
    window.location.replace('/login');
};

const goBack = () => {
    router.visit('/user/scanner');
};

const showSnackbar = (message, color = 'success') => {
    snackbar.value = { show: true, message, color };
};

// Lifecycle
onMounted(async () => {
    loadSettings();
    await loadUser();
    // If middleware redirected user here due to incomplete profile, show a toast once
    try {
        const userId = user.value?.id;
        const shownKey = userId ? `profile_incomplete_toast_shown_${userId}` : 'profile_incomplete_toast_shown';
        const mustUpdate = authUser.value?.must_update_profile || user.value?.must_update_profile;
        if (mustUpdate && !sessionStorage.getItem(shownKey)) {
            showMissingFieldsToast('mount');
            sessionStorage.setItem(shownKey, '1');
        }
    } catch (e) {
        // ignore
    }
    await fetchLocationHistory();
    await loadUserSystemFeedbacks();
});
</script>

<style scoped>
/* Profile Page Header */
.profile-page-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #3674B5;
    padding: env(safe-area-inset-top, 0) 0 0 0;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    gap: 12px;
}

.menu-btn, .placeholder-btn {
    color: white;
}

.header-title {
    flex: 1;
    text-align: center;
}

.header-title h1 {
    font-size: 1.125rem;
    font-weight: 700;
    color: white;
    margin: 0;
}

.header-title p {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
}

/* Profile Container - mobile-first padding */
.profile-container {
    padding: 12px !important;
    padding-bottom: 100px !important;
}

@media (min-width: 600px) {
    .profile-container {
        padding: 16px !important;
        padding-bottom: 100px !important;
    }
    
    .header-title h1 {
        font-size: 1.25rem;
    }
    
    .header-title p {
        font-size: 0.75rem;
    }
}

.profile-header-bg {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 50%, #0D47A1 100%);
}

.avatar-ring {
    border: 4px solid rgba(255, 255, 255, 0.3);
    background: linear-gradient(135deg, #42A5F5, #1E88E5);
}

/* Mobile avatar size adjustment */
.avatar-mobile {
    width: 80px !important;
    height: 80px !important;
}

@media (min-width: 600px) {
    .avatar-mobile {
        width: 100px !important;
        height: 100px !important;
    }
}

.edit-avatar-btn {
    bottom: -4px;
    right: -4px;
    border: 2px solid white;
}

.stats-row {
    background: rgba(25, 118, 210, 0.03);
}

.position-relative {
    position: relative;
}

.position-absolute {
    position: absolute;
}

.d-inline-block {
    display: inline-block;
}

/* Section Cards - mobile-friendly */
.section-card {
    border: 1px solid rgba(0, 0, 0, 0.05);
}

/* Panel titles - mobile-friendly with larger touch targets */
.panel-title-mobile {
    padding: 14px 12px !important;
    min-height: 60px !important;
}

@media (min-width: 600px) {
    .panel-title-mobile {
        padding: 16px !important;
        min-height: 64px !important;
    }
}

/* Panel content - mobile-friendly padding */
.panel-content-mobile {
    padding: 4px 0 !important;
}

:deep(.v-expansion-panel-text__wrapper) {
    padding: 0 12px 16px !important;
}

@media (min-width: 600px) {
    :deep(.v-expansion-panel-text__wrapper) {
        padding: 0 16px 16px !important;
    }
}

/* Mobile input fields - larger touch targets */
.mobile-input :deep(.v-field) {
    min-height: 48px !important;
}

.mobile-input :deep(.v-field__input) {
    padding-top: 12px !important;
    padding-bottom: 12px !important;
    font-size: 0.9375rem !important;
}

/* Highlight required missing fields */
.required-error :deep(.v-field) {
    border-color: #e53935 !important;
    box-shadow: 0 0 0 3px rgba(229, 57, 53, 0.06) !important;
}
.required-error :deep(.v-field__input) {
    background-color: #fff6f6 !important;
}

/* Mobile buttons - better touch targets */
.mobile-btn {
    min-height: 48px !important;
    font-weight: 600 !important;
}

/* Logout button styling */
.logout-btn {
    min-height: 52px !important;
    font-weight: 600 !important;
    letter-spacing: 0.5px;
}

/* Settings list mobile styling */
.settings-list {
    margin: 0 -4px;
}

.setting-item {
    background: rgba(0, 0, 0, 0.02);
    min-height: 64px !important;
}

.setting-item:active {
    background: rgba(0, 0, 0, 0.05);
}

/* History item styling */
.history-item {
    background: rgba(0, 0, 0, 0.02);
    transition: background 0.2s;
    min-height: 60px !important;
}

.history-item:hover,
.history-item:active {
    background: rgba(0, 0, 0, 0.06);
    cursor: pointer;
}

/* Non-editable fields styling */
.non-editable-field {
    opacity: 0.7;
}

.non-editable-field :deep(.v-field) {
    background-color: #f5f5f5 !important;
}

.non-editable-field :deep(.v-field__input) {
    color: #666 !important;
}

/* Password requirements styling */
.password-requirements {
    background: rgba(0, 0, 0, 0.02);
    border-radius: 8px;
    padding: 12px;
}

/* ── Change Password Dialog (ChangePassword.vue style) ── */
.pwd-dialog-card {
    overflow: hidden;
    border: none;
}

.pwd-card-header {
    background: linear-gradient(135deg, #3674B5 0%, #2D5F96 100%);
    padding: 28px 32px 24px;
    text-align: center;
    position: relative;
}
.pwd-card-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #DFA92C 0%, #f0c040 50%, #DFA92C 100%);
}
.pwd-header-icon-wrap {
    width: 64px;
    height: 64px;
    margin: 0 auto 14px;
    background: rgba(255,255,255,0.15);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
}
.pwd-header-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: white;
    margin-bottom: 6px;
    letter-spacing: -0.3px;
}
.pwd-header-subtitle {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.75);
    margin: 0;
    line-height: 1.5;
}
.pwd-close-btn {
    position: absolute !important;
    top: 12px;
    right: 12px;
}
.pwd-card-body {
    background: #fafbfc;
}

/* Field Labels */
.pwd-field-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #13294B;
    margin-bottom: 6px;
    letter-spacing: 0.3px;
}

/* Password Fields */
.pwd-password-field :deep(.v-field) {
    background: white;
    border: 1.5px solid #e0e4ea;
    transition: border-color 0.2s;
}
.pwd-password-field :deep(.v-field:hover) {
    border-color: #3674B5;
}
.pwd-password-field :deep(.v-field--focused) {
    border-color: #3674B5 !important;
}
.pwd-password-field :deep(input) {
    color: #13294B;
    font-size: 0.95rem;
}
.pwd-password-field :deep(input::placeholder) {
    color: #adb5bd;
}

/* Strength Bar */
.pwd-strength-bar-wrap {
    padding: 0 2px;
}
.pwd-strength-bar-track {
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
}
.pwd-strength-bar-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.4s ease, background 0.4s ease;
}

/* Requirements List (2-col grid) */
.pwd-requirements-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px 12px;
}
.pwd-req-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: #999;
    transition: color 0.2s;
}
.pwd-req-item.met {
    color: #3674B5;
}

/* OTP */
.pwd-otp-container {
    max-width: 300px;
    margin: 0 auto;
}
.pwd-otp-input-custom :deep(.v-otp-input__content) {
    justify-content: center;
    gap: 8px;
}
.pwd-otp-input-custom :deep(.v-field) {
    border-radius: 10px;
    background: white;
    border: 1.5px solid #e0e4ea;
}
.pwd-otp-input-custom :deep(.v-field--focused) {
    border-color: #3674B5 !important;
}
.pwd-otp-input-custom :deep(input) {
    font-size: 20px;
    font-weight: 600;
    text-align: center;
    color: #13294B;
}

/* Action Button */
.pwd-action-btn {
    font-weight: 600;
    font-size: 0.95rem;
    letter-spacing: 0;
    text-transform: none;
    height: 48px !important;
    box-shadow: 0 4px 14px rgba(54, 116, 181, 0.3);
    transition: box-shadow 0.2s, transform 0.1s;
}
.pwd-action-btn:hover {
    box-shadow: 0 6px 20px rgba(54, 116, 181, 0.4);
    transform: translateY(-1px);
}
.pwd-action-btn:active {
    transform: translateY(0);
}

/* Success Check */
.pwd-success-check-wrap {
    display: flex;
    justify-content: center;
}
.pwd-success-check-circle {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #3674B5, #4a8fd4);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(54, 116, 181, 0.3);
    animation: pwdSuccessPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes pwdSuccessPop {
    0% { transform: scale(0); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

/* Responsive for password dialog */
@media (max-width: 600px) {
    .pwd-card-header {
        padding: 18px 16px 16px;
    }
    .pwd-header-title {
        font-size: 1.1rem;
    }
    .pwd-header-subtitle {
        font-size: 0.78rem;
    }
    .pwd-header-icon-wrap {
        width: 52px;
        height: 52px;
        margin-bottom: 10px;
    }
    .pwd-requirements-list {
        grid-template-columns: 1fr;
        gap: 4px;
    }
    .pwd-action-btn {
        height: 44px !important;
        font-size: 0.9rem;
    }
}

/* OTP input styling */
:deep(.v-otp-input) {
    justify-content: center;
}

:deep(.v-otp-input input) {
    font-size: 1.25rem;
    font-weight: 600;
}

/* Mobile responsiveness */
@media (max-width: 600px) {
    .pb-20 {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 140px) !important;
    }
    
    /* Ensure buttons stack nicely on very small screens */
    .d-flex.flex-column.flex-sm-row .v-btn {
        width: 100%;
    }
    
    .v-main :deep(.v-container) {
        padding-bottom: 50px !important;
    }
}

@media (max-width: 1024px) {
    .v-main {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 120px) !important;
    }
    
    .v-main :deep(.v-container) {
        padding-bottom: 40px !important;
    }
}

@media (min-width: 1025px) {
    .pb-20 {
        padding-bottom: 0 !important;
    }
}

/* ═══════════════════════════════════════════════════════ */
/* SYSTEM FEEDBACK MODULE                                  */
/* ═══════════════════════════════════════════════════════ */

/* History items */
.sf-history-item {
    background: rgba(0, 0, 0, 0.02);
    border: 1px solid rgba(0, 0, 0, 0.04);
    transition: background 0.15s;
}

/* Dialog */
.sf-dialog-card {
    overflow: hidden;
    border-radius: 16px !important;
}

.sf-dialog-header {
    background: linear-gradient(135deg, #3674B5 0%, #2D5F96 100%);
    padding: 18px 20px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    position: relative;
}

.sf-dialog-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #DFA92C 0%, #f0c040 50%, #DFA92C 100%);
}

.sf-dialog-header-content {
    display: flex;
    align-items: flex-start;
}

.sf-dialog-title {
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    color: white;
    margin: 0;
    line-height: 1.3;
}

.sf-dialog-subtitle {
    font-family: 'Inter', sans-serif;
    font-size: 0.72rem;
    color: rgba(255, 255, 255, 0.7);
    margin: 2px 0 0;
}

.sf-dialog-body {
    padding: 24px 24px 20px;
    background: #fafbfc;
}

.sf-field-label {
    font-family: 'Inter', sans-serif;
    font-size: 0.8rem;
    font-weight: 600;
    color: #13294B;
    margin-bottom: 6px;
    letter-spacing: 0.3px;
}

.sf-category-toggle {
    width: 100%;
}

.sf-cat-btn {
    flex: 1 !important;
    font-family: 'Inter', sans-serif;
    font-size: 0.78rem !important;
    font-weight: 600 !important;
    text-transform: none !important;
    letter-spacing: 0 !important;
}

/* Upload area */
.sf-upload-area {
    border: 2px dashed #d0d5dd;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    position: relative;
    background: white;
}

.sf-upload-area:hover {
    border-color: #3674B5;
    background: rgba(54, 116, 181, 0.03);
}

.sf-upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.sf-upload-preview {
    position: relative;
}

.sf-upload-remove {
    position: absolute;
    top: -6px;
    right: -6px;
}

/* Submit button */
.sf-submit-btn {
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    font-size: 0.92rem;
    letter-spacing: 0.02em;
    text-transform: none;
    height: 46px !important;
    box-shadow: 0 4px 14px rgba(54, 116, 181, 0.25);
    transition: box-shadow 0.2s, transform 0.1s;
}

.sf-submit-btn:hover {
    box-shadow: 0 6px 20px rgba(54, 116, 181, 0.35);
    transform: translateY(-1px);
}

.sf-submit-btn:active {
    transform: translateY(0);
}

@media (max-width: 400px) {
    .sf-dialog-body {
        padding: 18px 16px 16px;
    }

    .sf-cat-btn {
        font-size: 0.72rem !important;
    }
}
</style>
