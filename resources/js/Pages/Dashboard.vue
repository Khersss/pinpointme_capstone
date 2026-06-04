<template>
    <v-app class="bg-user-gradient-light">
        <v-app-bar color="primary">
            <v-app-bar-title>PinPointMe Admin</v-app-bar-title>
            <v-spacer />
            <v-btn icon @click="logout">
                <v-icon>mdi-logout</v-icon>
            </v-btn>
        </v-app-bar>

        <v-main>
            <v-container class="py-8">
                <h1 class="text-h4 mb-6">Dashboard</h1>

                <v-row>
                    <v-col cols="12" md="4">
                        <v-card color="primary" variant="flat" class="text-white">
                            <v-card-item>
                                <v-card-title class="text-h4">{{ stats.buildings }}</v-card-title>
                                <v-card-subtitle class="text-white">Buildings</v-card-subtitle>
                            </v-card-item>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-card color="success" variant="flat" class="text-white">
                            <v-card-item>
                                <v-card-title class="text-h4">{{ stats.rescues }}</v-card-title>
                                <v-card-subtitle class="text-white">Total Rescues</v-card-subtitle>
                            </v-card-item>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-card color="warning" variant="flat" class="text-white">
                            <v-card-item>
                                <v-card-title class="text-h4">{{ stats.pending }}</v-card-title>
                                <v-card-subtitle class="text-white">Pending Requests</v-card-subtitle>
                            </v-card-item>
                        </v-card>
                    </v-col>
                </v-row>

                <v-row class="mt-6">
                    <v-col cols="12">
                        <v-card>
                            <v-card-title>Quick Links</v-card-title>
                            <v-card-text>
                                <v-btn color="primary" class="mr-2" href="/admin/dashboard">Admin Dashboard</v-btn>
                                <v-btn color="primary" class="mr-2" href="/admin/users">Manage Users</v-btn>
                                <v-btn color="primary" class="mr-2" href="/admin/rescuers">Manage Responders</v-btn>
                                <v-btn color="primary" class="mr-2" href="/admin/buildings">Manage Buildings</v-btn>
                                <v-btn color="primary" class="mr-2" href="/admin/reports">Reports</v-btn>
                                <v-btn color="primary" href="/audit-trails">Audit Trails</v-btn>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { setUserActiveStatus } from '@/Utilities/firebase';

const stats = ref({
    buildings: 0,
    rescues: 0,
    pending: 0,
});

const logout = async () => {
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

onMounted(async () => {
    // Fetch stats if needed
});
</script>
