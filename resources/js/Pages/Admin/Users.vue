<template>
    <v-app class="bg-grey-lighten-4">

        <!-- Admin App Bar -->
        <AdminAppBar activePage="users" />

        <!-- Main Content -->
        <v-main>
            <v-container fluid :class="isMobile ? 'pa-3' : 'pa-6'">
                <!-- Page Header -->
                <div class="d-flex align-center mb-4">
                    <div>
                        <h1 :class="isMobile ? 'text-h5' : 'text-h4'" class="font-weight-bold gradient-text">User Management</h1>
                        <p class="text-grey mt-1 text-body-2">Manage patient/visitor accounts</p>
                    </div>
                </div>


                <!-- Stats Banner -->
                <v-card rounded="lg" class="users-stats-banner mb-3" elevation="0">
                    <div class="stats-banner-grid">
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon">mdi-account-group</v-icon>
                            <div class="stat-inline-value">{{ stats.total || 0 }}</div>
                            <div class="stat-inline-label">Total Users</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon blue-icon">mdi-school</v-icon>
                            <div class="stat-inline-value">{{ stats.by_role?.student || 0 }}</div>
                            <div class="stat-inline-label">Patients/Visitors</div>
                        </div>    
                        <!--- Removed Clinical Personnel and Hospital Staff                
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon purple-icon">mdi-human-male-board</v-icon>
                            <div class="stat-inline-value">{{ stats.by_role?.faculty || 0 }}</div>
                            <div class="stat-inline-label">Clinical Personnel</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon teal-icon">mdi-briefcase</v-icon>
                            <div class="stat-inline-value">{{ stats.by_role?.staff || 0 }}</div>
                            <div class="stat-inline-label">Hospital Staff</div>
                        </div>
                        -->
                    </div>
                </v-card>

                <!-- Filters -->
                <v-card rounded="lg" elevation="0" class="mb-3">
                    <v-card-text class="pa-3 pb-2">
                        <v-row dense align="center">
                            <v-col cols="12" sm="4" md="3">
                                <v-text-field
                                    v-model="search"
                                    label="Search users..."
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
                                    v-model="roleFilter"
                                    :items="roleOptions"
                                    item-title="label"
                                    item-value="value"
                                    label="Role"
                                    variant="outlined"
                                    density="compact"
                                    clearable
                                    hide-details
                                    @update:model-value="fetchUsers"
                                />
                            </v-col>
                            <v-col cols="auto" class="d-flex gap-2">
                                <v-btn variant="outlined" size="small" @click="roleFilter = 'all'; search = ''; fetchUsers()" title="Reset">
                                    <v-icon size="small">mdi-filter-off</v-icon>
                                </v-btn>
                                <v-menu>
                                    <template v-slot:activator="{ props }">
                                        <v-btn variant="outlined" size="small" v-bind="props" title="Export">
                                            <v-icon size="small">mdi-export-variant</v-icon>
                                        </v-btn>
                                    </template>
                                    <v-list density="compact">
                                        <v-list-item @click="handleExport('csv')" prepend-icon="mdi-file-delimited">
                                            <v-list-item-title>Export CSV</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item @click="handleExport('xlsx')" prepend-icon="mdi-file-excel">
                                            <v-list-item-title>Export XLSX</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item @click="handleExport('pdf')" prepend-icon="mdi-file-pdf-box">
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
                <v-card v-if="selectedUsers.length > 0" rounded="lg" elevation="0" class="mb-3 pa-2">
                    <div class="d-flex align-center gap-2 pa-1">
                        <v-chip size="small" color="primary" variant="flat">{{ selectedUsers.length }} selected</v-chip>
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
                        v-model="selectedUsers"
                        :headers="headers"
                        :items="usersList"
                        :loading="loading"
                        v-model:items-per-page="itemsPerPage"
                        v-model:page="page"
                        item-value="id"
                        show-select
                        class="elevation-0"
                        hover
                    >
                        <!-- Name Column -->
                        <template v-slot:item.name="{ item }">
                            <div class="d-flex align-center py-2">
                                <v-avatar :color="getAvatarColor(item.role)" size="36" class="mr-3">
                                    <v-img v-if="item.profile_picture" :src="getProfilePictureUrl(item.profile_picture, item.updated_at)" cover />
                                    <span v-else class="text-white font-weight-medium text-caption">{{ getInitials(item) }}</span>
                                </v-avatar>
                                <div>
                                    <p class="font-weight-medium mb-0">{{ item.first_name }} {{ item.last_name }}</p>
                                    <p class="text-caption text-grey mb-0">{{ item.email }}</p>
                                </div>
                            </div>
                        </template>

                        <!-- Role Column -->
                        <template v-slot:item.role="{ item }">
                            <v-chip :color="getRoleColor(item.role)" size="small" variant="flat">
                                {{ formatRole(item.role) }}
                            </v-chip>
                        </template>

                        <!-- Status Column -->
                        <template v-slot:item.status="{ item }">
                            <v-chip 
                                :color="getStatusColor(item.status)" 
                                size="small" 
                                :variant="item.status === 'pending' ? 'flat' : 'outlined'"
                            >
                                <v-icon v-if="item.status === 'pending'" start size="small">mdi-clock-outline</v-icon>
                                {{ formatStatus(item.status) }}
                            </v-chip>
                        </template>

                        <!-- ID Number Column -->
                        <template v-slot:item.id_number="{ item }">
                            <span class="text-body-2">{{ item.student_id || item.faculty_id || item.staff_id || '-' }}</span>
                        </template>

                        <!-- Date Created Column -->
                        <template v-slot:item.created_at="{ item }">
                            <span class="text-body-2 text-grey">{{ formatDateTime(item.created_at) }}</span>
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
                                <p class="text-grey mt-4">No users found. Click "Add User" to create one.</p>
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

        <!-- Add/Edit Single User Dialog -->
        <v-dialog v-model="dialog" max-width="550">
            <v-card rounded="lg">
                <v-card-title class="d-flex align-center pa-4">
                    <v-icon :color="isEditing ? 'info' : 'primary'" class="mr-2">
                        {{ isEditing ? 'mdi-account-edit' : 'mdi-account-plus' }}
                    </v-icon>
                    {{ isEditing ? 'Edit User' : 'Add New User' }}
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <v-form ref="form" v-model="formValid">
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
                            label="Email (Optional)"
                            type="email"
                            variant="outlined"
                            density="compact"
                            :rules="[
                                v => !v || /.+@.+\..+/.test(v) || 'Invalid email'
                            /*  v => !!v || 'Email is required',
                                v => /.+@.+\..+/.test(v) || 'Invalid email' */
                            ]"
                            class="mb-3"
                            hint="Accepts institutional or external email addresses"
                            persistent-hint
                            @keypress="preventInvalidEmailChars"
                            @input="sanitizeEmail"
                        />
        
                        <v-select
                            v-model="formData.role"
                            :items="roleSelectOptions"
                            item-title="label"
                            item-value="value"
                            label="Role"
                            variant="outlined"
                            density="compact"
                            :rules="[v => !!v || 'Role is required']"
                            class="mb-3"
                        />
                        
                        <!-- ID Number Field (shown for all roles) -->
                        <v-text-field
                            v-model="formData.id_number"
                            @input="onIdNumberInput"
                            @keypress="onIdNumberKeypress"
                            :label="getIdLabel(formData.role)"
                            variant="outlined"
                            density="compact"
                            :rules="isIdRequired(formData.role) ? [rules.idNumber] : []"
                            :hint="isIdRequired(formData.role) ? `Must be exactly 9 digits (${(formData.id_number || '').length}/9)` : 'Optional for patient/visitor accounts'"
                            persistent-hint
                            class="mb-3"
                            :counter="9"
                            maxlength="9"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            placeholder="123456789"
                            :error="isIdRequired(formData.role) && formData.id_number && formData.id_number.length !== 9"
                        />
                        
                        <v-text-field
                            v-model="formData.phone"
                            label="Phone Number"
                            variant="outlined"
                            density="compact"
                            :rules="[rules.phoneNumber]"
                            hint="Mobile number (e.g., 09171234567)"
                            persistent-hint
                            placeholder="09171234567"
                            class="mb-3"
                            type="tel"
                            inputmode="numeric"
                            maxlength="11"
                            @keypress="preventNonDigits"
                            @input="sanitizePhone"
                        />
                        
                        <!-- OTP Activation Notice for new users -->
                        <v-alert 
                            v-if="!isEditing" 
                            type="info" 
                            variant="tonal" 
                            class="mb-3"
                            density="compact"
                        >
                            <span class="text-body-2">
                                An email with OTP verification will be sent to the user. 
                                Account will be <strong>pending</strong> until email is verified and password is changed.
                            </span>
                        </v-alert>
                        
                        <!-- Status selector for editing -->
                        <v-select
                            v-if="isEditing"
                            v-model="formData.status"
                            :items="statusSelectOptions"
                            item-title="label"
                            item-value="value"
                            label="Account Status"
                            variant="outlined"
                            density="compact"
                            class="mb-3"
                        />
                    </v-form>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="saving" :disabled="!isFormValid" @click="saveUser">
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
                    <p class="mb-4">Update status for {{ selectedUsers.length }} selected user(s):</p>
                    <v-select
                        v-model="bulkUpdateStatus"
                        :items="statusSelectOptions"
                        item-title="label"
                        item-value="value"
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
                    Delete User
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    Are you sure you want to delete <strong>{{ selectedUser?.first_name }} {{ selectedUser?.last_name }}</strong>? This action cannot be undone.
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" :loading="deleting" @click="deleteUser">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Bulk Delete Confirmation -->
        <v-dialog v-model="bulkDeleteDialog" max-width="400">
            <v-card rounded="lg">
                <v-card-title class="text-error pa-4">
                    <v-icon start color="error">mdi-alert</v-icon>
                    Delete Multiple Users
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    Are you sure you want to delete <strong>{{ selectedUsers.length }}</strong> user(s)? This action cannot be undone.
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
            <v-card v-if="viewingUser" rounded="lg">
                <v-card-title class="pa-4">
                    <v-icon color="primary" class="mr-2">mdi-account</v-icon>
                    User Profile
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <div class="text-center mb-4">
                        <v-avatar :color="getAvatarColor(viewingUser.role)" size="80">
                            <v-img v-if="viewingUser.profile_picture" :src="getProfilePictureUrl(viewingUser.profile_picture, viewingUser.updated_at)" cover />
                            <span v-else class="text-h4 text-white">{{ getInitials(viewingUser) }}</span>
                        </v-avatar>
                        <h3 class="mt-3 text-h6">{{ viewingUser.first_name }} {{ viewingUser.last_name }}</h3>
                        <v-chip :color="getRoleColor(viewingUser.role)" size="small" class="mt-2">
                            {{ formatRole(viewingUser.role) }}
                        </v-chip>
                    </div>
                    <v-list density="compact">
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-email</v-icon>
                            </template>
                            <v-list-item-title>{{ viewingUser.email }}</v-list-item-title>
                            <v-list-item-subtitle>Email</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-card-account-details</v-icon>
                            </template>
                            <v-list-item-title>{{ viewingUser.student_id || viewingUser.faculty_id || viewingUser.staff_id || 'Not provided' }}</v-list-item-title>
                            <v-list-item-subtitle>Identifier</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-phone</v-icon>
                            </template>
                            <v-list-item-title>{{ viewingUser.phone || viewingUser.phone_number || viewingUser.contact_number || 'Not provided' }}</v-list-item-title>
                            <v-list-item-subtitle>Phone</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-calendar</v-icon>
                            </template>
                            <v-list-item-title>{{ formatDateTime(viewingUser.created_at) }}</v-list-item-title>
                            <v-list-item-subtitle>Date Created</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="profileDialog = false">Close</v-btn>
                    <v-btn color="primary" @click="profileDialog = false; openEditDialog(viewingUser)">
                        <v-icon start>mdi-pencil</v-icon>
                        Edit
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

import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

// Export users as CSV, XLSX, or PDF
const handleExport = (format = 'csv') => {
    const data = usersList.value.map(u => ([
        u.first_name,
        u.last_name,
        u.email,
        u.role,
        u.student_id || u.faculty_id || u.staff_id || u.id_number || '',
        u.phone || '',
        u.status,
        u.created_at
    ]));
    const headers = [
        'First Name',
        'Last Name',
        'Email',
        'Role',
        'ID Number',
        'Phone',
        'Status',
        'Created'
    ];
    if (format === 'pdf') {
        const doc = new jsPDF();
        autoTable(doc, {
            head: [headers],
            body: data,
            styles: { fontSize: 8 },
            headStyles: { fillColor: [33, 150, 243] },
            margin: { top: 20 }
        });
        doc.save('users_export.pdf');
    } else {
        // For CSV/XLSX
        const objData = usersList.value.map(u => ({
            'First Name': u.first_name,
            'Last Name': u.last_name,
            'Email': u.email,
            'Role': u.role,
            'ID Number': u.student_id || u.faculty_id || u.staff_id || u.id_number || '',
            'Phone': u.phone || '',
            'Status': u.status,
            'Created': u.created_at
        }));
        const worksheet = XLSX.utils.json_to_sheet(objData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Users');
        if (format === 'csv') {
            XLSX.writeFile(workbook, 'users_export.csv');
        } else {
            XLSX.writeFile(workbook, 'users_export.xlsx');
        }
    }
};
import { ref, computed } from 'vue';
import * as XLSX from 'xlsx';
import { getProfilePictureUrl } from '@/Composables/useApi';

const props = defineProps({
    users: { type: Object, default: () => ({ data: [] }) },
    stats: { type: Object, default: () => ({ total: 0, by_role: {} }) },
    auditTrail: { type: Array, default: () => [] }
});

// State
const loading = ref(false);
const dialog = ref(false);
const form = ref(null);
const formValid = ref(false);
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
const roleFilter = ref('all');
const selectedUser = ref(null);
const viewingUser = ref(null);
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarColor = ref('success');
const page = ref(1);
const itemsPerPage = ref(10);
const sortBy = ref('name');
const sortOrder = ref('asc');
const selectedUsers = ref([]);

// Bulk update
const bulkUpdateStatus = ref('active');

// Data
const usersList = ref(props.users?.data || []);
const stats = ref(props.stats);
const auditTrail = ref(props.auditTrail || []);

// Activity pagination
const activityPage = ref(1);
const activityPerPage = ref(5);


const formData = ref({
    first_name: '',
    last_name: '',
    email: '',
    role: 'student',
    phone: '',
    id_number: '',
    status: 'pending'
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
    // Phone number validation
    phoneNumber: (v) => {
        if (!v) return true; // Optional field
        const cleaned = v.replace(/[\s\-\(\)]/g, '');
        
        // Must start with 09 and have exactly 11 digits
        if (!/^09[0-9]{9}$/.test(cleaned)) {
            return 'Please enter a valid number';
        }
        
        return true;
    },
    // ID Number validation - exactly 9 digits
    idNumber: (v) => {
        if (!v || v.toString().trim() === '') return 'ID number is required';
        const digitsOnly = v.toString().replace(/\D/g, '');
        if (digitsOnly.length === 0) {
            return 'ID number is required';
        }
        if (digitsOnly.length < 9) {
            return `ID Number must be exactly 9 digits (${digitsOnly.length} entered)`;
        }
        if (digitsOnly.length > 9) {
            return 'ID Number must be exactly 9 digits';
        }
        return true;
    }
};

// Form validation computed - checks all required fields and validations
const isFormValid = computed(() => {
    // Check required fields
    if (!formData.value.first_name || !formData.value.last_name || !formData.value.email || !formData.value.role) {
        return false;
    }
    
    // Check email format
    if (!/.+@.+\..+/.test(formData.value.email)) {
        return false;
    }
    
    // Check ID number - must be exactly 9 digits
    const cleanedId = (formData.value.id_number || '').replace(/\D/g, '');
    if (cleanedId.length !== 9) {
        return false;
    }
    
    // Check phone number if provided
    if (formData.value.phone) {
        const phoneResult = rules.phoneNumber(formData.value.phone);
        if (phoneResult !== true) {
            return false;
        }
    }
    
    return formValid.value;
});

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
    { title: 'User', key: 'name', sortable: true },
    { title: 'Role', key: 'role', sortable: true },
    { title: 'ID Number', key: 'id_number', sortable: false },
    { title: 'Phone', key: 'phone', sortable: false },
    { title: 'Status', key: 'status', sortable: true },
    { title: 'Created', key: 'created_at', sortable: true },
    { title: '', key: 'actions', sortable: false, align: 'end', width: '50px' }
];

const roleOptions = [
    { label: 'All Roles', value: 'all' },
    { label: 'Patients / Visitors', value: 'student' },
    { label: 'Clinical Personnel', value: 'faculty' },
    { label: 'Hospital Staff', value: 'staff' }
];

const roleSelectOptions = [
    { label: 'Patient / Visitor', value: 'student' },
    { label: 'Clinical Personnel', value: 'faculty' },
    { label: 'Hospital Staff', value: 'staff' }
];

const statusSelectOptions = [
    { label: 'Active', value: 'active' },
    { label: 'Pending Verification', value: 'pending' },
    { label: 'Inactive', value: 'inactive' }
];

// Methods
let searchTimeout = null;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(fetchUsers, 500);
};

const fetchUsers = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (search.value) params.append('search', search.value);
        if (roleFilter.value !== 'all') params.append('role', roleFilter.value);
        
        const response = await fetch(`/admin/users?${params}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
            usersList.value = data.data.data || data.data;
            stats.value = data.stats;
            if (data.audit_trail) {
                auditTrail.value = data.audit_trail;
            }
        }
    } catch (error) {
        console.error('Error fetching users:', error);
        showSnackbar('Error fetching users', 'error');
    } finally {
        loading.value = false;
    }
};

const sortUsers = () => {
    usersList.value.sort((a, b) => {
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

const openAddDialog = () => {
    isEditing.value = false;
    formData.value = {
        first_name: '',
        last_name: '',
        email: '',
        role: 'student',
        phone: '',
        id_number: '',
        status: 'pending'
    };
    dialog.value = true;
};

// Track original data to detect changes
const originalFormData = ref({});

const openEditDialog = (user) => {
    isEditing.value = true;
    selectedUser.value = user;
    // Get ID number and clean it (strip non-digits, limit to 9)
    const rawId = user.student_id || user.faculty_id || user.staff_id || user.id_number || '';
    const cleanedId = rawId.toString().replace(/\D/g, '').slice(0, 9);
    const editFormData = { 
        ...user,
        id_number: cleanedId,
        phone: user.phone_number || user.phone || ''
    };
    formData.value = { ...editFormData };
    // Store original to compare later
    originalFormData.value = { ...editFormData };
    dialog.value = true;
};

const openBulkUpdateDialog = () => {
    bulkUpdateStatus.value = 'active';
    bulkUpdateDialog.value = true;
};

const viewProfile = (user) => {
    viewingUser.value = user;
    profileDialog.value = true;
};

// Helper functions for ID field
const getIdLabel = (role) => {
    const labels = {
        'student': 'Patient/Visitor ID (optional)',
        'faculty': 'Clinical Personnel ID',
        'staff': 'Hospital Staff ID'
    };
    return labels[role] || 'ID Number';
};

const isIdRequired = (role) => role !== 'student';

const getIdHint = (role) => {
    const hints = {
        'student': 'e.g., 2024-00001',
        'faculty': 'e.g., FAC-2024-001',
        'staff': 'e.g., STF-2024-001'
    };
    return hints[role] || 'Enter ID number';
};

// Handle ID number input - only allow digits and limit to 9
const onIdNumberInput = (event) => {
    const value = event?.target?.value || formData.value.id_number || '';
    // Strip non-digits and limit to 9 characters
    const cleaned = value.toString().replace(/\D/g, '').slice(0, 9);
    formData.value.id_number = cleaned;
    // Update the input element directly to prevent non-digit display
    if (event?.target) {
        event.target.value = cleaned;
    }
};

// Prevent non-digit keys from being entered
const onIdNumberKeypress = (event) => {
    const char = String.fromCharCode(event.keyCode || event.which);
    if (!/[0-9]/.test(char)) {
        event.preventDefault();
    }
};

// Check if form has changes from original
const hasFormChanges = computed(() => {
    if (!isEditing.value) return true; // New user always has "changes"
    
    const orig = originalFormData.value;
    const curr = formData.value;
    
    return (
        orig.first_name !== curr.first_name ||
        orig.last_name !== curr.last_name ||
        orig.email !== curr.email ||
        orig.role !== curr.role ||
        orig.id_number !== curr.id_number ||
        (orig.phone || '') !== (curr.phone || '') ||
        orig.status !== curr.status
    );
});

const saveUser = async () => {
    // Check if there are any changes when editing
    if (isEditing.value && !hasFormChanges.value) {
        showSnackbar('No changes to save', 'info');
        dialog.value = false;
        return;
    }
    
    const idRequired = isIdRequired(formData.value.role);

    // Clean and validate ID number - strip non-digits and check length when required
    const cleanedIdNumber = (formData.value.id_number || '').replace(/\D/g, '');
    
    if (idRequired) {
        if (!cleanedIdNumber) {
            showSnackbar('ID number is required', 'error');
            return;
        }
        
        if (cleanedIdNumber.length !== 9) {
            showSnackbar('ID Number must be exactly 9 digits (currently ' + cleanedIdNumber.length + ' digits)', 'error');
            return;
        }
    }
    
    // Update the form data with cleaned ID when provided
    formData.value.id_number = cleanedIdNumber;
    
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
        const url = isEditing.value ? `/admin/users/${selectedUser.value.id}` : '/admin/users';
        const method = isEditing.value ? 'PUT' : 'POST';
        
        // Prepare data with proper ID field based on role
        const submitData = {
            ...formData.value,
            student_id: formData.value.role === 'student' ? (cleanedIdNumber || null) : null,
            faculty_id: formData.value.role === 'faculty' ? cleanedIdNumber : null,
            staff_id: formData.value.role === 'staff' ? cleanedIdNumber : null,
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
            showSnackbar(isEditing.value ? 'User updated successfully' : 'User created successfully', 'success');
            dialog.value = false;
            fetchUsers();
        } else {
            showSnackbar(data.errors ? Object.values(data.errors).flat().join(', ') : 'Error saving user', 'error');
        }
    } catch (error) {
        console.error('Error saving user:', error);
        showSnackbar('Error saving user', 'error');
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (user) => {
    selectedUser.value = user;
    deleteDialog.value = true;
};

const deleteUser = async () => {
    deleting.value = true;
    try {
        const response = await fetch(`/admin/users/${selectedUser.value.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        
        const data = await response.json();
        if (data.success) {
            showSnackbar('User deleted successfully', 'success');
            deleteDialog.value = false;
            fetchUsers();
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showSnackbar('Error deleting user', 'error');
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
        for (const id of selectedUsers.value) {
            await fetch(`/admin/users/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });
        }
        showSnackbar(`${selectedUsers.value.length} user(s) deleted successfully`, 'success');
        bulkDeleteDialog.value = false;
        selectedUsers.value = [];
        fetchUsers();
    } catch (error) {
        console.error('Error bulk deleting:', error);
        showSnackbar('Error deleting users', 'error');
    } finally {
        bulkDeleting.value = false;
    }
};

const processBulkUpdate = async () => {
    bulkUpdating.value = true;
    try {
        for (const id of selectedUsers.value) {
            await fetch(`/admin/users/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({ status: bulkUpdateStatus.value })
            });
        }
        showSnackbar(`${selectedUsers.value.length} user(s) updated successfully`, 'success');
        bulkUpdateDialog.value = false;
        selectedUsers.value = [];
        fetchUsers();
    } catch (error) {
        console.error('Error bulk updating:', error);
        showSnackbar('Error updating users', 'error');
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

// Prevent invalid characters in email field
const preventInvalidEmailChars = (event) => {
    const char = String.fromCharCode(event.keyCode || event.which);
    // Allow only valid email characters: letters, digits, @, ., _, -, +
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
const getInitials = (user) => {
    return `${user.first_name?.[0] || ''}${user.last_name?.[0] || ''}`.toUpperCase();
};

const getAvatarColor = (role) => {
    const colors = { student: 'blue', faculty: 'purple', staff: 'teal' };
    return colors[role] || 'grey';
};

const getRoleColor = (role) => {
    const colors = { student: 'blue', faculty: 'purple', staff: 'teal' };
    return colors[role] || 'grey';
};

const formatRole = (role) => {
    const roleLabels = {
        student: 'Patient / Visitor',
        faculty: 'Clinical Personnel',
        staff: 'Hospital Staff',
        rescuer: 'Clinical Responder'
    };

    return roleLabels[role] || (role?.charAt(0).toUpperCase() + role?.slice(1) || '');
};

const getStatusColor = (status) => {
    const colors = {
        'active': 'success',
        'pending': 'warning',
        'inactive': 'grey'
    };
    return colors[status] || 'grey';
};

const formatStatus = (status) => {
    const labels = {
        'active': 'Active',
        'pending': 'Pending',
        'inactive': 'Inactive'
    };
    return labels[status] || status || 'Active';
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
.users-stats-banner {
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
    min-width: 70px;
    padding: 4px 8px;
}
.stat-inline-icon {
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 2px;
}
.stat-inline-icon.blue-icon { color: #90CAF9; }
.stat-inline-icon.purple-icon { color: #CE93D8; }
.stat-inline-icon.teal-icon { color: #80CBC4; }
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
</style>
