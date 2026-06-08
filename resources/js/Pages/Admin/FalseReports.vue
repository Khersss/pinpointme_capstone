<template>
    <v-app class="bg-grey-lighten-4">
        <AdminAppBar activePage="false-reports" />

        <v-main>
            <v-container fluid :class="isMobile ? 'pa-3' : 'pa-6'">
                <!-- Page Header -->
                <div class="page-header mb-4 mb-md-6">
                    <div class="page-header-content">
                        <h1 :class="isMobile ? 'text-h5' : 'text-h4'" class="font-weight-bold gradient-text">False Alarm Reports</h1>
                        <p class="text-grey mt-1 text-body-2">View reports submitted by responders for false assistance requests</p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <v-row class="mb-4 mb-md-6">
                    <v-col cols="6" sm="3">
                        <v-card rounded="lg" class="stat-card text-center pa-4" elevation="0">
                            <div class="stat-icon-wrap mb-2">
                                <v-icon size="28" color="error">mdi-alert-decagram</v-icon>
                            </div>
                            <div class="text-h5 font-weight-bold" style="color: #C62828;">{{ totalReports }}</div>
                            <div class="text-caption text-grey">Total Reports</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <v-card rounded="lg" class="stat-card text-center pa-4" elevation="0">
                            <div class="stat-icon-wrap mb-2">
                                <v-icon size="28" color="warning">mdi-calendar-today</v-icon>
                            </div>
                            <div class="text-h5 font-weight-bold" style="color: #EF6C00;">{{ todayReportCount }}</div>
                            <div class="text-caption text-grey">Today</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <v-card rounded="lg" class="stat-card text-center pa-4" elevation="0">
                            <div class="stat-icon-wrap mb-2">
                                <v-icon size="28" color="info">mdi-calendar-week</v-icon>
                            </div>
                            <div class="text-h5 font-weight-bold" style="color: #1565C0;">{{ weekReportCount }}</div>
                            <div class="text-caption text-grey">This Week</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <v-card rounded="lg" class="stat-card text-center pa-4" elevation="0">
                            <div class="stat-icon-wrap mb-2">
                                <v-icon size="28" color="purple">mdi-calendar-month</v-icon>
                            </div>
                            <div class="text-h5 font-weight-bold" style="color: #7B1FA2;">{{ monthReportCount }}</div>
                            <div class="text-caption text-grey">This Month</div>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Filters -->
                <v-card rounded="lg" class="mb-6" elevation="0">
                    <v-card-text>
                        <v-row align="center" dense>
                            <v-col cols="12" sm="4" md="3">
                                <v-select
                                    v-model="timeFilter"
                                    :items="timeFilterOptions"
                                    item-title="label"
                                    item-value="value"
                                    label="Time Period"
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                />
                            </v-col>
                            <v-col cols="12" sm="5" md="4">
                                <v-text-field
                                    v-model="searchQuery"
                                    label="Search reports..."
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    prepend-inner-icon="mdi-magnify"
                                    clearable
                                />
                            </v-col>
                            <v-col cols="12" sm="3" md="2">
                                <v-btn block color="grey-lighten-1" variant="outlined" density="compact" @click="resetFilters" class="mt-1">
                                    <v-icon start>mdi-refresh</v-icon>
                                    Reset
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <!-- Reports List -->
                <v-card rounded="lg" elevation="0">
                    <v-card-title class="d-flex align-center">
                        <v-icon start color="error">mdi-delete-alert</v-icon>
                        <span class="text-subtitle-1 font-weight-bold">False Alarm Reports ({{ filteredReports.length }})</span>
                    </v-card-title>

                    <!-- Loading -->
                    <v-card-text v-if="loading" class="text-center py-8">
                        <v-progress-circular indeterminate color="primary" size="40" />
                        <p class="mt-3 text-grey">Loading reports...</p>
                    </v-card-text>

                    <!-- Empty state -->
                    <v-card-text v-else-if="filteredReports.length === 0" class="text-center py-8">
                        <v-icon size="48" color="grey-lighten-1">mdi-check-decagram-outline</v-icon>
                        <p class="mt-3 text-grey">No false alarm reports found</p>
                        <p class="text-caption text-grey-lighten-1">False alarm reports from responders will appear here</p>
                    </v-card-text>

                    <!-- Reports Table (Desktop) -->
                    <v-data-table
                        v-if="!loading && !isMobile && filteredReports.length > 0"
                        :headers="tableHeaders"
                        :items="filteredReports"
                        :items-per-page="10"
                        class="elevation-0"
                        @click:row="(event, { item }) => viewReport(item)"
                        hover
                    >
                        <template v-slot:item.created_at="{ value }">
                            <span class="text-caption">{{ formatDate(value) }}</span>
                        </template>
                        <template v-slot:item.reporter="{ item }">
                            <div class="d-flex align-center">
                                <v-icon size="16" color="primary" class="mr-2">mdi-lifebuoy</v-icon>
                                <span>{{ getReporterName(item) }}</span>
                            </div>
                        </template>
                        <template v-slot:item.reason="{ item }">
                            <span class="text-body-2">{{ extractReason(item) }}</span>
                        </template>
                        <template v-slot:item.requester="{ item }">
                            <span>{{ extractRequesterFromDesc(item) }}</span>
                        </template>
                        <template v-slot:item.actions="{ item }">
                            <v-btn icon variant="text" size="small" @click.stop="viewReport(item)">
                                <v-icon>mdi-eye</v-icon>
                            </v-btn>
                        </template>
                    </v-data-table>

                    <!-- Reports Cards (Mobile) -->
                    <v-card-text v-if="!loading && isMobile && filteredReports.length > 0" class="pa-0">
                        <div
                            v-for="report in paginatedReports"
                            :key="report.id"
                            class="report-card"
                            @click="viewReport(report)"
                        >
                            <div class="report-card-header">
                                <div class="d-flex align-center">
                                    <v-avatar color="error" size="32" class="mr-2">
                                        <v-icon size="16" color="white">mdi-alert-decagram</v-icon>
                                    </v-avatar>
                                    <div>
                                        <div class="font-weight-medium text-body-2">{{ extractRequesterFromDesc(report) }}</div>
                                        <div class="text-caption text-grey">{{ formatDate(report.created_at) }}</div>
                                    </div>
                                </div>
                                <v-chip size="x-small" color="error" variant="tonal">
                                    <v-icon start size="10">mdi-delete-alert</v-icon>
                                    False Alarm
                                </v-chip>
                            </div>

                            <div class="report-card-reason mt-2">
                                <v-icon size="12" color="error" class="mr-1">mdi-format-quote-open</v-icon>
                                {{ extractReason(report) }}
                            </div>

                            <div class="report-card-footer mt-2">
                                <v-chip size="x-small" color="primary" variant="tonal">
                                    <v-icon start size="10">mdi-lifebuoy</v-icon>
                                    Reported by: {{ getReporterName(report) }}
                                </v-chip>
                            </div>
                        </div>

                        <!-- Mobile pagination -->
                        <div v-if="mobilePages > 1" class="d-flex justify-center py-3">
                            <v-pagination
                                v-model="currentPage"
                                :length="mobilePages"
                                :total-visible="3"
                                density="compact"
                                rounded="circle"
                                color="primary"
                            />
                        </div>
                    </v-card-text>
                </v-card>

                <!-- Report Detail Dialog -->
                <v-dialog v-model="showDetailDialog" :width="isMobile ? '95%' : 550" rounded="lg">
                    <v-card v-if="selectedReport" rounded="lg">
                        <v-card-title class="d-flex align-center pa-4" style="background: linear-gradient(135deg, #C62828 0%, #B71C1C 100%); color: white;">
                            <v-icon color="white" class="mr-2">mdi-alert-decagram</v-icon>
                            False Alarm Report Detail
                            <v-spacer />
                            <v-btn icon variant="text" density="compact" @click="showDetailDialog = false">
                                <v-icon color="white">mdi-close</v-icon>
                            </v-btn>
                        </v-card-title>
                        <v-card-text class="pa-4">
                            <!-- Date -->
                            <div class="mb-3">
                                <p class="text-caption text-grey mb-1">Date & Time</p>
                                <div class="d-flex align-center">
                                    <v-icon size="16" color="grey" class="mr-2">mdi-clock-outline</v-icon>
                                    <span class="font-weight-medium">{{ formatDate(selectedReport.created_at) }}</span>
                                </div>
                            </div>

                            <v-divider class="mb-3" />

                            <!-- Reporter -->
                            <div class="mb-3">
                                <p class="text-caption text-grey mb-1">Reported By (Responder)</p>
                                <div class="d-flex align-center">
                                    <v-icon size="16" color="primary" class="mr-2">mdi-lifebuoy</v-icon>
                                    <span class="font-weight-medium">{{ getReporterName(selectedReport) }}</span>
                                </div>
                            </div>

                            <!-- Requester -->
                            <div class="mb-3">
                                <p class="text-caption text-grey mb-1">Request By (User)</p>
                                <div class="d-flex align-center">
                                    <v-icon size="16" color="warning" class="mr-2">mdi-account</v-icon>
                                    <span class="font-weight-medium">{{ extractRequesterFromDesc(selectedReport) }}</span>
                                </div>
                            </div>

                            <!-- Location -->
                            <div class="mb-3">
                                <p class="text-caption text-grey mb-1">Location</p>
                                <div class="d-flex align-center">
                                    <v-icon size="16" color="teal" class="mr-2">mdi-map-marker</v-icon>
                                    <span class="font-weight-medium">{{ extractLocation(selectedReport) }}</span>
                                </div>
                            </div>

                            <v-divider class="mb-3" />

                            <!-- Reason -->
                            <div class="mb-3">
                                <p class="text-caption text-grey mb-1">Reason for Reporting</p>
                                <v-card variant="tonal" color="red-lighten-5" rounded="lg" class="pa-3">
                                    <v-icon size="14" color="error" class="mr-1">mdi-format-quote-open</v-icon>
                                    {{ extractReason(selectedReport) }}
                                </v-card>
                            </div>

                            <!-- Full description -->
                            <div>
                                <p class="text-caption text-grey mb-1">Full Audit Description</p>
                                <v-card variant="tonal" color="grey-lighten-4" rounded="lg" class="pa-3">
                                    <span class="text-body-2">{{ selectedReport.description }}</span>
                                </v-card>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-dialog>
            </v-container>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useDisplay } from 'vuetify';
import AdminAppBar from '@/Components/AdminAppBar.vue';

const page = usePage();
const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);

// State
const loading = ref(false);
const allReports = ref(page.props.falseReports || []);
const selectedReport = ref(null);
const showDetailDialog = ref(false);

// Filters
const timeFilter = ref('all');
const searchQuery = ref('');
const currentPage = ref(1);
const pageSize = 10;

const timeFilterOptions = [
    { label: 'All Time', value: 'all' },
    { label: 'Today', value: 'day' },
    { label: 'This Week', value: 'week' },
    { label: 'This Month', value: 'month' },
    { label: 'This Year', value: 'year' },
];

const tableHeaders = [
    { title: 'Date', key: 'created_at', width: '160px' },
    { title: 'Reported By', key: 'reporter', sortable: false },
    { title: 'Requester', key: 'requester', sortable: false },
    { title: 'Reason', key: 'reason', sortable: false },
    { title: '', key: 'actions', sortable: false, width: '60px' },
];

// Stats
const totalReports = computed(() => allReports.value.length);

const todayReportCount = computed(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return allReports.value.filter(r => new Date(r.created_at) >= today).length;
});

const weekReportCount = computed(() => {
    const now = new Date();
    const weekStart = new Date(now);
    weekStart.setDate(now.getDate() - now.getDay());
    weekStart.setHours(0, 0, 0, 0);
    return allReports.value.filter(r => new Date(r.created_at) >= weekStart).length;
});

const monthReportCount = computed(() => {
    const now = new Date();
    const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
    return allReports.value.filter(r => new Date(r.created_at) >= monthStart).length;
});

// Filtered
const filteredReports = computed(() => {
    let result = [...allReports.value];

    // Time filter
    if (timeFilter.value !== 'all') {
        const now = new Date();
        let startDate;
        switch (timeFilter.value) {
            case 'day':
                startDate = new Date(now); startDate.setHours(0, 0, 0, 0);
                break;
            case 'week':
                startDate = new Date(now); startDate.setDate(now.getDate() - now.getDay()); startDate.setHours(0, 0, 0, 0);
                break;
            case 'month':
                startDate = new Date(now.getFullYear(), now.getMonth(), 1);
                break;
            case 'year':
                startDate = new Date(now.getFullYear(), 0, 1);
                break;
        }
        if (startDate) result = result.filter(r => new Date(r.created_at) >= startDate);
    }

    // Search
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(r =>
            (r.description || '').toLowerCase().includes(q) ||
            (r.initiator || '').toLowerCase().includes(q) ||
            getReporterName(r).toLowerCase().includes(q)
        );
    }

    return result;
});

const mobilePages = computed(() => Math.ceil(filteredReports.value.length / pageSize));

const paginatedReports = computed(() => {
    const start = (currentPage.value - 1) * pageSize;
    return filteredReports.value.slice(start, start + pageSize);
});

// Methods
const resetFilters = () => {
    timeFilter.value = 'all';
    searchQuery.value = '';
    currentPage.value = 1;
};

const viewReport = (report) => {
    selectedReport.value = report;
    showDetailDialog.value = true;
};

// Helpers
const getReporterName = (report) => {
    if (report.user) {
        return `${report.user.first_name || ''} ${report.user.last_name || ''}`.trim() || 'Unknown Responder';
    }
    return report.initiator || 'Unknown Responder';
};

const extractReason = (report) => {
    const desc = report.description || '';
    const match = desc.match(/Reason:\s*(.+)$/i);
    return match ? match[1].trim() : report.details || desc;
};

const extractRequesterFromDesc = (report) => {
    const desc = report.description || '';
    // Pattern: "by FirstName LastName at Location"
    const match = desc.match(/by\s+(.+?)\s+at\s+/i);
    return match ? match[1].trim() : 'Unknown User';
};

const extractLocation = (report) => {
    const desc = report.description || '';
    // Pattern: "at Location. Reason:"
    const match = desc.match(/at\s+(.+?)\.\s*Reason:/i);
    return match ? match[1].trim() : 'Unknown Location';
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// Fetch reports on time filter change (fetch fresh from server)
const fetchReports = async () => {
    loading.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const resp = await fetch(`/admin/false-reports?time_filter=${timeFilter.value}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            credentials: 'include',
        });
        const result = await resp.json();
        if (result.success) {
            allReports.value = result.data || [];
        }
    } catch (e) {
        console.error('Error fetching false reports:', e);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    // Initial data comes from Inertia props
    if (!allReports.value.length) {
        fetchReports();
    }
});
</script>

<style scoped>
/* Gradient Text */
.gradient-text {
    background: linear-gradient(135deg, #1976D2, #0D47A1);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.stat-card {
    border: 1px solid #e8ecf0;
    transition: box-shadow 0.2s ease;
}
.stat-card:hover {
    box-shadow: 0 4px 16px rgba(25, 118, 210, 0.1) !important;
}

.stat-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #f4f6f9;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Report cards for mobile */
.report-card {
    padding: 16px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.15s ease;
}
.report-card:hover {
    background: #fff8f8;
}
.report-card:last-child {
    border-bottom: none;
}
.report-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.report-card-reason {
    font-size: 13px;
    color: #546E7A;
    line-height: 1.5;
    padding: 8px 12px;
    background: #fff5f5;
    border-radius: 8px;
    border-left: 3px solid #EF5350;
}
.report-card-footer {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}
</style>
