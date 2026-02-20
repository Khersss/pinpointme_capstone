<template>
    <v-app class="bg-grey-lighten-4">

        <!-- Admin App Bar -->
        <AdminAppBar activePage="rescuers" />

        <!-- Main Content -->
        <v-main>
            <v-container fluid :class="isMobile ? 'pa-3' : 'pa-6'">
                <!-- Page Header -->
                <div class="d-flex align-center mb-4">
                    <div>
                        <h1 :class="isMobile ? 'text-h5' : 'text-h4'" class="font-weight-bold gradient-text">Rescuer Management</h1>
                        <p class="text-grey mt-1 text-body-2">To maintain security and compliance.</p>
                    </div>
                </div>

                <!-- Stats Banner -->
                <v-card rounded="lg" class="rescuers-stats-banner mb-3" elevation="0">
                    <div class="stats-banner-grid">
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon">mdi-account-group</v-icon>
                            <div class="stat-inline-value">{{ counts.total || 0 }}</div>
                            <div class="stat-inline-label">Total</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon green-icon">mdi-check-circle</v-icon>
                            <div class="stat-inline-value">{{ counts.available || 0 }}</div>
                            <div class="stat-inline-label">Available</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon amber-icon">mdi-alert-circle</v-icon>
                            <div class="stat-inline-value">{{ counts.on_rescue || 0 }}</div>
                            <div class="stat-inline-label">On Rescue</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon">mdi-sleep</v-icon>
                            <div class="stat-inline-value">{{ counts.off_duty || 0 }}</div>
                            <div class="stat-inline-label">Off Duty</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon red-icon">mdi-close-circle</v-icon>
                            <div class="stat-inline-value">{{ counts.unavailable || 0 }}</div>
                            <div class="stat-inline-label">Unavailable</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon orange-icon">mdi-account-clock</v-icon>
                            <div class="stat-inline-value">{{ counts.pending || 0 }}</div>
                            <div class="stat-inline-label">Pending</div>
                        </div>
                    </div>
                </v-card>

                <!-- Pending Rescuer Applications -->
                <v-card v-if="pendingApplications.length > 0" rounded="lg" elevation="0" class="mb-3 pending-card">
                    <v-card-title class="d-flex align-center justify-space-between pa-4">
                        <div class="d-flex align-center">
                            <v-icon start color="orange-darken-2">mdi-account-clock</v-icon>
                            <span class="font-weight-bold">Pending Rescuer Applications</span>
                            <v-chip size="small" color="orange" variant="flat" class="ml-2">{{ pendingApplications.length }}</v-chip>
                        </div>
                        <v-btn variant="text" size="small" color="primary" @click="pendingExpanded = !pendingExpanded">
                            {{ pendingExpanded ? 'Collapse' : 'Expand' }}
                            <v-icon end>{{ pendingExpanded ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
                        </v-btn>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-expand-transition>
                        <div v-show="pendingExpanded">
                            <v-card-text class="pa-4">
                                <div v-for="app in pendingApplications" :key="app.id" class="pending-item pa-4 mb-3 rounded-lg">
                                    <div class="d-flex flex-wrap align-center justify-space-between gap-3">
                                        <div class="d-flex align-center">
                                            <v-avatar color="orange-darken-1" size="42" class="mr-3">
                                                <span class="text-white font-weight-medium text-caption">{{ (app.first_name?.[0] || '') + (app.last_name?.[0] || '') }}</span>
                                            </v-avatar>
                                            <div>
                                                <div class="font-weight-bold text-subtitle-2">{{ app.first_name }} {{ app.last_name }}</div>
                                                <div class="text-caption text-grey">{{ app.email }}</div>
                                                <div class="d-flex align-center gap-2 mt-1">
                                                    <v-chip v-if="app.organization" size="x-small" color="blue-grey" variant="tonal" prepend-icon="mdi-domain">
                                                        {{ app.organization }}
                                                    </v-chip>
                                                    <v-chip v-if="app.phone" size="x-small" color="grey" variant="tonal" prepend-icon="mdi-phone">
                                                        {{ app.phone }}
                                                    </v-chip>
                                                    <v-chip v-if="app.is_external" size="x-small" color="orange" variant="tonal" prepend-icon="mdi-earth">
                                                        External
                                                    </v-chip>
                                                    <v-chip v-if="app.otp_verified" size="x-small" color="success" variant="tonal" prepend-icon="mdi-email-check">
                                                        Email Verified
                                                    </v-chip>
                                                    <v-chip v-else size="x-small" color="warning" variant="tonal" prepend-icon="mdi-email-alert">
                                                        Unverified
                                                    </v-chip>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-center gap-2">
                                            <span class="text-caption text-grey mr-2">{{ formatDateTime(app.created_at) }}</span>
                                            <v-btn color="success" size="small" variant="flat" :loading="approvalLoading === app.id" @click="approveRescuerApplication(app)">
                                                <v-icon start size="small">mdi-check</v-icon>
                                                Approve
                                            </v-btn>
                                            <v-btn color="error" size="small" variant="outlined" :loading="approvalLoading === app.id" @click="openDeclineDialog(app)">
                                                <v-icon start size="small">mdi-close</v-icon>
                                                Decline
                                            </v-btn>
                                        </div>
                                    </div>
                                </div>
                            </v-card-text>
                        </div>
                    </v-expand-transition>
                </v-card>

                <!-- Filters -->
                <v-card rounded="lg" elevation="0" class="mb-3">
                    <v-card-text class="pa-3 pb-2">
                        <v-row dense align="center">
                            <v-col cols="12" sm="4" md="3">
                                <v-text-field
                                    v-model="search"
                                    label="Search rescuers..."
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    prepend-inner-icon="mdi-magnify"
                                    clearable
                                    @input="debouncedSearch"
                                />
                            </v-col>
                            <v-col cols="6" sm="3" md="2">
                                <v-select
                                    v-model="statusFilter"
                                    :items="statusOptions"
                                    item-title="label"
                                    item-value="value"
                                    label="Status"
                                    variant="outlined"
                                    density="compact"
                                    clearable
                                    hide-details
                                    @update:model-value="fetchRescuers"
                                />
                            </v-col>
                            <v-col cols="auto" class="d-flex gap-2">
                                <v-btn variant="outlined" size="small" @click="statusFilter = 'all'; search = ''; fetchRescuers()" title="Reset">
                                    <v-icon size="small">mdi-filter-off</v-icon>
                                </v-btn>
                                <v-menu>
                                    <template v-slot:activator="{ props }">
                                        <v-btn variant="outlined" size="small" v-bind="props" title="Export">
                                            <v-icon size="small">mdi-export-variant</v-icon>
                                        </v-btn>
                                    </template>
                                    <v-list density="compact">
                                        <v-list-item @click="exportRescuers('csv')" prepend-icon="mdi-file-delimited">
                                            <v-list-item-title>Export CSV</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item @click="exportRescuers('xlsx')" prepend-icon="mdi-file-excel">
                                            <v-list-item-title>Export XLSX</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item @click="exportRescuers('pdf')" prepend-icon="mdi-file-pdf-box">
                                            <v-list-item-title>Export PDF</v-list-item-title>
                                        </v-list-item>
                                    </v-list>
                                </v-menu>
                                <v-btn color="primary" size="small" @click="openAddDialog">
                                    <v-icon size="small">mdi-plus</v-icon>
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <!-- Bulk Actions Bar -->
                <v-card v-if="selectedRescuers.length > 0" rounded="lg" elevation="0" class="mb-3 pa-2">
                    <div class="d-flex align-center gap-2 pa-1">
                        <v-chip size="small" color="primary" variant="flat">{{ selectedRescuers.length }} selected</v-chip>
                        <v-btn variant="outlined" color="error" size="small" @click="bulkDeleteConfirm">
                            <v-icon start size="small">mdi-delete</v-icon>
                            Delete
                        </v-btn>
                        <v-btn variant="outlined" size="small" @click="openBulkUpdateDialog">
                            <v-icon start size="small">mdi-pencil</v-icon>
                            Update
                        </v-btn>
                    </div>
                </v-card>

                <!-- Main Table Card -->
                <v-card rounded="lg" elevation="0">

                    <!-- Data Table -->
                    <v-data-table
                        v-model="selectedRescuers"
                        :headers="headers"
                        :items="rescuersList"
                        :loading="loading"
                        v-model:items-per-page="itemsPerPage"
                        v-model:page="page"
                        item-value="id"
                        show-select
                        class="elevation-0"
                        hover
                    >
                        <!-- Checkbox header -->
                        <template v-slot:header.data-table-select="{ allSelected, selectAll, someSelected }">
                            <v-checkbox-btn
                                :model-value="allSelected"
                                :indeterminate="someSelected && !allSelected"
                                color="primary"
                                @click="selectAll(!allSelected)"
                            />
                        </template>

                        <!-- Name Column -->
                        <template v-slot:item.name="{ item }">
                            <div class="d-flex align-center py-2">
                                <v-avatar :color="getStatusColor(item.status)" size="36" class="mr-3">
                                    <v-img v-if="item.profile_picture" :src="getProfilePictureUrl(item.profile_picture)" cover />
                                    <span v-else class="text-white font-weight-medium text-caption">{{ getInitials(item) }}</span>
                                </v-avatar>
                                <span class="font-weight-medium">{{ item.first_name }} {{ item.last_name }}</span>
                            </div>
                        </template>

                        <!-- Email Column -->
                        <template v-slot:item.email="{ item }">
                            <span class="text-body-2">{{ item.email }}</span>
                        </template>

                        <!-- ID Column -->
                        <template v-slot:item.rescuer_id="{ item }">
                            <span class="text-body-2 font-weight-medium">{{ item.rescuer_id || item.id }}</span>
                        </template>

                        <!-- Status Column -->
                        <template v-slot:item.status="{ item }">
                            <v-chip 
                                :color="getStatusColor(item.status)" 
                                size="small" 
                                variant="flat"
                            >
                                {{ formatStatus(item.status) }}
                            </v-chip>
                        </template>

                        <!-- Date Created Column -->
                        <template v-slot:item.created_at="{ item }">
                            <span class="text-body-2 text-grey">{{ formatDateTime(item.created_at) }}</span>
                        </template>

                        <!-- 2FA Auth Column -->
                        <template v-slot:item.two_factor="{ item }">
                            <span :class="item.two_factor_enabled ? 'text-success' : 'text-grey'">
                                {{ item.two_factor_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </template>

                        <!-- Actions Column -->
                        <template v-slot:item.actions="{ item }">
                            <v-menu>
                                <template v-slot:activator="{ props }">
                                    <v-btn icon size="small" variant="text" v-bind="props">
                                        <v-icon>mdi-dots-vertical</v-icon>
                                    </v-btn>
                                </template>
                                <v-list density="compact" min-width="160">
                                    <v-list-item @click="viewProfile(item)" prepend-icon="mdi-account">
                                        <v-list-item-title>View profile</v-list-item-title>
                                    </v-list-item>
                                    <v-list-item @click="openEditDialog(item)" prepend-icon="mdi-pencil">
                                        <v-list-item-title>Edit details</v-list-item-title>
                                    </v-list-item>
                                    <v-divider class="my-1"></v-divider>
                                    <v-list-subheader>Change Status</v-list-subheader>
                                    <v-list-item @click="updateStatus(item, 'available')">
                                        <template v-slot:prepend>
                                            <v-icon color="success" size="small">mdi-check-circle</v-icon>
                                        </template>
                                        <v-list-item-title>Set Available</v-list-item-title>
                                    </v-list-item>
                                    <v-list-item @click="updateStatus(item, 'off_duty')">
                                        <template v-slot:prepend>
                                            <v-icon color="grey" size="small">mdi-sleep</v-icon>
                                        </template>
                                        <v-list-item-title>Set Off Duty</v-list-item-title>
                                    </v-list-item>
                                    <v-list-item @click="updateStatus(item, 'unavailable')">
                                        <template v-slot:prepend>
                                            <v-icon color="error" size="small">mdi-close-circle</v-icon>
                                        </template>
                                        <v-list-item-title>Set Unavailable</v-list-item-title>
                                    </v-list-item>
                                    <v-divider class="my-1"></v-divider>
                                    <v-list-item @click="confirmDelete(item)" class="text-error">
                                        <template v-slot:prepend>
                                            <v-icon color="error" size="small">mdi-delete</v-icon>
                                        </template>
                                        <v-list-item-title>Delete user</v-list-item-title>
                                    </v-list-item>
                                </v-list>
                            </v-menu>
                        </template>

                        <!-- No Data -->
                        <template v-slot:no-data>
                            <div class="text-center py-8">
                                <v-icon size="64" color="grey-lighten-1">mdi-account-off</v-icon>
                                <p class="text-grey mt-4">No rescuers found. Click "Add Rescuer" to create one.</p>
                            </div>
                        </template>
                    </v-data-table>
                </v-card>

                <!-- Recent Activity -->
                <v-card rounded="lg" class="mt-4" elevation="0">
                    <v-card-title class="d-flex align-center justify-space-between pa-4">
                        <div class="d-flex align-center">
                            <v-icon start color="primary">mdi-history</v-icon>
                            <span class="font-weight-bold">Recent Activity</span>
                            <v-chip size="x-small" color="primary" class="ml-2">{{ auditTrail.length }}</v-chip>
                        </div>

                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text class="pa-4">
                        <div v-if="auditTrail.length > 0">
                            <v-timeline density="compact" side="end">
                                <v-timeline-item
                                    v-for="audit in paginatedAuditTrail"
                                    :key="audit.id"
                                    :dot-color="getAuditColor(audit.action)"
                                    size="small"
                                >
                                    <template v-slot:icon>
                                        <v-icon size="12" color="white">{{ getAuditIcon(audit.action) }}</v-icon>
                                    </template>
                                    <v-card variant="tonal" :color="getAuditColor(audit.action)" density="compact" class="mb-1">
                                        <v-card-text class="pa-2">
                                            <div class="d-flex align-center justify-space-between">
                                                <p class="font-weight-medium mb-0 text-body-2">{{ audit.description || audit.action }}</p>
                                                <v-chip size="x-small" :color="getAuditColor(audit.action)" variant="flat">
                                                    {{ formatAction(audit.action) }}
                                                </v-chip>
                                            </div>
                                            <p class="text-caption text-grey mt-1 mb-0">
                                                <v-icon size="12" class="mr-1">mdi-clock-outline</v-icon>
                                                {{ formatDateTime(audit.created_at) }}
                                            </p>
                                        </v-card-text>
                                    </v-card>
                                </v-timeline-item>
                            </v-timeline>
                            
                            <!-- Pagination for Activity -->
                            <v-pagination
                                v-if="totalActivityPages > 1"
                                v-model="activityPage"
                                :length="totalActivityPages"
                                :total-visible="5"
                                density="comfortable"
                                rounded="circle"
                                class="mt-4"
                                size="small"
                            ></v-pagination>
                            
                            <p v-if="auditTrail.length > 0" class="text-caption text-center text-grey mt-2 mb-0">
                                Showing {{ activityStartIndex + 1 }}-{{ activityEndIndex }} of {{ auditTrail.length }} activities
                            </p>
                        </div>
                        <v-alert v-else type="info" variant="tonal" rounded="lg">
                            <v-icon start>mdi-information-outline</v-icon>
                            No recent activity
                        </v-alert>
                    </v-card-text>
                </v-card>
            </v-container>
        </v-main>

        <!-- Add/Edit Single Rescuer Dialog -->
        <v-dialog v-model="dialog" max-width="550">
            <v-card rounded="lg">
                <v-card-title class="d-flex align-center pa-4">
                    <v-icon :color="isEditing ? 'info' : 'primary'" class="mr-2">
                        {{ isEditing ? 'mdi-account-edit' : 'mdi-account-plus' }}
                    </v-icon>
                    {{ isEditing ? 'Edit Rescuer' : 'Add New Rescuer' }}
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <v-form ref="form">
                        <v-row>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="formData.first_name"
                                    label="First Name"
                                    variant="outlined"
                                    density="compact"
                                    :rules="[rules.nameOnly]"
                                    @keypress="preventInvalidNameChars"
                                    @input="sanitizeName('first_name')"
                                />
                            </v-col>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="formData.last_name"
                                    label="Last Name"
                                    variant="outlined"
                                    density="compact"
                                    :rules="[rules.nameOnly]"
                                    @keypress="preventInvalidNameChars"
                                    @input="sanitizeName('last_name')"
                                />
                            </v-col>
                        </v-row>
                        <v-text-field
                            v-model="formData.email"
                            label="Email (@sdca.edu.ph)"
                            type="email"
                            variant="outlined"
                            density="compact"
                            :rules="[
                                v => !!v || 'Email is required', 
                                v => /.+@.+\..+/.test(v) || 'Invalid email',
                                v => v.endsWith('@sdca.edu.ph') || 'Must be an SDCA email (@sdca.edu.ph)'
                            ]"
                            class="mb-3"
                            hint="Must be an SDCA email address"
                            persistent-hint
                            @keypress="preventInvalidEmailChars"
                            @input="sanitizeEmail"
                        />
                        <v-text-field
                            v-model="formData.phone"
                            label="Phone Number"
                            variant="outlined"
                            density="compact"
                            :rules="[rules.phoneNumber]"
                            hint="Mobile number (11 digits starting with 09)"
                            persistent-hint
                            placeholder="09171234567"
                            class="mb-3"
                            type="tel"
                            inputmode="numeric"
                            maxlength="11"
                            @keypress="preventNonDigits"
                            @input="sanitizePhone"
                        />
                        <v-text-field
                            v-model="formData.rescuer_id"
                            label="Rescuer ID (9 digits)"
                            variant="outlined"
                            density="compact"
                            :rules="[rules.rescuerId]"
                            :hint="`Must be exactly 9 digits (${(formData.rescuer_id || '').replace(/\D/g, '').length}/9)`"
                            persistent-hint
                            placeholder="123456789"
                            class="mb-3"
                            type="text"
                            inputmode="numeric"
                            maxlength="9"
                            @keypress="preventNonDigits"
                            @input="sanitizeRescuerId"
                        />
                        
                        <!-- OTP Activation Notice for new rescuers -->
                        <v-alert 
                            v-if="!isEditing" 
                            type="info" 
                            variant="tonal" 
                            class="mb-3"
                            density="compact"
                        >
                            <span class="text-body-2">
                                An email with OTP verification will be sent to the rescuer. 
                                Account will be <strong>pending</strong> until email is verified and password is changed.
                            </span>
                        </v-alert>
                    </v-form>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="saving" @click="saveRescuer">
                        {{ isEditing ? 'Update' : 'Create & Send OTP' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Bulk Update Dialog -->
        <v-dialog v-model="bulkUpdateDialog" max-width="400">
            <v-card rounded="lg">
                <v-card-title class="pa-4">
                    <v-icon color="info" class="mr-2">mdi-account-multiple-check</v-icon>
                    Bulk Update Status
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <p class="mb-4">Update status for {{ selectedRescuers.length }} selected rescuer(s):</p>
                    <v-select
                        v-model="bulkUpdateStatus"
                        :items="['available', 'off_duty', 'unavailable']"
                        label="New Status"
                        variant="outlined"
                        density="compact"
                    />
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="bulkUpdateDialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="bulkUpdating" @click="processBulkUpdate">
                        Update All
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card rounded="lg">
                <v-card-title class="text-error pa-4">
                    <v-icon start color="error">mdi-alert</v-icon>
                    Delete Rescuer
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    Are you sure you want to delete <strong>{{ selectedRescuer?.first_name }} {{ selectedRescuer?.last_name }}</strong>? This action cannot be undone.
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" :loading="deleting" @click="deleteRescuer">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Bulk Delete Confirmation -->
        <v-dialog v-model="bulkDeleteDialog" max-width="400">
            <v-card rounded="lg">
                <v-card-title class="text-error pa-4">
                    <v-icon start color="error">mdi-alert</v-icon>
                    Delete Multiple Rescuers
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    Are you sure you want to delete <strong>{{ selectedRescuers.length }}</strong> rescuer(s)? This action cannot be undone.
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="bulkDeleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" :loading="bulkDeleting" @click="processBulkDelete">Delete All</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- View Profile Dialog -->
        <v-dialog v-model="profileDialog" max-width="500">
            <v-card v-if="viewingRescuer" rounded="lg">
                <v-card-title class="pa-4">
                    <v-icon color="primary" class="mr-2">mdi-account</v-icon>
                    Rescuer Profile
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <div class="text-center mb-4">
                        <v-avatar :color="getStatusColor(viewingRescuer.status)" size="80">
                            <v-img v-if="viewingRescuer.profile_picture" :src="getProfilePictureUrl(viewingRescuer.profile_picture)" cover />
                            <span v-else class="text-h4 text-white">{{ getInitials(viewingRescuer) }}</span>
                        </v-avatar>
                        <h3 class="mt-3 text-h6">{{ viewingRescuer.first_name }} {{ viewingRescuer.last_name }}</h3>
                        <v-chip :color="getStatusColor(viewingRescuer.status)" size="small" class="mt-2">
                            {{ formatStatus(viewingRescuer.status) }}
                        </v-chip>
                    </div>
                    <v-list density="compact">
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-email</v-icon>
                            </template>
                            <v-list-item-title>{{ viewingRescuer.email }}</v-list-item-title>
                            <v-list-item-subtitle>Email</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-phone</v-icon>
                            </template>
                            <v-list-item-title>{{ viewingRescuer.phone || viewingRescuer.phone_number || viewingRescuer.contact_number || 'Not provided' }}</v-list-item-title>
                            <v-list-item-subtitle>Phone</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-calendar</v-icon>
                            </template>
                            <v-list-item-title>{{ formatDateTime(viewingRescuer.created_at) }}</v-list-item-title>
                            <v-list-item-subtitle>Date Created</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="profileDialog = false">Close</v-btn>
                    <v-btn color="primary" @click="profileDialog = false; openEditDialog(viewingRescuer)">
                        <v-icon start>mdi-pencil</v-icon>
                        Edit
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Decline Rescuer Application Dialog -->
        <v-dialog v-model="declineDialog" max-width="450">
            <v-card rounded="lg">
                <v-card-title class="text-error pa-4">
                    <v-icon start color="error">mdi-account-cancel</v-icon>
                    Decline Rescuer Application
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <p class="mb-3">
                        Are you sure you want to decline the application from
                        <strong>{{ declineTarget?.first_name }} {{ declineTarget?.last_name }}</strong>?
                    </p>
                    <p class="text-caption text-grey mb-3">
                        The applicant will receive an email notification and their account will be removed.
                    </p>
                    <v-textarea
                        v-model="declineReason"
                        label="Reason for declining (optional)"
                        variant="outlined"
                        density="compact"
                        rows="3"
                        placeholder="Provide a reason that will be sent to the applicant..."
                        hide-details
                    />
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="declineDialog = false">Cancel</v-btn>
                    <v-btn color="error" :loading="approvalLoading === declineTarget?.id" @click="declineRescuerApplication">
                        <v-icon start>mdi-close</v-icon>
                        Decline
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000" location="bottom right">
            {{ snackbarText }}
            <template v-slot:actions>
                <v-btn variant="text" @click="snackbar = false">Close</v-btn>
            </template>
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { useDisplay } from 'vuetify';
import AdminAppBar from '@/Components/AdminAppBar.vue';
const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);

import { ref, computed, onUnmounted } from 'vue';
import * as XLSX from 'xlsx';
import { getProfilePictureUrl } from '@/Composables/useApi';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

const props = defineProps({
    rescuers: { type: Object, default: () => ({ data: [] }) },
    counts: { type: Object, default: () => ({ total: 0, available: 0, on_rescue: 0, off_duty: 0, unavailable: 0 }) },
    auditTrail: { type: Array, default: () => [] }
});

// State
const loading = ref(false);
const dialog = ref(false);
const bulkUpdateDialog = ref(false);
const deleteDialog = ref(false);
const bulkDeleteDialog = ref(false);
const profileDialog = ref(false);
const isEditing = ref(false);
const saving = ref(false);
const bulkUpdating = ref(false);
const deleting = ref(false);
const bulkDeleting = ref(false);
const search = ref('');
const statusFilter = ref('all');
const selectedRescuer = ref(null);
const viewingRescuer = ref(null);
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarColor = ref('success');
const page = ref(1);
const itemsPerPage = ref(10);
const sortBy = ref('name');
const sortOrder = ref('asc');
const selectedRescuers = ref([]);

// Bulk update
const bulkUpdateStatus = ref('available');

// Data
// Export rescuers as CSV, XLSX, or PDF
const exportRescuers = (format = 'csv') => {
    if (format === 'pdf') {
        // Export as PDF using jsPDF
        const doc = new jsPDF();
        const headers = ['First Name', 'Last Name', 'Email', 'Phone', 'Status', 'Created'];
        const data = rescuersList.value.map(r => [
            r.first_name,
            r.last_name,
            r.email,
            r.phone || '',
            r.status,
            r.created_at
        ]);
        autoTable(doc, {
            head: [headers],
            body: data,
            styles: { fontSize: 8 },
            headStyles: { fillColor: [33, 150, 243] },
            margin: { top: 20 }
        });
        doc.save('rescuers_export.pdf');
    } else {
        // Export as CSV or XLSX
        const data = rescuersList.value.map(r => ({
            'First Name': r.first_name,
            'Last Name': r.last_name,
            'Email': r.email,
            'Phone': r.phone || '',
            'Status': r.status,
            'Created': r.created_at
        }));
        const worksheet = XLSX.utils.json_to_sheet(data);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Rescuers');
        if (format === 'csv') {
            XLSX.writeFile(workbook, 'rescuers_export.csv');
        } else {
            XLSX.writeFile(workbook, 'rescuers_export.xlsx');
        }
    }
};

const exportPDF = () => {
    const doc = new jsPDF();
    const columns = [
        { header: 'First Name', dataKey: 'first_name' },
        { header: 'Last Name', dataKey: 'last_name' },
        { header: 'Email', dataKey: 'email' },
        { header: 'Phone', dataKey: 'phone' },
        { header: 'Status', dataKey: 'status' },
        { header: 'Created', dataKey: 'created_at' }
    ];
    const rows = rescuersList.value.map(r => ({
        first_name: r.first_name,
        last_name: r.last_name,
        email: r.email,
        phone: r.phone || '',
        status: r.status,
        created_at: r.created_at
    }));
    autoTable(doc, {
        columns,
        body: rows,
        styles: { fontSize: 8 },
        headStyles: { fillColor: [25, 118, 210] },
        margin: { top: 20 }
    });
    doc.save('rescuers_export.pdf');
};
const rescuersList = ref(props.rescuers?.data || []);
const counts = ref(props.counts);
const auditTrail = ref(props.auditTrail || []);

// Pending Rescuer Applications
const pendingApplications = ref([]);
const pendingExpanded = ref(true);
const approvalLoading = ref(null);
const declineDialog = ref(false);
const declineTarget = ref(null);
const declineReason = ref('');

// Fetch pending rescuer applications
const fetchPendingApplications = async () => {
    try {
        const response = await fetch('/admin/rescuers/pending', {
            headers: { 
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        const data = await response.json();
        if (data.success) {
            pendingApplications.value = data.data || [];
        }
    } catch (error) {
        console.error('Error fetching pending applications:', error);
    }
};

// Approve rescuer application
const approveRescuerApplication = async (app) => {
    approvalLoading.value = app.id;
    try {
        const response = await fetch(`/admin/rescuers/${app.id}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        const data = await response.json();
        if (data.success) {
            showSnackbar(`${app.first_name} ${app.last_name} has been approved as a rescuer!`, 'success');
            pendingApplications.value = pendingApplications.value.filter(a => a.id !== app.id);
            fetchRescuers(); // Refresh the rescuer list
        } else {
            showSnackbar(data.message || 'Error approving rescuer', 'error');
        }
    } catch (error) {
        console.error('Error approving rescuer:', error);
        showSnackbar('Error approving rescuer', 'error');
    } finally {
        approvalLoading.value = null;
    }
};

// Open decline dialog
const openDeclineDialog = (app) => {
    declineTarget.value = app;
    declineReason.value = '';
    declineDialog.value = true;
};

// Decline rescuer application
const declineRescuerApplication = async () => {
    if (!declineTarget.value) return;
    const app = declineTarget.value;
    approvalLoading.value = app.id;
    try {
        const response = await fetch(`/admin/rescuers/${app.id}/decline`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ reason: declineReason.value || undefined })
        });
        const data = await response.json();
        if (data.success) {
            showSnackbar(`Application from ${app.first_name} ${app.last_name} has been declined`, 'warning');
            pendingApplications.value = pendingApplications.value.filter(a => a.id !== app.id);
            declineDialog.value = false;
            fetchRescuers();
        } else {
            showSnackbar(data.message || 'Error declining rescuer', 'error');
        }
    } catch (error) {
        console.error('Error declining rescuer:', error);
        showSnackbar('Error declining rescuer', 'error');
    } finally {
        approvalLoading.value = null;
    }
};

// Load pending applications on mount
import { onMounted } from 'vue';

let rescuerPollingInterval = null;
const RESCUER_POLLING_INTERVAL = 10000; // 10 seconds

const silentFetchRescuers = async () => {
    try {
        const params = new URLSearchParams();
        if (search.value) params.append('search', search.value);
        if (statusFilter.value !== 'all') params.append('status', statusFilter.value);
        
        const response = await fetch(`/admin/rescuers?${params}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
            rescuersList.value = data.data.data || data.data;
            counts.value = data.counts;
            if (data.audit_trail) {
                auditTrail.value = data.audit_trail;
            }
        }
    } catch (error) {
        // Silent fail for polling
    }
};

onMounted(() => {
    fetchPendingApplications();
    // Poll rescuer data to keep status updated
    rescuerPollingInterval = setInterval(silentFetchRescuers, RESCUER_POLLING_INTERVAL);
});

onUnmounted(() => {
    if (rescuerPollingInterval) {
        clearInterval(rescuerPollingInterval);
        rescuerPollingInterval = null;
    }
});

// Activity pagination
const activityPage = ref(1);
const activityPerPage = ref(5);


const formData = ref({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    rescuer_id: '',
    status: 'available'
});

// Validation rules
const rules = {
    required: (v) => !!v || 'Required',
    // Name validation - only letters, spaces, hyphens, periods, commas, apostrophes
    nameOnly: (v) => {
        if (!v) return 'Required';
        if (/[0-9]/.test(v)) {
            return 'Names cannot contain numbers';
        }
        // Block emoji and special characters
        if (/[^a-zA-Z\s\-\.\'\,ñÑ]/.test(v)) {
            return 'Names can only contain letters, spaces, hyphens, and apostrophes';
        }
        return true;
    },
    // Phone number validation - only accepts 11 numeric digits
    phoneNumber: (v) => {
        if (!v) return true; // Optional field
        
        // Remove all non-digit characters
        const cleaned = v.replace(/\D/g, '');
        
        // Must be exactly 11 digits and start with 09
        if (cleaned.length !== 11) {
            return 'Must be exactly 11 digits';
        }
        
        if (!cleaned.startsWith('09')) {
            return 'Must start with 09';
        }
        
        // Ensure it's purely numeric (no letters)
        if (!/^\d{11}$/.test(cleaned)) {
            return 'Must contain only numbers';
        }
        
        return true;
    },
    // Rescuer ID validation - exactly 9 digits
    rescuerId: (v) => {
        if (!v) return 'Rescuer ID is required';
        
        // Remove all non-digit characters
        const cleaned = v.replace(/\D/g, '');
        
        // Must be exactly 9 digits
        if (cleaned.length !== 9) {
            return `Rescuer ID must be exactly 9 digits (${cleaned.length}/9)`;
        }
        
        // Ensure it's purely numeric (no letters)
        if (!/^\d{9}$/.test(cleaned)) {
            return 'Rescuer ID must contain only numbers';
        }
        
        return true;
    }
};

// Activity pagination computed
const totalActivityPages = computed(() => {
    return Math.ceil(auditTrail.value.length / activityPerPage.value);
});

const activityStartIndex = computed(() => {
    return (activityPage.value - 1) * activityPerPage.value;
});

const activityEndIndex = computed(() => {
    return Math.min(activityStartIndex.value + activityPerPage.value, auditTrail.value.length);
});

const paginatedAuditTrail = computed(() => {
    return auditTrail.value.slice(activityStartIndex.value, activityEndIndex.value);
});

// Table headers
const headers = [
    { title: 'Name', key: 'name', sortable: true },
    { title: 'Email', key: 'email', sortable: true },
    { title: 'ID', key: 'rescuer_id', sortable: false, width: '100px' },
    { title: 'Status', key: 'status', sortable: true },
    { title: 'Date created', key: 'created_at', sortable: true },
    { title: '', key: 'actions', sortable: false, align: 'end', width: '50px' }
];

const statusOptions = [
    { label: 'All Status', value: 'all' },
    { label: 'Available', value: 'available' },
    { label: 'On Rescue', value: 'on_rescue' },
    { label: 'Off Duty', value: 'off_duty' },
    { label: 'Unavailable', value: 'unavailable' },
    { label: 'Pending Approval', value: 'pending' }
];

// Methods
let searchTimeout = null;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(fetchRescuers, 500);
};

const fetchRescuers = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (search.value) params.append('search', search.value);
        if (statusFilter.value !== 'all') params.append('status', statusFilter.value);
        
        const response = await fetch(`/admin/rescuers?${params}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
            rescuersList.value = data.data.data || data.data;
            counts.value = data.counts;
            if (data.audit_trail) {
                auditTrail.value = data.audit_trail;
            }
        }
    } catch (error) {
        console.error('Error fetching rescuers:', error);
        showSnackbar('Error fetching rescuers', 'error');
    } finally {
        loading.value = false;
    }
};

const sortRescuers = () => {
    rescuersList.value.sort((a, b) => {
        let comparison = 0;
        if (sortBy.value === 'name') {
            const nameA = `${a.first_name} ${a.last_name}`.toLowerCase();
            const nameB = `${b.first_name} ${b.last_name}`.toLowerCase();
            comparison = nameA.localeCompare(nameB);
        } else if (sortBy.value === 'created_at') {
            comparison = new Date(a.created_at) - new Date(b.created_at);
        }
        return sortOrder.value === 'asc' ? comparison : -comparison;
    });
};

const resetFilters = () => {
    search.value = '';
    statusFilter.value = 'all';
    fetchRescuers();
};

const openAddDialog = () => {
    isEditing.value = false;
    formData.value = {
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        rescuer_id: '',
        status: 'available'
    };
    dialog.value = true;
};

const openEditDialog = (rescuer) => {
    isEditing.value = true;
    selectedRescuer.value = rescuer;
    formData.value = { ...rescuer };
    dialog.value = true;
};

const openBulkUpdateDialog = () => {
    bulkUpdateStatus.value = 'available';
    bulkUpdateDialog.value = true;
};

const viewProfile = (rescuer) => {
    viewingRescuer.value = rescuer;
    profileDialog.value = true;
};

const saveRescuer = async () => {
    // Validate rescuer ID - required
    const rescuerIdValidation = rules.rescuerId(formData.value.rescuer_id);
    if (rescuerIdValidation !== true) {
        showSnackbar(rescuerIdValidation, 'error');
        return;
    }
    
    // Validate phone number if provided
    if (formData.value.phone) {
        const phoneValidation = rules.phoneNumber(formData.value.phone);
        if (phoneValidation !== true) {
            showSnackbar(phoneValidation, 'error');
            return;
        }
    }
    
    saving.value = true;
    try {
        const url = isEditing.value ? `/admin/rescuers/${selectedRescuer.value.id}` : '/admin/rescuers';
        const method = isEditing.value ? 'PUT' : 'POST';
        
        // Clean rescuer_id to only digits
        const cleanedRescuerId = (formData.value.rescuer_id || '').replace(/\D/g, '');
        
        const submitData = {
            ...formData.value,
            rescuer_id: cleanedRescuerId,
            send_otp: !isEditing.value
        };
        
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(submitData)
        });
        
        const data = await response.json();
        if (data.success) {
            showSnackbar(isEditing.value ? 'Rescuer updated successfully' : 'Rescuer created successfully', 'success');
            dialog.value = false;
            fetchRescuers();
        } else {
            showSnackbar(data.errors ? Object.values(data.errors).flat().join(', ') : 'Error saving rescuer', 'error');
        }
    } catch (error) {
        console.error('Error saving rescuer:', error);
        showSnackbar('Error saving rescuer', 'error');
    } finally {
        saving.value = false;
    }
};

const updateStatus = async (rescuer, status) => {
    try {
        // Validate status change
        if (status === 'on_rescue') {
            showSnackbar('Cannot manually set rescuer to "On Rescue". This status is set automatically when they accept a rescue.', 'warning');
            return;
        }
        
        const response = await fetch(`/admin/rescuers/${rescuer.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ status })
        });
        
        const data = await response.json();
        if (data.success) {
            // Update local data immediately
            const index = rescuersList.value.findIndex(r => r.id === rescuer.id);
            if (index !== -1) {
                rescuersList.value[index].status = status;
            }
            
            // Show appropriate message based on status
            const statusMessages = {
                'available': 'Rescuer is now available and can accept rescue requests',
                'off_duty': 'Rescuer is now off duty and cannot accept rescue requests',
                'unavailable': 'Rescuer is now unavailable and cannot accept rescue requests'
            };
            
            showSnackbar(statusMessages[status] || `Status updated to ${formatStatus(status)}`, 'success');
            
            // Refresh to get updated counts
            fetchRescuers();
        } else {
            showSnackbar(data.message || 'Error updating status', 'error');
        }
    } catch (error) {
        console.error('Error updating status:', error);
        showSnackbar('Error updating status', 'error');
    }
};

const confirmDelete = (rescuer) => {
    selectedRescuer.value = rescuer;
    deleteDialog.value = true;
};

const deleteRescuer = async () => {
    deleting.value = true;
    try {
        const response = await fetch(`/admin/rescuers/${selectedRescuer.value.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        
        const data = await response.json();
        if (data.success) {
            showSnackbar('Rescuer deleted successfully', 'success');
            deleteDialog.value = false;
            fetchRescuers();
        }
    } catch (error) {
        console.error('Error deleting rescuer:', error);
        showSnackbar('Error deleting rescuer', 'error');
    } finally {
        deleting.value = false;
    }
};

// Bulk operations
const bulkDeleteConfirm = () => {
    bulkDeleteDialog.value = true;
};

const processBulkDelete = async () => {
    bulkDeleting.value = true;
    try {
        for (const id of selectedRescuers.value) {
            await fetch(`/admin/rescuers/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });
        }
        showSnackbar(`${selectedRescuers.value.length} rescuer(s) deleted successfully`, 'success');
        bulkDeleteDialog.value = false;
        selectedRescuers.value = [];
        fetchRescuers();
    } catch (error) {
        console.error('Error bulk deleting:', error);
        showSnackbar('Error deleting rescuers', 'error');
    } finally {
        bulkDeleting.value = false;
    }
};

const processBulkUpdate = async () => {
    bulkUpdating.value = true;
    try {
        for (const id of selectedRescuers.value) {
            await fetch(`/admin/rescuers/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({ status: bulkUpdateStatus.value })
            });
        }
        showSnackbar(`${selectedRescuers.value.length} rescuer(s) updated successfully`, 'success');
        bulkUpdateDialog.value = false;
        selectedRescuers.value = [];
        fetchRescuers();
    } catch (error) {
        console.error('Error bulk updating:', error);
        showSnackbar('Error updating rescuers', 'error');
    } finally {
        bulkUpdating.value = false;
    }
};

const showSnackbar = (text, color) => {
    snackbarText.value = text;
    snackbarColor.value = color;
    snackbar.value = true;
};

// Prevent invalid characters in name fields (only allow letters, spaces, hyphens, apostrophes, ñ)
const preventInvalidNameChars = (event) => {
    const char = String.fromCharCode(event.keyCode || event.which);
    if (!/[a-zA-ZñÑ\s\-\.\,\']/.test(char)) {
        event.preventDefault();
    }
};

// Sanitize name input - strip emoji and invalid characters on paste/input
const sanitizeName = (field) => {
    if (formData.value[field]) {
        formData.value[field] = formData.value[field].replace(/[^a-zA-ZñÑ\s\-\.\,\']/g, '');
    }
};

// Prevent non-digit characters in numeric fields (phone, ID)
const preventNonDigits = (event) => {
    const char = String.fromCharCode(event.keyCode || event.which);
    if (!/[0-9]/.test(char)) {
        event.preventDefault();
    }
};

// Sanitize phone input - strip anything that isn't a digit (including emoji)
const sanitizePhone = () => {
    if (formData.value.phone) {
        formData.value.phone = formData.value.phone.replace(/\D/g, '').slice(0, 11);
    }
};

// Sanitize rescuer ID input - strip anything that isn't a digit
const sanitizeRescuerId = () => {
    if (formData.value.rescuer_id) {
        formData.value.rescuer_id = formData.value.rescuer_id.replace(/\D/g, '').slice(0, 9);
    }
};

// Prevent invalid characters in email field
const preventInvalidEmailChars = (event) => {
    const char = String.fromCharCode(event.keyCode || event.which);
    if (!/[a-zA-Z0-9@._\-+]/.test(char)) {
        event.preventDefault();
    }
};

// Sanitize email input - strip emoji and invalid characters
const sanitizeEmail = () => {
    if (formData.value.email) {
        formData.value.email = formData.value.email.replace(/[^a-zA-Z0-9@._\-+]/g, '');
    }
};

// Helpers
const getInitials = (rescuer) => {
    return `${rescuer.first_name?.[0] || ''}${rescuer.last_name?.[0] || ''}`.toUpperCase();
};

const getStatusColor = (status) => {
    const colors = {
        available: 'success',
        on_rescue: 'warning',
        off_duty: 'grey',
        unavailable: 'error',
        pending: 'info',
        inactive: 'grey-darken-1',
        active: 'success' // Map active to success color
    };
    return colors[status] || 'grey';
};

const formatStatus = (status) => {
    const labels = {
        available: 'Available',
        on_rescue: 'On Rescue',
        off_duty: 'Off Duty',
        unavailable: 'Unavailable',
        pending: 'Pending Activation',
        inactive: 'Inactive',
        active: 'Available' // Display 'Available' instead of 'Active'
    };
    return labels[status] || status?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || 'Unknown';
};

const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getAuditColor = (action) => {
    const colors = { create: 'success', update: 'info', delete: 'error' };
    return colors[action] || 'grey';
};

const getAuditIcon = (action) => {
    const icons = { 
        create: 'mdi-plus', 
        update: 'mdi-pencil', 
        delete: 'mdi-delete',
        login: 'mdi-login',
        logout: 'mdi-logout'
    };
    return icons[action] || 'mdi-information';
};

const formatAction = (action) => {
    const labels = { 
        create: 'Created', 
        update: 'Updated', 
        delete: 'Deleted',
        login: 'Login',
        logout: 'Logout'
    };
    return labels[action] || action;
};

</script>

<style scoped>
/* Gradient Text */
.gradient-text {
    background: linear-gradient(135deg, #1976D2, #0D47A1);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ===== Stats Banner ===== */
.rescuers-stats-banner {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 50%, #0D47A1 100%) !important;
}
.stats-banner-grid {
    display: flex;
    align-items: center;
    justify-content: space-around;
    flex-wrap: wrap;
    padding: 12px 16px;
    gap: 8px;
}
.stat-inline {
    text-align: center;
    min-width: 60px;
    padding: 4px 6px;
}
.stat-inline-icon {
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 2px;
}
.stat-inline-icon.green-icon { color: #A5D6A7; }
.stat-inline-icon.amber-icon { color: #FFD54F; }
.stat-inline-icon.red-icon { color: #EF9A9A; }
.stat-inline-icon.orange-icon { color: #FFCC80; }
.stat-inline-value {
    font-size: 20px;
    font-weight: 800;
    color: white;
    line-height: 1.2;
}
.stat-inline-label {
    font-size: 10px;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Gap utility */
.gap-2 {
    gap: 8px;
}
.gap-3 {
    gap: 12px;
}

/* Pending Applications */
.pending-card {
    border-left: 4px solid #E65100;
}

.pending-item {
    background: linear-gradient(135deg, rgba(255, 152, 0, 0.04), rgba(230, 81, 0, 0.03));
    border: 1px solid rgba(255, 152, 0, 0.15);
    transition: all 0.2s ease;
}

.pending-item:hover {
    background: linear-gradient(135deg, rgba(255, 152, 0, 0.08), rgba(230, 81, 0, 0.05));
    box-shadow: 0 2px 8px rgba(255, 152, 0, 0.12);
}

.text-orange {
    color: #E65100 !important;
}
</style>
