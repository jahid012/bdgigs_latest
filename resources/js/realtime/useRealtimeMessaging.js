import { useEffect, useState } from "react";
import { apiRequest } from "../api/apiClient.js";
import { useToast } from "../components/common/ToastProvider.jsx";
import { useDashboardStore } from "../stores/useDashboardStore.js";
import { useSessionStore } from "../stores/useSessionStore.js";
import { configureRealtime } from "./echo.js";
import {
    listenForForegroundPush,
    registerFirebaseMessaging,
} from "./firebaseMessaging.js";

const notificationSoundPath = "/assets/audio/notification.wav";

export function useRealtimeMessaging() {
    const toast = useToast();
    const currentUser = useSessionStore((state) => state.currentUser);
    const applyRealtimeMessage = useDashboardStore(
        (state) => state.applyRealtimeMessage,
    );
    const applyRealtimeConversation = useDashboardStore(
        (state) => state.applyRealtimeConversation,
    );
    const fetchConversation = useDashboardStore(
        (state) => state.fetchConversation,
    );
    const fetchNotifications = useDashboardStore(
        (state) => state.fetchNotifications,
    );
    const applyRealtimeNotification = useDashboardStore(
        (state) => state.applyRealtimeNotification,
    );
    const [notificationSettings, setNotificationSettings] = useState(null);

    useEffect(() => {
        if (!currentUser?.authenticated) {
            setNotificationSettings(null);
            return undefined;
        }

        let cancelled = false;

        apiRequest("/api/user/settings")
            .then((settings) => {
                if (!cancelled) {
                    setNotificationSettings(settings.notifications || null);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setNotificationSettings(null);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [currentUser?.authenticated]);

    useEffect(() => {
        if (!currentUser?.authenticated || !currentUser.id) {
            return undefined;
        }

        let isMounted = true;
        let unsubscribePush = () => {};
        let pushToken = null;
        const echo = configureRealtime();
        const channelName = `user.${currentUser.id}`;

        if (echo) {
            echo.private(channelName)
                .listen(".message.sent", (payload) => {
                    applyRealtimeMessage(payload);
                })
                .listen(".message.read", (payload) => {
                    if (payload.conversation) {
                        applyRealtimeConversation(payload.conversation);
                    }
                })
                .listen(".conversation.updated", (payload) => {
                    if (payload.conversation) {
                        applyRealtimeConversation(payload.conversation);
                    }
                })
                .listen(".notification.created", (payload) => {
                    const notification = payload?.notification;

                    if (notification) {
                        applyRealtimeNotification(notification);
                        notifyUser(notification, notificationSettings, toast);
                    }

                    fetchNotifications();
                });

            echo.join("presence.online")
                .here(() => {})
                .joining(() => {})
                .leaving(() => {});
        }

        const markOnline = (token) =>
            apiRequest("/api/presence/join", {
                body: token ? { token } : {},
            }).catch(() => {});

        registerFirebaseMessaging()
            .then((token) => {
                pushToken = token;
                markOnline(token);
            })
            .catch(() => {
                markOnline();
            });

        listenForForegroundPush((payload) => {
            fetchNotifications();

            const conversationId = payload?.data?.conversationId;
            const notification = notificationFromPushPayload(payload);

            if (notification) {
                notifyUser(notification, notificationSettings, toast);
            }

            if (conversationId) {
                fetchConversation(conversationId).catch(() => {});
            }
        })
            .then((unsubscribe) => {
                if (isMounted) {
                    unsubscribePush = unsubscribe;
                    return;
                }

                unsubscribe();
            })
            .catch(() => {});

        return () => {
            isMounted = false;
            unsubscribePush();

            if (echo) {
                echo.leave(channelName);
                echo.leave("presence.online");
            }
        };
    }, [
        applyRealtimeNotification,
        applyRealtimeConversation,
        applyRealtimeMessage,
        currentUser?.authenticated,
        currentUser?.id,
        fetchConversation,
        fetchNotifications,
        notificationSettings,
        toast,
    ]);
}

function notifyUser(notification, settings, toast) {
    if (!shouldShowRealtimeNotification(notification, settings)) {
        return;
    }

    toast.info(notification.detail || notification.title, {
        title: notification.title || "New notification",
        action: notification.actionUrl
            ? { label: "Open", url: notification.actionUrl }
            : undefined,
        duration: 6500,
    });

    if (settings?.soundEnabled ?? true) {
        playNotificationSound();
    }
}

function shouldShowRealtimeNotification(notification, settings) {
    if (settings && !settings.realtimeEnabled) {
        return false;
    }

    const preferenceKey =
        notification.preferenceKey || preferenceKeyFromType(notification.type);
    const row = settings?.preferences?.[preferenceKey];

    if (!row && preferenceKey !== "other") {
        return true;
    }

    return row?.push ?? true;
}

function preferenceKeyFromType(type = "") {
    const normalized = type.toLowerCase();

    if (normalized.includes("message") || normalized.includes("reply")) {
        return "inboxMessages";
    }

    if (normalized.includes("order") || normalized.includes("payment")) {
        return "orderUpdates";
    }

    if (normalized.includes("withdraw") || normalized.includes("payout")) {
        return "payouts";
    }

    if (normalized.includes("gig")) {
        return "gigUpdates";
    }

    return "other";
}

function notificationFromPushPayload(payload) {
    const title = payload?.notification?.title || payload?.data?.title;
    const detail = payload?.notification?.body || payload?.data?.body;

    if (!title && !detail) {
        return null;
    }

    return {
        title,
        detail,
        type: payload?.data?.type || "Push",
        actionUrl: payload?.data?.url,
        preferenceKey: payload?.data?.preferenceKey,
    };
}

function playNotificationSound() {
    try {
        const audio = new Audio(notificationSoundPath);
        audio.volume = 0.72;
        audio.play().catch(() => {});
    } catch {
        // Browsers can block sound before user interaction.
    }
}
