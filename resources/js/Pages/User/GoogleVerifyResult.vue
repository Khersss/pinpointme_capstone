<template>
  <v-app class="bg-user-gradient-light">
    <v-main class="min-h-screen d-flex align-center justify-center">
      <v-card class="pa-8" max-width="420" rounded="xl" elevation="2">
        <div class="text-center mb-4">
          <v-icon :color="success ? 'success' : 'error'" size="64">
            {{ success ? 'mdi-check-circle' : 'mdi-alert-circle' }}
          </v-icon>
        </div>
        <h2 class="form-title text-center mb-2" :class="success ? 'text-success' : 'text-error'">
          {{ success ? 'Account Verified!' : 'Verification Failed' }}
        </h2>
        <p class="form-subtitle text-center mb-4">{{ message }}</p>

        <!-- Success: auto-redirect countdown -->
        <template v-if="success">
          <v-alert
            type="success"
            variant="tonal"
            density="compact"
            class="mb-4 text-left"
            rounded="lg"
          >
            <div style="font-size: 0.85rem; line-height: 1.5;">
              <v-icon size="16" class="mr-1">mdi-login</v-icon>
              Redirecting you to the login page in <strong>{{ countdown }}</strong> second{{ countdown !== 1 ? 's' : '' }}...
            </div>
          </v-alert>

          <v-progress-linear
            :model-value="progressValue"
            color="success"
            rounded
            height="6"
            class="mb-3"
          />

          <v-btn
            color="primary"
            variant="flat"
            block
            size="large"
            class="submit-btn"
            @click="goToLogin"
          >
            <v-icon start>mdi-login</v-icon>
            Go to Login Now
          </v-btn>
        </template>

        <!-- Failure alert -->
        <v-alert
          v-if="!success"
          type="warning"
          variant="tonal"
          density="compact"
          class="mb-4 text-left"
          rounded="lg"
        >
          <div style="font-size: 0.85rem; line-height: 1.5;">
            <v-icon size="16" class="mr-1">mdi-arrow-left</v-icon>
            Please go back to the <strong>PinPointMe app</strong> or your <strong>browser</strong> and try signing in with Google again.
          </div>
        </v-alert>
      </v-card>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const REDIRECT_SECONDS = 5;

const page = usePage();
const success = computed(() => page.props.success);
const message = computed(() => page.props.message);

const countdown = ref(REDIRECT_SECONDS);
const progressValue = computed(() => ((REDIRECT_SECONDS - countdown.value) / REDIRECT_SECONDS) * 100);

let timer = null;

const goToLogin = () => {
  window.location.href = '/login';
};

onMounted(() => {
  if (success.value) {
    timer = setInterval(() => {
      countdown.value--;
      if (countdown.value <= 0) {
        clearInterval(timer);
        goToLogin();
      }
    }, 1000);
  }
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});
</script>

<style scoped>
.form-title {
  font-size: 1.5rem;
  font-weight: 700;
}
.form-subtitle {
  font-size: 1rem;
  color: #666;
}
.submit-btn {
  text-transform: none !important;
  font-weight: 600;
  font-size: 1rem;
  border-radius: 12px !important;
  height: 46px !important;
  box-shadow: 0 4px 12px rgba(54, 116, 181, 0.25) !important;
}
</style>
