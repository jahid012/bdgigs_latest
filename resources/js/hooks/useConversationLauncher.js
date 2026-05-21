import { useCallback, useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { useDashboardStore } from "../stores/useDashboardStore.js";
import { useSessionStore } from "../stores/useSessionStore.js";

const pendingConversationKey = "bdgigs:pending-conversation";

export function useConversationLauncher() {
    const location = useLocation();
    const navigate = useNavigate();
    const currentUser = useSessionStore((state) => state.currentUser);
    const startConversation = useDashboardStore(
        (state) => state.startConversation,
    );

    return useCallback(
        async (payload) => {
            const normalizedPayload = normalizeConversationPayload(payload);

            if (!currentUser?.authenticated) {
                rememberPendingConversation(normalizedPayload);
                const redirect = encodeURIComponent(
                    `${location.pathname}${location.search}${location.hash}`,
                );
                navigate(`/?auth=login&redirect=${redirect}`);
                return null;
            }

            const conversation = await startConversation(normalizedPayload);
            navigate(
                `/dashboard/messages?conversation=${encodeURIComponent(conversation.id)}`,
            );

            return conversation;
        },
        [currentUser?.authenticated, location, navigate, startConversation],
    );
}

export function usePendingConversationResume() {
    const navigate = useNavigate();
    const currentUser = useSessionStore((state) => state.currentUser);
    const startConversation = useDashboardStore(
        (state) => state.startConversation,
    );

    useEffect(() => {
        if (!currentUser?.authenticated) {
            return;
        }

        const payload = takePendingConversation();

        if (!payload) {
            return;
        }

        startConversation(payload)
            .then((conversation) => {
                navigate(
                    `/dashboard/messages?conversation=${encodeURIComponent(conversation.id)}`,
                );
            })
            .catch(() => {
                rememberPendingConversation(payload);
            });
    }, [currentUser?.authenticated, navigate, startConversation]);
}

function normalizeConversationPayload(payload) {
    return {
        targetUserId: payload.targetUserId || null,
        targetName: payload.targetName || null,
        targetSlug: payload.targetSlug || null,
        contextType: payload.contextType,
        contextId: payload.contextId || null,
        message: payload.message || "",
    };
}

function rememberPendingConversation(payload) {
    window.localStorage.setItem(
        pendingConversationKey,
        JSON.stringify(normalizeConversationPayload(payload)),
    );
}

function takePendingConversation() {
    const raw = window.localStorage.getItem(pendingConversationKey);

    if (!raw) {
        return null;
    }

    window.localStorage.removeItem(pendingConversationKey);

    try {
        return normalizeConversationPayload(JSON.parse(raw));
    } catch {
        return null;
    }
}
