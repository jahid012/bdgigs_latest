import { initializeApp } from "firebase/app";
import {
    getMessaging,
    getToken,
    isSupported,
    onMessage,
} from "firebase/messaging";
import { apiRequest } from "../api/apiClient.js";

let messagingPromise = null;
let pushTokenPromise = null;

export async function registerFirebaseMessaging() {
    const messaging = await getMessagingInstance();
    const vapidKey = import.meta.env.VITE_FIREBASE_VAPID_KEY;

    if (!messaging || !vapidKey || !("Notification" in window)) {
        return null;
    }

    if (Notification.permission === "default") {
        await Notification.requestPermission();
    }

    if (Notification.permission !== "granted") {
        return null;
    }

    pushTokenPromise =
        pushTokenPromise ||
        getToken(messaging, {
            vapidKey,
            serviceWorkerRegistration: await getServiceWorkerRegistration(),
        });

    const token = await pushTokenPromise;

    if (!token) {
        return null;
    }

    await apiRequest("/api/push-subscriptions", {
        body: {
            token,
            platform: "web",
            metadata: {
                userAgent: navigator.userAgent,
            },
        },
    });

    return token;
}

export async function listenForForegroundPush(callback) {
    const messaging = await getMessagingInstance();

    if (!messaging) {
        return () => {};
    }

    return onMessage(messaging, callback);
}

async function getMessagingInstance() {
    if (!messagingPromise) {
        messagingPromise = createMessagingInstance();
    }

    return messagingPromise;
}

async function createMessagingInstance() {
    const config = getFirebaseConfig();

    if (!config || !(await isSupported())) {
        return null;
    }

    const app = initializeApp(config);

    return getMessaging(app);
}

function getFirebaseConfig() {
    const config = {
        apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
        authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
        projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
        messagingSenderId:
            import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
        appId: import.meta.env.VITE_FIREBASE_APP_ID,
    };

    if (
        !config.apiKey ||
        !config.projectId ||
        !config.messagingSenderId ||
        !config.appId
    ) {
        return null;
    }

    return config;
}

async function getServiceWorkerRegistration() {
    if (!("serviceWorker" in navigator)) {
        return undefined;
    }

    return navigator.serviceWorker.register("/firebase-messaging-sw.js");
}
