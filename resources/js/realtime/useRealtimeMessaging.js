import { useEffect } from "react";
import { apiRequest } from "../api/apiClient.js";
import { useDashboardStore } from "../stores/useDashboardStore.js";
import { useSessionStore } from "../stores/useSessionStore.js";
import { configureRealtime } from "./echo.js";
import {
    listenForForegroundPush,
    registerFirebaseMessaging,
} from "./firebaseMessaging.js";

const heartbeatIntervalMs = 45_000;

export function useRealtimeMessaging() {
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
                .listen(".notification.created", () => {
                    fetchNotifications();
                });
        }

        const heartbeat = () =>
            apiRequest("/api/presence/heartbeat", {
                body: pushToken ? { token: pushToken } : {},
            }).catch(() => {});

        registerFirebaseMessaging()
            .then((token) => {
                pushToken = token;
                heartbeat();
            })
            .catch(() => {
                heartbeat();
            });

        listenForForegroundPush((payload) => {
            fetchNotifications();

            const conversationId = payload?.data?.conversationId;

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

        const heartbeatTimer = window.setInterval(
            heartbeat,
            heartbeatIntervalMs,
        );

        return () => {
            isMounted = false;
            window.clearInterval(heartbeatTimer);
            unsubscribePush();

            if (echo) {
                echo.leave(channelName);
            }
        };
    }, [
        applyRealtimeConversation,
        applyRealtimeMessage,
        currentUser?.authenticated,
        currentUser?.id,
        fetchConversation,
        fetchNotifications,
    ]);
}
