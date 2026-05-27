import { useEffect } from "react";
import { useLocation } from "react-router-dom";
import { apiRequest } from "../api/apiClient.js";

const VISITOR_ID_KEY = "bdgigs:visitor-id";
let lastTracked = { key: "", at: 0 };

export function usePageViewTracking() {
    const { pathname, search } = useLocation();

    useEffect(() => {
        const path = `${pathname}${search}`;

        if (!path || path.startsWith("/admin")) {
            return undefined;
        }

        const visitorId = getVisitorId();
        const trackingKey = `${visitorId}:${path}`;
        const now = Date.now();

        if (lastTracked.key === trackingKey && now - lastTracked.at < 2000) {
            return undefined;
        }

        lastTracked = { key: trackingKey, at: now };

        const frameId = window.requestAnimationFrame(() => {
            apiRequest("/api/analytics/page-view", {
                method: "POST",
                body: {
                    path,
                    title: document.title,
                    referrer: document.referrer,
                    visitorId,
                },
            }).catch(() => {});
        });

        return () => window.cancelAnimationFrame(frameId);
    }, [pathname, search]);
}

function getVisitorId() {
    try {
        const stored = window.localStorage.getItem(VISITOR_ID_KEY);

        if (stored) {
            return stored;
        }

        const generated = createVisitorId();
        window.localStorage.setItem(VISITOR_ID_KEY, generated);

        return generated;
    } catch {
        return createVisitorId();
    }
}

function createVisitorId() {
    return (
        window.crypto?.randomUUID?.() ||
        `${Date.now()}-${Math.random().toString(36).slice(2)}`
    );
}
