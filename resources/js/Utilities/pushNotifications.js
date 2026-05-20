/**
 * Push Notification Utility for PinPointMe
 * Handles service worker registration, notification permissions, and push subscriptions
 * Supports both Web Push (browser) and Capacitor Push Notifications (native APK)
 */

import { Capacitor } from '@capacitor/core';
import { PushNotifications } from '@capacitor/push-notifications';
import { initializeFCMForUser, storeUserFCMToken } from '@/Utilities/firebase';

// ============================================================
// NATIVE (Capacitor) Push Notifications
// ============================================================

async function initializeNativePush() {
    console.log('[Push] Initializing native push notifications...');

    try {
        // Request permission
        const permResult = await PushNotifications.requestPermissions();
        console.log('[Push] Native permission result:', permResult.receive);

        if (permResult.receive !== 'granted') {
            console.warn('[Push] Native notification permission not granted');
            return { success: false, reason: 'permission_denied' };
        }

        // Register for push notifications
        await PushNotifications.register();
        console.log('[Push] Native push registration initiated');

        // Listen for registration success
        PushNotifications.addListener('registration', async (token) => {
            console.log('[Push] Native FCM token received:', token.value);

            // Send FCM token to your Laravel server (MySQL backup)
            try {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.content : '';

                await fetch('/api/push/subscribe-native', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        token: token.value,
                        platform: 'android',
                    }),
                });
                console.log('[Push] Native FCM token sent to Laravel server');
            } catch (error) {
                console.error('[Push] Error sending native FCM token to server:', error);
            }

            // CRITICAL: Also store FCM token in Firestore so Cloud Functions can find it
            // This is what actually enables push notifications when app is closed/killed
            try {
                const userDataStr = localStorage.getItem('userData');
                if (userDataStr) {
                    const userData = JSON.parse(userDataStr);
                    if (userData && userData.id) {
                        const stored = await storeUserFCMToken(userData.id, token.value);
                        if (stored) {
                            console.log('[Push] Native FCM token stored in Firestore for user:', userData.id);
                        } else {
                            console.warn('[Push] Failed to store native FCM token in Firestore');
                        }
                    } else {
                        console.warn('[Push] No user ID found in localStorage for Firestore token storage');
                    }
                } else {
                    console.warn('[Push] No userData in localStorage for Firestore token storage');
                }
            } catch (firestoreError) {
                console.error('[Push] Error storing native FCM token in Firestore:', firestoreError);
            }
        });

        // Listen for registration errors
        PushNotifications.addListener('registrationError', (error) => {
            console.error('[Push] Native registration error:', error);
        });

        // Listen for push notifications received while app is in foreground
        PushNotifications.addListener('pushNotificationReceived', (notification) => {
            console.log('[Push] Native notification received in foreground:', notification);
        });

        // Listen for push notification action (user tapped notification)
        PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
            console.log('[Push] Native notification tapped:', notification);
        });

        return { success: true };
    } catch (error) {
        console.error('[Push] Native push initialization error:', error);
        return { success: false, reason: 'native_init_failed', error: error.message };
    }
}

// ============================================================
// WEB Push Notifications (original code)
// ============================================================

// Register the service worker
export async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        console.warn('Service workers are not supported');
        return null;
    }

    try {
        const registration = await navigator.serviceWorker.register('/service-worker.js', {
            scope: '/'
        });
        console.log('[Push] Service worker registered:', registration.scope);
        return registration;
    } catch (error) {
        console.error('[Push] Service worker registration failed:', error);
        return null;
    }
}

// Get the current service worker registration
export async function getServiceWorkerRegistration() {
    if (!('serviceWorker' in navigator)) {
        return null;
    }
    return navigator.serviceWorker.ready;
}

// Check if push notifications are supported
export function isPushSupported() {
    return 'PushManager' in window && 'serviceWorker' in navigator && 'Notification' in window;
}

// Get current notification permission status
export function getNotificationPermission() {
    if (!('Notification' in window)) {
        return 'unsupported';
    }
    return Notification.permission;
}

// Request notification permission
export async function requestNotificationPermission() {
    if (!('Notification' in window)) {
        console.warn('Notifications are not supported');
        return 'unsupported';
    }

    if (Notification.permission === 'granted') {
        return 'granted';
    }

    if (Notification.permission === 'denied') {
        console.warn('Notification permission was previously denied');
        return 'denied';
    }

    try {
        const permission = await Notification.requestPermission();
        console.log('[Push] Notification permission:', permission);
        return permission;
    } catch (error) {
        console.error('[Push] Error requesting notification permission:', error);
        return 'denied';
    }
}

// Get the VAPID public key from the server
export async function getVapidPublicKey() {
    try {
        const response = await fetch('/api/push/vapid-public-key');
        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            console.warn('[Push] VAPID key unavailable:', data.message || response.statusText);
            return null;
        }
        const data = await response.json();
        return data.publicKey || null;
    } catch (error) {
        console.error('[Push] Error fetching VAPID public key:', error);
        return null;
    }
}

// Convert base64 string to Uint8Array (for VAPID key)
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}

// Subscribe to push notifications
export async function subscribeToPush(registration = null) {
    try {
        if (!registration) {
            registration = await getServiceWorkerRegistration();
        }

        if (!registration) {
            console.error('[Push] No service worker registration available');
            return null;
        }

        // Check if already subscribed
        let subscription = await registration.pushManager.getSubscription();
        
        if (subscription) {
            console.log('[Push] Already subscribed, updating server...');
            await sendSubscriptionToServer(subscription);
            return subscription;
        }

        // Get the VAPID public key
        const vapidPublicKey = await getVapidPublicKey();
        
        if (!vapidPublicKey) {
            console.error('[Push] VAPID public key not available');
            return null;
        }

        // Subscribe to push notifications
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
        });

        console.log('[Push] Push subscription created:', subscription);

        // Send subscription to server
        await sendSubscriptionToServer(subscription);

        return subscription;
    } catch (error) {
        console.error('[Push] Error subscribing to push notifications:', error);
        return null;
    }
}

// Send subscription to the server
export async function sendSubscriptionToServer(subscription) {
    try {
        // Convert ArrayBuffer to base64url (required for Web Push)
        const arrayBufferToBase64Url = (buffer) => {
            const bytes = new Uint8Array(buffer);
            let binary = '';
            for (let i = 0; i < bytes.byteLength; i++) {
                binary += String.fromCharCode(bytes[i]);
            }
            // Convert to base64, then convert to base64url
            return btoa(binary)
                .replace(/\+/g, '-')
                .replace(/\//g, '_')
                .replace(/=+$/, '');
        };

        const p256dhKey = subscription.getKey('p256dh');
        const authKey = subscription.getKey('auth');

        if (!p256dhKey || !authKey) {
            console.error('[Push] Missing subscription keys');
            return false;
        }

        const response = await fetch('/api/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: arrayBufferToBase64Url(p256dhKey),
                    auth: arrayBufferToBase64Url(authKey),
                },
                contentEncoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0],
            }),
            credentials: 'same-origin',
        });

        const data = await response.json();
        
        if (data.success) {
            console.log('[Push] Subscription saved to server');
        } else {
            console.error('[Push] Failed to save subscription:', data.message);
        }

        return data.success;
    } catch (error) {
        console.error('[Push] Error sending subscription to server:', error);
        return false;
    }
}

// Unsubscribe from push notifications
export async function unsubscribeFromPush() {
    try {
        const registration = await getServiceWorkerRegistration();
        
        if (!registration) {
            return true;
        }

        const subscription = await registration.pushManager.getSubscription();
        
        if (!subscription) {
            return true;
        }

        // Notify server
        await fetch('/api/push/unsubscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                endpoint: subscription.endpoint,
            }),
            credentials: 'same-origin',
        });

        // Unsubscribe locally
        await subscription.unsubscribe();
        console.log('[Push] Unsubscribed from push notifications');

        return true;
    } catch (error) {
        console.error('[Push] Error unsubscribing:', error);
        return false;
    }
}

// Initialize push notifications - call this after login
export async function initializePushNotifications() {
    console.log('[Push] Initializing push notifications...');

    // Use native Capacitor Push Notifications for APK
    if (Capacitor.isNativePlatform()) {
        return await initializeNativePush();
    }

    // Web push for browser users
    if (!isPushSupported()) {
        console.warn('[Push] Push notifications are not supported in this browser');
        return { success: false, reason: 'unsupported' };
    }

    // Register service worker
    const registration = await registerServiceWorker();
    
    if (!registration) {
        return { success: false, reason: 'service_worker_failed' };
    }

    // Request permission
    const permission = await requestNotificationPermission();
    
    if (permission !== 'granted') {
        console.warn('[Push] Notification permission not granted:', permission);
        return { success: false, reason: 'permission_denied', permission };
    }

    // Subscribe to push
    const subscription = await subscribeToPush(registration);
    
    if (!subscription) {
        return { success: false, reason: 'subscription_failed' };
    }

    console.log('[Push] Push notifications initialized successfully');
    return { success: true, subscription };
}

// Show a local test notification
export function showTestNotification(title = 'PinPointMe', body = 'Notifications are working!') {
    if (Notification.permission === 'granted') {
        new Notification(title, {
            body,
            icon: '/images/logos/pinpointme.png',
            badge: '/images/logos/pinpointme.png',
        });
    }
}
