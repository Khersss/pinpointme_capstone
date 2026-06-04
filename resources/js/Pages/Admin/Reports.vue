<template>
    <v-app class="bg-grey-lighten-4">

        <!-- Admin App Bar -->
        <AdminAppBar activePage="reports" />

        <!-- Main Content -->
        <v-main>
            <v-container fluid :class="isMobile ? 'pa-3' : 'pa-6'">
                <!-- Page Header -->
                <div class="d-flex align-center mb-4">
                    <div>
                        <h1 :class="isMobile ? 'text-h5' : 'text-h4'" class="font-weight-bold gradient-text">Reports & Analytics</h1>
                        <p class="text-grey mt-1 text-body-2">Monitor rescue operations and system reports</p>
                    </div>
                </div>

                <!-- Section Navigation Cards -->
                <v-row class="mb-4" dense>
                    <v-col cols="12" sm="4">
                        <v-card
                            class="tab-card cursor-pointer"
                            :class="activeTab === 'rescue' ? 'tab-active-rescue' : 'tab-inactive'"
                            @click="activeTab = 'rescue'"
                            rounded="lg"
                            :elevation="activeTab === 'rescue' ? 3 : 0"
                        >
                            <div class="d-flex align-center pa-3">
                                <v-avatar :color="activeTab === 'rescue' ? 'rgba(255,255,255,0.2)' : '#E3F2FD'" size="38" rounded="lg" class="mr-3">
                                    <v-icon :color="activeTab === 'rescue' ? 'white' : '#1976D2'" size="20">mdi-ambulance</v-icon>
                                </v-avatar>
                                <div class="flex-grow-1">
                                    <div :class="activeTab === 'rescue' ? 'text-white' : ''" class="font-weight-bold text-body-2">Rescue Reports</div>
                                    <div :class="activeTab === 'rescue' ? 'text-blue-lighten-4' : 'text-grey'" class="text-caption">Emergency operations</div>
                                </div>
                                <v-chip :color="activeTab === 'rescue' ? 'white' : 'primary'" :variant="activeTab === 'rescue' ? 'flat' : 'tonal'" size="x-small" class="font-weight-bold">
                                    <span :class="activeTab === 'rescue' ? 'text-primary' : ''">{{ counts.total || 0 }}</span>
                                </v-chip>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="4">
                        <v-card
                            class="tab-card cursor-pointer"
                            :class="activeTab === 'self-safe' ? 'tab-active-success' : 'tab-inactive'"
                            @click="activeTab = 'self-safe'"
                            rounded="lg"
                            :elevation="activeTab === 'self-safe' ? 3 : 0"
                        >
                            <div class="d-flex align-center pa-3">
                                <v-avatar :color="activeTab === 'self-safe' ? 'rgba(255,255,255,0.2)' : '#E8F5E8'" size="38" rounded="lg" class="mr-3">
                                    <v-icon :color="activeTab === 'self-safe' ? 'white' : '#388E3C'" size="20">mdi-shield-check</v-icon>
                                </v-avatar>
                                <div class="flex-grow-1">
                                    <div :class="activeTab === 'self-safe' ? 'text-white' : ''" class="font-weight-bold text-body-2">Self-Safe Reports</div>
                                    <div :class="activeTab === 'self-safe' ? 'text-green-lighten-4' : 'text-grey'" class="text-caption">Safe confirmations</div>
                                </div>
                                <v-chip :color="activeTab === 'self-safe' ? 'white' : 'success'" :variant="activeTab === 'self-safe' ? 'flat' : 'tonal'" size="x-small" class="font-weight-bold">
                                    <span :class="activeTab === 'self-safe' ? 'text-success' : ''">{{ selfSafeReports.length || 0 }}</span>
                                </v-chip>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="4">
                        <v-card
                            class="tab-card cursor-pointer"
                            :class="activeTab === 'false-alarm' ? 'tab-active-warning' : 'tab-inactive'"
                            @click="activeTab = 'false-alarm'"
                            rounded="lg"
                            :elevation="activeTab === 'false-alarm' ? 3 : 0"
                        >
                            <div class="d-flex align-center pa-3">
                                <v-avatar :color="activeTab === 'false-alarm' ? 'rgba(255,255,255,0.2)' : '#FFF3E0'" size="38" rounded="lg" class="mr-3">
                                    <v-icon :color="activeTab === 'false-alarm' ? 'white' : '#F57C00'" size="20">mdi-alert-decagram</v-icon>
                                </v-avatar>
                                <div class="flex-grow-1">
                                    <div :class="activeTab === 'false-alarm' ? 'text-white' : ''" class="font-weight-bold text-body-2">False Alarm Reports</div>
                                    <div :class="activeTab === 'false-alarm' ? 'text-orange-lighten-4' : 'text-grey'" class="text-caption">Incorrect reports</div>
                                </div>
                                <v-chip :color="activeTab === 'false-alarm' ? 'white' : 'orange'" :variant="activeTab === 'false-alarm' ? 'flat' : 'tonal'" size="x-small" class="font-weight-bold">
                                    <span :class="activeTab === 'false-alarm' ? 'text-orange-darken-3' : ''">{{ falseAlarmReports.length || 0 }}</span>
                                </v-chip>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Rescue Reports Tab Content -->
                <div v-show="activeTab === 'rescue'">

                <!-- Stats Banner -->
                <v-card rounded="lg" class="rescue-stats-banner mb-3" elevation="0">
                    <div class="stats-banner-grid">
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon">mdi-table</v-icon>
                            <div class="stat-inline-value">{{ reportsList.length || 0 }}</div>
                            <div class="stat-inline-label">Total Reports</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon green-icon">mdi-check-circle</v-icon>
                            <div class="stat-inline-value">{{ getStatusCount('rescued') || 0 }}</div>
                            <div class="stat-inline-label">Assisted</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon amber-icon">mdi-clock-outline</v-icon>
                            <div class="stat-inline-value">{{ getStatusCount('in_progress') || 0 }}</div>
                            <div class="stat-inline-label">In Progress</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon red-icon">mdi-alert-circle</v-icon>
                            <div class="stat-inline-value">{{ getStatusCount('urgent') || 0 }}</div>
                            <div class="stat-inline-label">Urgent</div>
                        </div>
                    </div>
                </v-card>

                <!-- Filters -->
                <v-card rounded="lg" elevation="0" class="mb-3">
                    <v-card-text class="pa-3 pb-2">
                        <v-row dense align="center">
                            <v-col cols="12" sm="4" md="3">
                                <v-text-field
                                    v-model="search"
                                    label="Search reports..."
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    prepend-inner-icon="mdi-magnify"
                                    clearable
                                />
                            </v-col>
                            <v-col cols="6" sm="3" md="2">
                                <v-select
                                    v-model="timeFilter"
                                    :items="timeFilters"
                                    item-title="label"
                                    item-value="value"
                                    label="Time Period"
                                    variant="outlined"
                                    density="compact"
                                    clearable
                                    hide-details
                                    @update:model-value="fetchReports"
                                />
                            </v-col>
                            <v-col cols="6" sm="3" md="2">
                                <v-select
                                    v-model="statusFilter"
                                    :items="statusFilters"
                                    item-title="label"
                                    item-value="value"
                                    label="Status"
                                    variant="outlined"
                                    density="compact"
                                    clearable
                                    hide-details
                                    @update:model-value="fetchReports"
                                />
                            </v-col>
                            <v-col cols="auto" class="d-flex gap-2">
                                <v-btn variant="outlined" size="small" @click="resetFilters">
                                    <v-icon size="small">mdi-filter-off</v-icon>
                                </v-btn>
                                <v-btn color="primary" size="small" @click="openExportDialog" :loading="exporting">
                                    <v-icon size="small">mdi-file-pdf-box</v-icon>
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                 

                <!-- Reports Table -->
                <v-card rounded="lg">
                    <v-card-title class="d-flex align-center">
                        <v-icon start>mdi-table</v-icon>
                        Rescue Requests
                        <v-spacer />
                        <v-chip size="small" color="primary">{{ filteredData.length }} records</v-chip>
                    </v-card-title>
                    <v-card-text>
                        <v-data-table
                            :headers="headers"
                            :items="filteredData"
                            :search="search"
                            :loading="loading"
                            :items-per-page="10"
                            class="elevation-0"
                        >
                            <template v-slot:item.name="{ item }">
                                <div class="d-flex align-center py-2">
                                    <v-avatar color="primary" size="32" class="mr-2">
                                        <span class="text-white text-caption">{{ getInitials(item.name) }}</span>
                                    </v-avatar>
                                    {{ item.name }}
                                </div>
                            </template>
                            <template v-slot:item.status="{ item }">
                                <v-chip :color="getStatusColor(item.status)" size="small" variant="flat">
                                    {{ formatStatus(item.status) }}
                                </v-chip>
                            </template>
                            <template v-slot:item.urgency_level="{ item }">
                                <v-chip :color="getUrgencyColor(item.urgency_level)" size="small" variant="outlined">
                                    {{ item.urgency_level || 'Low' }}
                                </v-chip>
                            </template>
                            <template v-slot:item.completion_photo="{ item }">
                                <v-icon v-if="item.completion_photo" size="18" color="success" title="Proof photo attached">mdi-camera-check</v-icon>
                                <v-icon v-else-if="item.status === 'safe' || item.status === 'rescued'" size="18" color="grey-lighten-1" title="No proof photo">mdi-camera-off</v-icon>
                                <span v-else>—</span>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-btn icon size="small" variant="text" @click="viewDetails(item)">
                                    <v-icon size="small">mdi-eye</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-card-text>
                </v-card>

                <!-- Summary Charts (simplified for Vue) -->
                <v-row class="mt-6">
                    <v-col cols="12" md="6">
                        <v-card rounded="lg">
                            <v-card-title>
                                <v-icon start>mdi-chart-pie</v-icon>
                                Status Distribution
                            </v-card-title>
                            <v-card-text>
                                <div class="d-flex flex-column gap-3">
                                    <div v-for="status in statusDistribution" :key="status.name">
                                        <div class="d-flex justify-space-between mb-1">
                                            <span class="text-caption">{{ status.name }}</span>
                                            <span class="text-caption font-weight-medium">{{ status.count }} ({{ status.percentage }}%)</span>
                                        </div>
                                        <v-progress-linear
                                            :model-value="status.percentage"
                                            :color="status.color"
                                            height="8"
                                            rounded
                                        />
                                    </div>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-card rounded="lg">
                            <v-card-title>
                                <v-icon start>mdi-office-building</v-icon>
                                Requests by Building
                            </v-card-title>
                            <v-card-text>
                                <v-list density="compact">
                                    <v-list-item v-for="building in buildingDistribution" :key="building.name">
                                        <template v-slot:prepend>
                                            <v-avatar color="primary" size="32">
                                                <v-icon size="small">mdi-domain</v-icon>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title>{{ building.name }}</v-list-item-title>
                                        <template v-slot:append>
                                            <v-chip color="primary" size="small">{{ building.count }}</v-chip>
                                        </template>
                                    </v-list-item>
                                </v-list>
                                <v-alert v-if="buildingDistribution.length === 0" type="info" variant="tonal" density="compact">
                                    No data available
                                </v-alert>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
                </div>

                <!-- Self-Safe Reports Tab Content -->
                <div v-show="activeTab === 'self-safe'">

                <!-- Stats Banner -->
                <v-card rounded="lg" class="self-safe-stats-banner mb-3" elevation="0">
                    <div class="stats-banner-grid">
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon">mdi-clipboard-list</v-icon>
                            <div class="stat-inline-value">{{ selfSafeReports.length || 0 }}</div>
                            <div class="stat-inline-label">Total Reports</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon blue-icon">mdi-camera-check</v-icon>
                            <div class="stat-inline-value">{{ getPhotoProofCount() || 0 }}</div>
                            <div class="stat-inline-label">With Photos</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon amber-icon">mdi-calendar-today</v-icon>
                            <div class="stat-inline-value">{{ getTodayCount('self-safe') || 0 }}</div>
                            <div class="stat-inline-label">Today</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon teal-icon">mdi-calendar-week</v-icon>
                            <div class="stat-inline-value">{{ getWeekCount('self-safe') || 0 }}</div>
                            <div class="stat-inline-label">This Week</div>
                        </div>
                    </div>
                </v-card>

                <!-- Filters -->
                <v-card rounded="lg" elevation="0" class="mb-3">
                    <v-card-text class="pa-3 pb-2">
                        <v-row dense align="center">
                            <v-col cols="12" sm="6" md="4">
                                <v-text-field
                                    v-model="selfSafeSearch"
                                    label="Search by name, reason..."
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    prepend-inner-icon="mdi-magnify"
                                    clearable
                                />
                            </v-col>
                            <v-col cols="6" sm="3" md="2">
                                <v-select
                                    v-model="selfSafeTimeFilter"
                                    :items="timeFilters"
                                    item-title="label"
                                    item-value="value"
                                    label="Time Period"
                                    variant="outlined"
                                    density="compact"
                                    clearable
                                    hide-details
                                    @update:model-value="fetchSelfSafeReports"
                                />
                            </v-col>
                            <v-col cols="auto" class="d-flex gap-2">
                                <v-btn variant="outlined" size="small" @click="resetSelfSafeFilters">
                                    <v-icon size="small">mdi-filter-off</v-icon>
                                </v-btn>
                                <v-btn color="success" size="small" @click="exportSelfSafeReports" :loading="selfSafeExporting">
                                    <v-icon size="small">mdi-file-pdf-box</v-icon>
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                    <!-- Self-Safe Reports Table -->
                    <v-card rounded="lg">
                        <v-card-title class="d-flex align-center">
                            <v-icon start color="success">mdi-shield-check</v-icon>
                            Self-Marked Safe Reports
                            <v-spacer />
                            <v-chip size="small" color="success">{{ filteredSelfSafeData.length }} records</v-chip>
                        </v-card-title>
                        <v-card-text>
                            <v-data-table
                                :headers="selfSafeHeaders"
                                :items="filteredSelfSafeData"
                                :search="selfSafeSearch"
                                :loading="selfSafeLoading"
                                :items-per-page="10"
                                class="elevation-0"
                            >
                                <template v-slot:item.user="{ item }">
                                    <div class="d-flex align-center py-2">
                                        <v-avatar color="success" size="32" class="mr-2">
                                            <span class="text-white text-caption">{{ getInitials(item.user_name) }}</span>
                                        </v-avatar>
                                        <div>
                                            <div class="font-weight-medium">{{ item.user_name }}</div>
                                            <div class="text-caption text-grey">{{ item.rescue_code }}</div>
                                        </div>
                                    </div>
                                </template>
                                <template v-slot:item.reason="{ item }">
                                    <div class="text-truncate" style="max-width: 250px;">
                                        {{ item.safe_proof_reason || 'No reason provided' }}
                                    </div>
                                </template>
                                <template v-slot:item.photo="{ item }">
                                    <v-btn 
                                        v-if="item.safe_proof_photo"
                                        icon
                                        variant="text"
                                        size="small"
                                        color="primary"
                                        @click="viewSelfSafePhoto(item)"
                                    >
                                        <v-icon>mdi-image</v-icon>
                                    </v-btn>
                                    <span v-else class="text-grey text-caption">No photo</span>
                                </template>
                                <template v-slot:item.marked_at="{ item }">
                                    {{ formatDateTime(item.self_marked_safe_at) }}
                                </template>
                                <template v-slot:item.actions="{ item }">
                                    <v-btn icon size="small" variant="text" @click="viewSelfSafeDetails(item)">
                                        <v-icon size="small">mdi-eye</v-icon>
                                    </v-btn>
                                </template>
                            </v-data-table>
                        </v-card-text>
                    </v-card>
                </div>

                <!-- False Alarm Reports Tab Content -->
                <div v-show="activeTab === 'false-alarm'">

                <!-- Stats Banner -->
                <v-card rounded="lg" class="false-alarm-stats-banner mb-3" elevation="0">
                    <div class="stats-banner-grid">
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon">mdi-alert-decagram</v-icon>
                            <div class="stat-inline-value">{{ falseAlarmReports.length || 0 }}</div>
                            <div class="stat-inline-label">Total Reports</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon amber-icon">mdi-calendar-today</v-icon>
                            <div class="stat-inline-value">{{ falseAlarmTodayCount || 0 }}</div>
                            <div class="stat-inline-label">Today</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon blue-icon">mdi-calendar-week</v-icon>
                            <div class="stat-inline-value">{{ falseAlarmWeekCount || 0 }}</div>
                            <div class="stat-inline-label">This Week</div>
                        </div>
                        <div class="stat-inline">
                            <v-icon size="22" class="stat-inline-icon red-icon">mdi-calendar-month</v-icon>
                            <div class="stat-inline-value">{{ falseAlarmMonthCount || 0 }}</div>
                            <div class="stat-inline-label">This Month</div>
                        </div>
                    </div>
                </v-card>

                <!-- Filters -->
                <v-card rounded="lg" elevation="0" class="mb-3">
                    <v-card-text class="pa-3 pb-2">
                        <v-row dense align="center">
                            <v-col cols="12" sm="6" md="4">
                                <v-text-field
                                    v-model="falseAlarmSearch"
                                    label="Search reports..."
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    prepend-inner-icon="mdi-magnify"
                                    clearable
                                />
                            </v-col>
                            <v-col cols="6" sm="3" md="2">
                                <v-select
                                    v-model="falseAlarmTimeFilter"
                                    :items="falseAlarmTimeOptions"
                                    item-title="label"
                                    item-value="value"
                                    label="Time Period"
                                    variant="outlined"
                                    density="compact"
                                    clearable
                                    hide-details
                                    @update:model-value="fetchFalseAlarmReports"
                                />
                            </v-col>
                            <v-col cols="auto" class="d-flex gap-2">
                                <v-btn variant="outlined" size="small" @click="resetFalseAlarmFilters">
                                    <v-icon size="small">mdi-filter-off</v-icon>
                                </v-btn>
                                <v-btn color="warning" size="small" @click="exportFalseAlarmReports" :loading="falseAlarmExporting">
                                    <v-icon size="small">mdi-file-pdf-box</v-icon>
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                    <!-- False Alarm Reports Table -->
                    <v-card rounded="lg" elevation="0">
                        <v-card-title class="d-flex align-center">
                            <v-icon start color="error">mdi-alert-decagram</v-icon>
                            <span class="font-weight-bold">False Alarm Reports</span>
                            <v-spacer />
                            <v-chip size="small" color="error">{{ filteredFalseAlarmData.length }} records</v-chip>
                        </v-card-title>
                        <v-card-text>
                            <v-data-table
                                :headers="falseAlarmHeaders"
                                :items="filteredFalseAlarmData"
                                :search="falseAlarmSearch"
                                :loading="falseAlarmLoading"
                                :items-per-page="10"
                                class="elevation-0"
                                hover
                            >
                                <template v-slot:item.created_at="{ value }">
                                    <span class="text-caption">{{ formatDateTime(value) }}</span>
                                </template>
                                <template v-slot:item.reporter="{ item }">
                                    <div class="d-flex align-center py-2">
                                        <v-avatar color="primary" size="32" class="mr-2">
                                            <span class="text-white text-caption">{{ getInitials(getFalseAlarmReporter(item)) }}</span>
                                        </v-avatar>
                                        <span>{{ getFalseAlarmReporter(item) }}</span>
                                    </div>
                                </template>
                                <template v-slot:item.requester="{ item }">
                                    <div class="d-flex align-center">
                                        <v-icon size="16" color="warning" class="mr-1">mdi-account</v-icon>
                                        <span>{{ getFalseAlarmRequester(item) }}</span>
                                    </div>
                                </template>
                                <template v-slot:item.reason="{ item }">
                                    <div class="text-truncate" style="max-width: 250px;">
                                        {{ getFalseAlarmReason(item) }}
                                    </div>
                                </template>
                                <template v-slot:item.actions="{ item }">
                                    <v-btn icon size="small" variant="text" @click="viewFalseAlarmDetails(item)">
                                        <v-icon size="small">mdi-eye</v-icon>
                                    </v-btn>
                                </template>
                                <template v-slot:no-data>
                                    <div class="text-center py-8">
                                        <v-icon size="48" color="grey-lighten-1">mdi-check-decagram-outline</v-icon>
                                        <p class="mt-3 text-grey">No false alarm reports found</p>
                                    </div>
                                </template>
                            </v-data-table>
                        </v-card-text>
                    </v-card>
                </div>
            </v-container>
        </v-main>

        <!-- False Alarm Report Detail Dialog -->
        <v-dialog v-model="falseAlarmDetailsDialog" max-width="600">
            <v-card v-if="selectedFalseAlarm" class="rounded-xl">
                <v-card-title class="d-flex align-center pa-4" style="background: linear-gradient(135deg, #C62828 0%, #B71C1C 100%);">
                    <v-icon color="white" class="mr-2">mdi-alert-decagram</v-icon>
                    <span class="text-white font-weight-bold">False Alarm Report Details</span>
                    <v-spacer />
                    <v-btn icon variant="text" density="compact" @click="falseAlarmDetailsDialog = false">
                        <v-icon color="white">mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text class="pt-4">
                    <!-- Reporter Info -->
                    <div class="d-flex align-center mb-4">
                        <v-avatar color="primary" size="48" class="mr-3">
                            <span class="text-white">{{ getInitials(getFalseAlarmReporter(selectedFalseAlarm)) }}</span>
                        </v-avatar>
                        <div>
                            <div class="font-weight-bold text-h6 text-grey-darken-4">{{ getFalseAlarmReporter(selectedFalseAlarm) }}</div>
                            <div class="text-body-2 text-grey-darken-1">Responder who reported</div>
                        </div>
                    </div>

                    <!-- Detail List -->
                    <v-list density="compact" class="bg-transparent">
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="grey-darken-1">mdi-clock-outline</v-icon>
                            </template>
                            <v-list-item-title class="text-body-2 text-grey-darken-4">Date & Time</v-list-item-title>
                            <v-list-item-subtitle class="text-body-2 text-grey-darken-2">{{ formatDateTime(selectedFalseAlarm.created_at) }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="warning">mdi-account</v-icon>
                            </template>
                            <v-list-item-title class="text-body-2 text-grey-darken-4">Request By (User)</v-list-item-title>
                            <v-list-item-subtitle class="text-body-2 text-grey-darken-2">{{ getFalseAlarmRequester(selectedFalseAlarm) }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="teal">mdi-map-marker</v-icon>
                            </template>
                            <v-list-item-title class="text-body-2 text-grey-darken-4">Location</v-list-item-title>
                            <v-list-item-subtitle class="text-body-2 text-grey-darken-2">{{ getFalseAlarmLocation(selectedFalseAlarm) }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>

                    <!-- Reason -->
                    <div class="mt-2 mb-4">
                        <p class="text-subtitle-2 text-grey-darken-2 mb-2">
                            <v-icon size="18" class="mr-1">mdi-text-box</v-icon>
                            Reason for Reporting
                        </p>
                        <v-card variant="outlined" class="pa-3 rounded-lg" style="border-color: #FFCDD2;">
                            <p class="text-body-1 text-grey-darken-3">{{ getFalseAlarmReason(selectedFalseAlarm) }}</p>
                        </v-card>
                    </div>

                    <!-- Report Summary -->
                    <div>
                        <p class="text-subtitle-2 text-grey-darken-2 mb-2">
                            <v-icon size="18" class="mr-1">mdi-information-outline</v-icon>
                            Report Summary
                        </p>
                        <v-card variant="outlined" class="pa-3 rounded-lg" style="border-color: #E3F2FD; background-color: #F3F9FF;">
                            <p class="text-body-2 text-grey-darken-3 mb-2">
                                <v-icon size="16" color="info" class="mr-1">mdi-file-document</v-icon>
                                <strong>Request Code:</strong> {{ getFalseAlarmCode(selectedFalseAlarm) }}
                            </p>
                            <p class="text-body-2 text-grey-darken-3 mb-2">
                                <v-icon size="16" color="success" class="mr-1">mdi-check-circle</v-icon>
                                <strong>Status:</strong> Request reported as false alarm and resolved
                            </p>
                            <p class="text-body-2 text-grey-darken-3">
                                <v-icon size="16" color="warning" class="mr-1">mdi-alert-outline</v-icon>
                                <strong>Action Taken:</strong> Request has been removed from active rescue operations
                            </p>
                        </v-card>
                    </div>
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- Self-Safe Details Dialog -->
        <v-dialog v-model="selfSafeDetailsDialog" max-width="600">
            <v-card v-if="selectedSelfSafe" class="rounded-xl">
                <v-card-title class="d-flex align-center bg-success text-white">
                    <v-icon start>mdi-shield-check</v-icon>
                    Self-Safe Report Details
                    <v-spacer />
                    <v-btn icon variant="text" color="white" @click="selfSafeDetailsDialog = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text class="pt-4">
                    <!-- User Info -->
                    <div class="d-flex align-center mb-4">
                        <v-avatar color="success" size="48" class="mr-3">
                            <span class="text-white">{{ getInitials(selectedSelfSafe.user_name) }}</span>
                        </v-avatar>
                        <div>
                            <div class="font-weight-bold text-h6">{{ selectedSelfSafe.user_name }}</div>
                            <div class="text-grey">Rescue Code: {{ selectedSelfSafe.rescue_code }}</div>
                        </div>
                    </div>

                    <!-- Photo -->
                    <div v-if="selectedSelfSafe.safe_proof_photo" class="mb-4">
                        <p class="text-subtitle-2 text-grey-darken-2 mb-2">
                            <v-icon size="18" class="mr-1">mdi-camera</v-icon>
                            Photo Proof
                        </p>
                        <v-img 
                            :src="getStorageUrl(selectedSelfSafe.safe_proof_photo)" 
                            max-height="300" 
                            cover 
                            class="rounded-lg"
                            @click="openFullScreenPhoto(selectedSelfSafe.safe_proof_photo)"
                            style="cursor: pointer;"
                        />
                    </div>

                    <!-- Reason -->
                    <div class="mb-4">
                        <p class="text-subtitle-2 text-grey-darken-2 mb-2">
                            <v-icon size="18" class="mr-1">mdi-text-box</v-icon>
                            Reason for Marking Safe
                        </p>
                        <v-card variant="outlined" class="pa-3 rounded-lg">
                            <p class="text-body-1">{{ selectedSelfSafe.safe_proof_reason || 'No reason provided' }}</p>
                        </v-card>
                    </div>

                    <!-- Timestamps -->
                    <v-list density="compact">
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="success">mdi-clock-check</v-icon>
                            </template>
                            <v-list-item-title>Marked Safe At</v-list-item-title>
                            <v-list-item-subtitle>{{ formatDateTime(selectedSelfSafe.self_marked_safe_at) }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="primary">mdi-clock-start</v-icon>
                            </template>
                            <v-list-item-title>Request Created</v-list-item-title>
                            <v-list-item-subtitle>{{ formatDateTime(selectedSelfSafe.created_at) }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item v-if="selectedSelfSafe.location">
                            <template v-slot:prepend>
                                <v-icon color="info">mdi-map-marker</v-icon>
                            </template>
                            <v-list-item-title>Location</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedSelfSafe.location }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- Full Screen Photo Dialog -->
        <v-dialog v-model="fullScreenPhotoDialog" max-width="800">
            <v-card class="rounded-xl">
                <v-card-title class="d-flex align-center">
                    <v-icon start>mdi-image</v-icon>
                    Photo Proof
                    <v-spacer />
                    <v-btn icon variant="text" @click="fullScreenPhotoDialog = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text class="pa-0">
                    <v-img :src="fullScreenPhotoUrl" max-height="600" contain />
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- Details Dialog -->
        <v-dialog v-model="detailsDialog" max-width="600">
            <v-card v-if="selectedReport">
                <v-card-title class="d-flex align-center">
                    <v-icon start color="primary">mdi-file-document</v-icon>
                    Rescue Request Details
                    <v-spacer />
                    <v-chip :color="getStatusColor(selectedReport.status)" size="small">
                        {{ formatStatus(selectedReport.status) }}
                    </v-chip>
                </v-card-title>
                <v-card-text>
                    <v-list density="compact">
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-identifier</v-icon>
                            </template>
                            <v-list-item-title>Rescue Code</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedReport.rescue_code }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-account</v-icon>
                            </template>
                            <v-list-item-title>Requester</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedReport.name }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-map-marker</v-icon>
                            </template>
                            <v-list-item-title>Location</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedReport.location }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-clock</v-icon>
                            </template>
                            <v-list-item-title>Date & Time</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedReport.date }} at {{ selectedReport.time }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item v-if="selectedReport.rescuer_name">
                            <template v-slot:prepend>
                                <v-icon>mdi-lifebuoy</v-icon>
                            </template>
                            <v-list-item-title>Assigned Responder</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedReport.rescuer_name }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-hospital-box-outline</v-icon>
                            </template>
                            <v-list-item-title>Incident Type</v-list-item-title>
                            <v-list-item-subtitle>
                                <v-chip color="info" size="small">
                                    {{ selectedReport.mobility_status || 'Not specified' }}
                                </v-chip>
                            </v-list-item-subtitle>
                        </v-list-item>

                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-information-outline</v-icon>
                            </template>
                            <v-list-item-title>Please Specify</v-list-item-title>
                            <v-list-item-subtitle>
                                <v-chip color="secondary" size="small">
                                    {{ selectedReport.urgency_level || 'Not specified' }}
                                </v-chip>
                            </v-list-item-subtitle>
                        </v-list-item>

                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-alert-circle</v-icon>
                            </template>
                            <v-list-item-title>Urgency Level</v-list-item-title>
                            <v-list-item-subtitle>
                                <v-chip :color="getUrgencyColor(selectedReport.injuries)" size="small">
                                    {{ selectedReport.injuries || 'Low' }}
                                </v-chip>
                            </v-list-item-subtitle>
                        </v-list-item>

                        <!-- Cancellation Reason -->
                        <v-list-item v-if="selectedReport.cancellation_reason">
                            <template v-slot:prepend>
                                <v-icon :color="selectedReport.status === 'false_report' ? 'red-darken-3' : 'error'">{{ selectedReport.status === 'false_report' ? 'mdi-alert-octagon' : 'mdi-cancel' }}</v-icon>
                            </template>
                            <v-list-item-title>{{ selectedReport.status === 'false_report' ? 'False Report Details' : 'Cancellation Reason' }}</v-list-item-title>
                            <v-list-item-subtitle class="text-wrap">{{ selectedReport.cancellation_reason }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item v-if="selectedReport.cancelled_at">
                            <template v-slot:prepend>
                                <v-icon>mdi-clock-alert</v-icon>
                            </template>
                            <v-list-item-title>{{ selectedReport.status === 'false_report' ? 'Reported At' : 'Cancelled At' }}</v-list-item-title>
                            <v-list-item-subtitle>{{ new Date(selectedReport.cancelled_at).toLocaleString() }}</v-list-item-subtitle>
                        </v-list-item>

                        <!-- Completion Notes -->
                        <v-list-item v-if="selectedReport.completion_notes">
                            <template v-slot:prepend>
                                <v-icon>mdi-note-text</v-icon>
                            </template>
                            <v-list-item-title>Rescue Notes</v-list-item-title>
                            <v-list-item-subtitle class="text-wrap">{{ selectedReport.completion_notes }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>

                    <!-- Completion Photo -->
                    <div v-if="selectedReport.completion_photo" class="pa-4 pt-0">
                        <p class="text-caption text-medium-emphasis mb-2 d-flex align-center">
                            <v-icon size="14" class="mr-1" color="success">mdi-camera-check</v-icon>
                            Rescue Completion Proof Photo
                            <v-chip size="x-small" color="success" variant="tonal" class="ml-2">
                                <v-icon start size="10">mdi-shield-check</v-icon>
                                Verified
                            </v-chip>
                        </p>
                        <v-img
                            :src="selectedReport.completion_photo"
                            max-height="320"
                            rounded="lg"
                            cover
                            class="border"
                        />
                    </div>

                    <!-- Self-Safe Proof Photo (when user marked themselves safe) -->
                    <div v-if="selectedReport.safe_proof_photo" class="pa-4 pt-0">
                        <p class="text-caption text-medium-emphasis mb-2 d-flex align-center">
                            <v-icon size="14" class="mr-1" color="info">mdi-account-check</v-icon>
                            Self-Safe Proof Photo
                        </p>
                        <v-img
                            :src="selectedReport.safe_proof_photo"
                            max-height="280"
                            rounded="lg"
                            cover
                            class="border"
                        />
                        <p v-if="selectedReport.safe_proof_reason" class="text-caption text-medium-emphasis mt-2">
                            <v-icon size="12" class="mr-1">mdi-text</v-icon>
                            {{ selectedReport.safe_proof_reason }}
                        </p>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="detailsDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Export Options Dialog -->
        <v-dialog v-model="exportDialog" max-width="420" persistent>
            <v-card rounded="lg">
                <v-card-title class="d-flex align-center bg-error pa-4">
                    <v-icon color="white" class="mr-2">mdi-file-pdf-box</v-icon>
                    <span class="text-white font-weight-bold">Export to PDF</span>
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-5">
                    <p class="text-body-2 text-grey-darken-1 mb-4">Select the time period for the report export:</p>
                    <v-select
                        v-model="exportTimeFilter"
                        :items="timeFilters"
                        item-title="label"
                        item-value="value"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        label="Time Period"
                        prepend-inner-icon="mdi-calendar-range"
                    ></v-select>
                     
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="exportDialog = false" :disabled="exporting">
                        Cancel
                    </v-btn>
                    <v-btn color="error" variant="flat" @click="confirmExport" :loading="exporting" rounded="lg">
                        <v-icon start>mdi-download</v-icon>
                        Export
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000">
            {{ snackbarText }}
        </v-snackbar>
        

    </v-app>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import { useDisplay } from 'vuetify';
import AdminAppBar from '@/Components/AdminAppBar.vue';


const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);

const props = defineProps({
    reportData: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({ total: 0, pending: 0, in_progress: 0, completed: 0 }) }
});

const loading = ref(false);
const exporting = ref(false);
const search = ref('');
const timeFilter = ref('day');
const statusFilter = ref('all');
const detailsDialog = ref(false);
const selectedReport = ref(null);
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarColor = ref('success');
const exportDialog = ref(false);
const exportTimeFilter = ref('day');
const exportReportData = ref([]);

const reportsList = ref(props.reportData || []);
const counts = ref(props.counts);

// Tab state
const activeTab = ref('rescue');

// Self-Safe Reports State
const selfSafeLoading = ref(false);
const selfSafeExporting = ref(false);
const selfSafeSearch = ref('');
const selfSafeTimeFilter = ref('day');
const selfSafeReports = ref([]);
const selfSafeDetailsDialog = ref(false);
const selectedSelfSafe = ref(null);
const fullScreenPhotoDialog = ref(false);
const fullScreenPhotoUrl = ref('');

// False Alarm Reports State
const falseAlarmLoading = ref(false);
const falseAlarmExporting = ref(false);
const falseAlarmSearch = ref('');
const falseAlarmTimeFilter = ref('all');
const falseAlarmReports = ref([]);
const falseAlarmDetailsDialog = ref(false);
const selectedFalseAlarm = ref(null);

const falseAlarmTimeOptions = [
    { label: 'All Time', value: 'all' },
    { label: 'Today', value: 'day' },
    { label: 'This Week', value: 'week' },
    { label: 'This Month', value: 'month' },
    { label: 'This Year', value: 'year' },
];

const falseAlarmHeaders = [
    { title: 'Date', key: 'created_at', width: '180px' },
    { title: 'Reported By (Responder)', key: 'reporter', sortable: false },
    { title: 'Requester', key: 'requester', sortable: false },
    { title: 'Reason', key: 'reason', sortable: false },
    { title: 'Actions', key: 'actions', sortable: false, width: '80px' },
];

// Self-Safe Headers
const selfSafeHeaders = [
    { title: 'User', key: 'user', width: '200px' },
    { title: 'Reason', key: 'reason' },
    { title: 'Photo', key: 'photo', width: '80px', sortable: false },
    { title: 'Marked At', key: 'marked_at', width: '180px' },
    { title: 'Actions', key: 'actions', sortable: false, width: '80px' }
];

// Calculate percentage helper
const getPercentage = (value) => {
    if (!counts.value.total || counts.value.total === 0) return 0;
    return Math.round((value / counts.value.total) * 100);
};

const timeFilters = [
    { label: 'Today', value: 'day' },
    { label: 'This Week', value: 'week' },
    { label: 'This Month', value: 'month' },
    { label: 'This Year', value: 'year' }
];

const statusFilters = [
    { label: 'All Status', value: 'all' },
    { label: 'Need Help', value: 'need_help' },
    { label: 'In Progress', value: 'in_progress' },
    { label: 'Assisted', value: 'rescued' },
    { label: 'Cancelled', value: 'cancelled' },
    { label: 'False Reports', value: 'false_report' }
];

const headers = [
    { title: 'Code', key: 'rescue_code', width: '100px' },
    { title: 'Reported By', key: 'name' },
    { title: 'Location', key: 'location' },
    { title: 'Time', key: 'time', width: '100px' },
    { title: 'Date', key: 'date', width: '120px' },
    { title: 'Status', key: 'status', width: '120px' },
    { title: 'Urgency', key: 'urgency_level', width: '100px' },
    { title: 'Proof', key: 'completion_photo', sortable: false, width: '60px', align: 'center' },
    { title: 'Actions', key: 'actions', sortable: false, width: '80px' }
];

const filteredData = computed(() => {
    let data = reportsList.value;
    if (statusFilter.value !== 'all') {
        if (statusFilter.value === 'need_help') {
            data = data.filter(r => r.status === 'pending');
        } else if (statusFilter.value === 'in_progress') {
            data = data.filter(r => ['accepted', 'in_progress', 'en_route'].includes(r.status));
        } else if (statusFilter.value === 'rescued') {
            data = data.filter(r => ['rescued', 'completed', 'safe'].includes(r.status));
        } else if (statusFilter.value === 'cancelled') {
            data = data.filter(r => r.status === 'cancelled');
        } else if (statusFilter.value === 'false_report') {
            data = data.filter(r => r.status === 'false_report');
        }
    }
    return data;
});

const statusDistribution = computed(() => {
    const total = reportsList.value.length || 1;
    return [
        { name: 'Need Help', count: counts.value.pending, percentage: Math.round((counts.value.pending / total) * 100), color: 'warning' },
        { name: 'In Progress', count: counts.value.in_progress, percentage: Math.round((counts.value.in_progress / total) * 100), color: 'info' },
        { name: 'Assisted', count: counts.value.completed, percentage: Math.round((counts.value.completed / total) * 100), color: 'success' },
        { name: 'Cancelled', count: counts.value.cancelled || 0, percentage: Math.round(((counts.value.cancelled || 0) / total) * 100), color: 'error' },
        { name: 'False Reports', count: counts.value.false_reports || 0, percentage: Math.round(((counts.value.false_reports || 0) / total) * 100), color: 'red-darken-3' }
    ];
});

const buildingDistribution = computed(() => {
    const buildings = {};
    reportsList.value.forEach(r => {
        const name = r.building || 'Unknown';
        buildings[name] = (buildings[name] || 0) + 1;
    });
    return Object.entries(buildings)
        .map(([name, count]) => ({ name, count }))
        .sort((a, b) => b.count - a.count)
        .slice(0, 5);
});

// Self-Safe Reports Computed
const filteredSelfSafeData = computed(() => {
    let data = selfSafeReports.value;
    if (selfSafeSearch.value) {
        const searchLower = selfSafeSearch.value.toLowerCase();
        data = data.filter(r => 
            r.user_name?.toLowerCase().includes(searchLower) ||
            r.rescue_code?.toLowerCase().includes(searchLower) ||
            r.safe_proof_reason?.toLowerCase().includes(searchLower)
        );
    }
    return data;
});

// Stats Helper Functions
const getStatusCount = (status) => {
    if (!reportsList.value || reportsList.value.length === 0) return 0;
    
    switch (status) {
        case 'rescued':
            return reportsList.value.filter(r => ['rescued', 'completed', 'safe'].includes(r.status)).length;
        case 'in_progress':
            return reportsList.value.filter(r => ['accepted', 'in_progress', 'en_route'].includes(r.status)).length;
        case 'urgent':
            return reportsList.value.filter(r => r.urgency_level === 'high' || r.urgency_level === 'urgent').length;
        default:
            return 0;
    }
};

const getPhotoProofCount = () => {
    if (!selfSafeReports.value || selfSafeReports.value.length === 0) return 0;
    return selfSafeReports.value.filter(r => r.safe_proof_photo).length;
};

const getTodayCount = (type) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (type === 'self-safe') {
        if (!selfSafeReports.value || selfSafeReports.value.length === 0) return 0;
        return selfSafeReports.value.filter(r => {
            const reportDate = new Date(r.created_at);
            reportDate.setHours(0, 0, 0, 0);
            return reportDate.getTime() === today.getTime();
        }).length;
    }
    
    return 0;
};

const getWeekCount = (type) => {
    const today = new Date();
    const weekstart = new Date(today.setDate(today.getDate() - today.getDay()));
    weekstart.setHours(0, 0, 0, 0);
    
    if (type === 'self-safe') {
        if (!selfSafeReports.value || selfSafeReports.value.length === 0) return 0;
        return selfSafeReports.value.filter(r => {
            const reportDate = new Date(r.created_at);
            return reportDate >= weekstart;
        }).length;
    }
    
    return 0;
};

// Self-Safe Reports Functions
const fetchSelfSafeReports = async () => {
    selfSafeLoading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('time_filter', selfSafeTimeFilter.value);
        
        const response = await fetch(`/admin/reports/self-safe?${params}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
            selfSafeReports.value = data.data;
        }
    } catch (error) {
        console.error('Error fetching self-safe reports:', error);
    } finally {
        selfSafeLoading.value = false;
    }
};

const viewSelfSafeDetails = (item) => {
    selectedSelfSafe.value = item;
    selfSafeDetailsDialog.value = true;
};

const viewSelfSafePhoto = (item) => {
    if (item.safe_proof_photo) {
        fullScreenPhotoUrl.value = getStorageUrl(item.safe_proof_photo);
        fullScreenPhotoDialog.value = true;
    }
};

const openFullScreenPhoto = (photoPath) => {
    fullScreenPhotoUrl.value = getStorageUrl(photoPath);
    fullScreenPhotoDialog.value = true;
};

const getStorageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http') || path.startsWith('data:')) return path;
    return `/storage/${path}`;
};

const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString([], {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const fetchReports = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('time_filter', timeFilter.value);
        if (statusFilter.value !== 'all') params.append('status_filter', statusFilter.value);
        
        const response = await fetch(`/admin/reports?${params}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
            reportsList.value = data.data;
            counts.value = data.counts;
        }
    } catch (error) {
        console.error('Error fetching reports:', error);
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    timeFilter.value = 'day';
    statusFilter.value = 'all';
    search.value = '';
    fetchReports();
};

const viewDetails = (item) => {
    selectedReport.value = item;
    detailsDialog.value = true;
};

const getExportRecordCount = computed(() => {
    return exportReportData.value.length;
});

const openExportDialog = () => {
    exportTimeFilter.value = timeFilter.value;
    exportReportData.value = filteredData.value;
    exportDialog.value = true;
};

const confirmExport = async () => {
    // Fetch data for selected time period if different from current
    if (exportTimeFilter.value !== timeFilter.value) {
        try {
            exporting.value = true;
            const params = new URLSearchParams();
            params.append('time_filter', exportTimeFilter.value);
            if (statusFilter.value !== 'all') params.append('status_filter', statusFilter.value);
            
            const response = await fetch(`/admin/reports?${params}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (data.success) {
                exportReportData.value = data.data;
                await exportToPDF(exportReportData.value, exportTimeFilter.value);
            }
        } catch (error) {
            console.error('Error fetching export data:', error);
            showSnackbar('Export failed', 'error');
        } finally {
            exporting.value = false;
            exportDialog.value = false;
        }
    } else {
        await exportToPDF(filteredData.value, exportTimeFilter.value);
        exportDialog.value = false;
    }
};

const exportToPDF = async (data, timePeriod) => {
    exporting.value = true;
    try {
        const doc = new jsPDF('p', 'mm', 'a4');
        const pageWidth = doc.internal.pageSize.getWidth();
        
        // Header with gradient effect simulation
        doc.setFillColor(25, 118, 210);
        doc.rect(0, 0, pageWidth, 35, 'F');
        
        // Logo placeholder and title
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(22);
        doc.setFont('helvetica', 'bold');
        doc.text('PinPointMe - Rescue Reports', 14, 18);
        
        // Date and time
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        const reportDate = new Date().toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        doc.text(`Generated: ${reportDate}`, 14, 28);
        
        // Time period filter
        const periodText = timePeriod === 'day' ? 'Today' : 
                          timePeriod === 'week' ? 'This Week' : 
                          timePeriod === 'month' ? 'This Month' : 'This Year';
        doc.text(`Period: ${periodText}`, pageWidth - 60, 28);
        
        // Table headers and data
        const tableColumn = ['Code', 'Reported By', 'Location', 'Time', 'Date', 'Status', 'Urgency', 'Responder'];
        const tableRows = data.map(r => [
            r.rescue_code || '',
            r.name || '',
            r.location || '',
            r.time || '',
            r.date || '',
            formatStatus(r.status),
            r.urgency_level || 'Low',
            r.rescuer_name || 'Unassigned'
        ]);
        
        autoTable(doc, {
            head: [tableColumn],
            body: tableRows,
            startY: 40,
            theme: 'striped',
            headStyles: {
                fillColor: [25, 118, 210],
                textColor: [255, 255, 255],
                fontStyle: 'bold',
                fontSize: 9
            },
            bodyStyles: {
                fontSize: 8
            },
            alternateRowStyles: {
                fillColor: [245, 245, 245]
            },
            // Remove columnStyles for portrait, let autoTable auto-fit columns
            didDrawPage: function(data) {
                // Footer
                doc.setFontSize(8);
                doc.setTextColor(128, 128, 128);
                doc.text(
                    `Page ${doc.internal.getNumberOfPages()}`,
                    pageWidth / 2,
                    doc.internal.pageSize.getHeight() - 10,
                    { align: 'center' }
                );
                doc.text(
                    'PinPointMe Emergency Response System',
                    14,
                    doc.internal.pageSize.getHeight() - 10
                );
            }
        });
        
        // Save the PDF
        const fileName = `rescue_report_${new Date().toISOString().split('T')[0]}.pdf`;
        doc.save(fileName);
        
        showSnackbar('PDF report exported successfully!', 'success');
    } catch (error) {
        console.error('Error exporting PDF:', error);
        showSnackbar('Error exporting PDF. Please try again.', 'error');
    } finally {
        exporting.value = false;
    }
};

const generateCSV = (data) => {
    const headers = ['Rescue Code', 'Name', 'Location', 'Time', 'Date', 'Status', 'Urgency', 'Responder'];
    const rows = data.map(r => [
        r.rescue_code,
        r.name,
        r.location,
        r.time,
        r.date,
        r.status,
        r.urgency_level || 'Low',
        r.rescuer_name || ''
    ]);
    return [headers, ...rows].map(row => row.map(cell => `"${cell || ''}"`).join(',')).join('\n');
};

const showSnackbar = (text, color) => {
    snackbarText.value = text;
    snackbarColor.value = color;
    snackbar.value = true;
};

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'warning',
        accepted: 'info',
        in_progress: 'info',
        en_route: 'info',
        rescued: 'success',
        completed: 'success',
        safe: 'success',
        cancelled: 'error',
        false_report: 'red-darken-3'
    };
    return colors[status] || 'grey';
};

const formatStatus = (status) => {
    const labels = {
        pending: 'Need Help',
        accepted: 'In Progress',
        in_progress: 'In Progress',
        en_route: 'In Progress',
        rescued: 'Assisted',
        completed: 'Assisted',
        safe: 'Assisted',
        cancelled: 'Cancelled',
        false_report: 'False Report'
    };
    return labels[status] || status?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || 'Unknown';
};

const getUrgencyColor = (urgency) => {
    const colors = {
        Critical: 'error',
        High: 'orange',
        Medium: 'warning',
        Low: 'success'
    };
    return colors[urgency] || 'success';
};

// Watch for tab changes to load self-safe reports
watch(activeTab, (newTab) => {
    if (newTab === 'self-safe' && selfSafeReports.value.length === 0) {
        fetchSelfSafeReports();
    }
    if (newTab === 'false-alarm' && falseAlarmReports.value.length === 0) {
        fetchFalseAlarmReports();
    }
});

// ── False Alarm Reports ──
const falseAlarmTodayCount = computed(() => {
    const today = new Date(); today.setHours(0, 0, 0, 0);
    return falseAlarmReports.value.filter(r => new Date(r.created_at) >= today).length;
});
const falseAlarmWeekCount = computed(() => {
    const now = new Date(); const ws = new Date(now); ws.setDate(now.getDate() - now.getDay()); ws.setHours(0, 0, 0, 0);
    return falseAlarmReports.value.filter(r => new Date(r.created_at) >= ws).length;
});
const falseAlarmMonthCount = computed(() => {
    const now = new Date(); const ms = new Date(now.getFullYear(), now.getMonth(), 1);
    return falseAlarmReports.value.filter(r => new Date(r.created_at) >= ms).length;
});

const filteredFalseAlarmData = computed(() => {
    let data = falseAlarmReports.value;
    if (falseAlarmSearch.value) {
        const q = falseAlarmSearch.value.toLowerCase();
        data = data.filter(r =>
            (r.description || '').toLowerCase().includes(q) ||
            (r.initiator || '').toLowerCase().includes(q) ||
            getFalseAlarmReporter(r).toLowerCase().includes(q)
        );
    }
    return data;
});

const fetchFalseAlarmReports = async () => {
    falseAlarmLoading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('time_filter', falseAlarmTimeFilter.value);
        const response = await fetch(`/admin/false-reports?${params}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        if (data.success) {
            falseAlarmReports.value = data.data || [];
        }
    } catch (error) {
        console.error('Error fetching false alarm reports:', error);
    } finally {
        falseAlarmLoading.value = false;
    }
};

const resetFalseAlarmFilters = () => {
    falseAlarmTimeFilter.value = 'all';
    falseAlarmSearch.value = '';
    fetchFalseAlarmReports();
};

const resetSelfSafeFilters = () => {
    selfSafeTimeFilter.value = 'day';
    selfSafeSearch.value = '';
    fetchSelfSafeReports();
};

const exportSelfSafeReports = async () => {
    if (selfSafeExporting.value) return;
    
    selfSafeExporting.value = true;
    try {
        // Create PDF export for self-safe reports
        const doc = new jsPDF();
        doc.text('Self-Safe Reports', 20, 20);
        
        const tableData = filteredSelfSafeData.value.map(report => [
            report.user_name || 'N/A',
            report.rescue_code || 'N/A',
            (report.safe_proof_reason || '').substring(0, 50) + '...',
            formatDateTime(report.created_at) || 'N/A',
            report.safe_proof_photo ? 'Yes' : 'No'
        ]);
        
        autoTable(doc, {
            head: [['User', 'Rescue Code', 'Reason', 'Date', 'Photo']],
            body: tableData,
            startY: 30,
        });
        
        doc.save('self-safe-reports.pdf');
        
        snackbarText.value = 'Self-Safe reports exported successfully!';
        snackbarColor.value = 'success';
        snackbar.value = true;
    } catch (error) {
        console.error('Export error:', error);
        snackbarText.value = 'Export failed. Please try again.';
        snackbarColor.value = 'error';
        snackbar.value = true;
    } finally {
        selfSafeExporting.value = false;
    }
};

const exportFalseAlarmReports = async () => {
    if (falseAlarmExporting.value) return;
    
    falseAlarmExporting.value = true;
    try {
        // Create PDF export for false alarm reports
        const doc = new jsPDF();
        doc.text('False Alarm Reports', 20, 20);
        
        const tableData = falseAlarmReports.value.map(report => [
            formatDateTime(report.created_at) || 'N/A',
            getFalseAlarmReporter(report),
            getFalseAlarmRequester(report),
            (getFalseAlarmReason(report) || '').substring(0, 50) + '...',
            getFalseAlarmCode(report)
        ]);
        
        autoTable(doc, {
            head: [['Date', 'Reporter', 'Requester', 'Reason', 'Code']],
            body: tableData,
            startY: 30,
        });
        
        doc.save('false-alarm-reports.pdf');
        
        snackbarText.value = 'False Alarm reports exported successfully!';
        snackbarColor.value = 'success';
        snackbar.value = true;
    } catch (error) {
        console.error('Export error:', error);
        snackbarText.value = 'Export failed. Please try again.';
        snackbarColor.value = 'error';
        snackbar.value = true;
    } finally {
        falseAlarmExporting.value = false;
    }
};

const viewFalseAlarmDetails = (item) => {
    selectedFalseAlarm.value = item;
    falseAlarmDetailsDialog.value = true;
};

const getFalseAlarmReporter = (report) => {
    if (report.user) return `${report.user.first_name || ''} ${report.user.last_name || ''}`.trim() || 'Unknown Responder';
    return report.initiator || 'Unknown Responder';
};

const getFalseAlarmReason = (report) => {
    const desc = report.description || '';
    const match = desc.match(/Reason:\s*(.+)$/i);
    if (match && match[1].trim()) {
        let reason = match[1].trim();
        // Capitalize first letter and clean up common cases
        if (reason.toLowerCase() === 'false alarm') {
            return 'This was reported as a false alarm';
        }
        // Capitalize first letter if it's all lowercase
        if (reason === reason.toLowerCase()) {
            reason = reason.charAt(0).toUpperCase() + reason.slice(1);
        }
        return reason;
    }
    return report.details || 'No specific reason provided';
};

const getFalseAlarmRequester = (report) => {
    const desc = report.description || '';
    const match = desc.match(/by\s+(.+?)\s+at\s+/i);
    return match ? match[1].trim() : 'Unknown User';
};

const getFalseAlarmLocation = (report) => {
    const desc = report.description || '';
    const match = desc.match(/at\s+(.+?)\.\s*Reason:/i);
    if (match && match[1].trim()) {
        const location = match[1].trim();
        // Handle malformed location data like "> >" or empty strings
        if (location === '> >' || location === '>' || location.length < 2) {
            return 'Location not available';
        }
        return location;
    }
    return 'Location not available';
};

const getFalseAlarmCode = (report) => {
    const desc = report.description || '';
    const match = desc.match(/Code:\s*([A-Z0-9]+)/i);
    if (match && match[1]) {
        return match[1].trim();
    }
    // Try alternative pattern
    const altMatch = desc.match(/#\d+\s*\(Code:\s*([A-Z0-9]+)\)/i);
    return altMatch ? altMatch[1].trim() : 'N/A';
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

/* Gradient App Bar */
.gradient-app-bar {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 50%, #0D47A1 100%) !important;
}

/* Stat Cards with Gradient Backgrounds */
.stat-card {
    position: relative;
    overflow: hidden;
}

.stat-card-overlay {
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    pointer-events: none;
}

.stat-card-primary {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 50%, #0D47A1 100%) !important;
}

.stat-card-warning {
    background: linear-gradient(135deg, #FB8C00 0%, #F57C00 50%, #EF6C00 100%) !important;
}

.stat-card-info {
    background: linear-gradient(135deg, #00ACC1 0%, #0097A7 50%, #00838F 100%) !important;
}

.stat-card-success {
    background: linear-gradient(135deg, #43A047 0%, #388E3C 50%, #2E7D32 100%) !important;
}

.opacity-80 {
    opacity: 0.8;
}

.gap-2 {
    gap: 8px;
}

/* ===== Tab Cards ===== */
.tab-card {
    transition: all 0.25s ease;
    border: 2px solid transparent;
}
.tab-card:hover {
    transform: translateY(-1px);
}
.tab-active-rescue {
    background: linear-gradient(135deg, #1976D2 0%, #0D47A1 100%) !important;
    border-color: transparent;
}
.tab-active-self-safe {
    background: linear-gradient(135deg, #388E3C 0%, #2E7D32 100%) !important;
    border-color: transparent;
}
.tab-active-false-alarm {
    background: linear-gradient(135deg, #EF6C00 0%, #E65100 100%) !important;
    border-color: transparent;
}
.tab-inactive {
    background: white !important;
    border-color: #e8ecf0;
}
.tab-inactive:hover {
    border-color: #90CAF9;
    box-shadow: 0 2px 8px rgba(25, 118, 210, 0.1);
}
.cursor-pointer {
    cursor: pointer;
}

/* ===== Stats Banners ===== */
.rescue-stats-banner {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 50%, #0D47A1 100%) !important;
}
.self-safe-stats-banner {
    background: linear-gradient(135deg, #388E3C 0%, #43A047 50%, #4CAF50 100%) !important;
}
.false-alarm-stats-banner {
    background: linear-gradient(135deg, #EF6C00 0%, #FF9800 50%, #FFA726 100%) !important;
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
.stat-inline-icon.amber-icon { color: #FFD54F; }
.stat-inline-icon.green-icon { color: #A5D6A7; }
.stat-inline-icon.teal-icon { color: #80CBC4; }
.stat-inline-icon.red-icon { color: #EF9A9A; }
.stat-inline-icon.orange-icon { color: #FFCC80; }
.stat-inline-icon.blue-icon { color: #90CAF9; }
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

/* Page Header Responsive Styles */
.page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.page-header-content {
    flex: 1;
    min-width: 200px;
}

/* Mobile Specific Styles */
@media (max-width: 600px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .page-header-content {
        width: 100%;
    }
}
</style>
