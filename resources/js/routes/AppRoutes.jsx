import { useEffect } from "react";
import { Navigate, Route, Routes, useLocation } from "react-router-dom";
import DashboardPage from "../pages/DashboardPage.jsx";
import { usePendingConversationResume } from "../hooks/useConversationLauncher.js";
import { usePageViewTracking } from "../hooks/usePageViewTracking.js";
import { useRealtimeMessaging } from "../realtime/useRealtimeMessaging.js";
import { useSessionStore } from "../stores/useSessionStore.js";
import {
    DASHBOARD_ROUTES,
    AUTH_ROUTES,
    HOME_ROUTE,
    MARKETPLACE_ROUTES,
    SELLER_GIG_ROUTES,
} from "./routeConfig.js";
import { usePageNavigation } from "./usePageNavigation.js";
import { useRouteEffects } from "./useRouteEffects.js";

function AppRoutes() {
    const navigate = usePageNavigation();

    useRouteEffects();
    usePageViewTracking();
    usePendingConversationResume();
    useRealtimeMessaging();

    return (
        <Routes>
            <Route
                path={HOME_ROUTE.path}
                element={renderRoutePage(HOME_ROUTE, navigate)}
            />
            {AUTH_ROUTES.map((route) => (
                <Route
                    key={route.key}
                    path={route.path}
                    element={renderRoutePage(route, navigate)}
                />
            ))}
            {MARKETPLACE_ROUTES.map((route) => (
                <Route
                    key={route.key}
                    path={route.path}
                    element={renderRoutePage(route, navigate)}
                />
            ))}
            {SELLER_GIG_ROUTES.map((route) => (
                <Route
                    key={route.key}
                    path={route.path}
                    element={renderDashboardRoute(route, navigate)}
                />
            ))}
            {DASHBOARD_ROUTES.map((route) => (
                <Route
                    key={route.key}
                    path={route.path}
                    element={renderDashboardRoute(route, navigate)}
                />
            ))}
            <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
    );
}

function renderDashboardRoute(route, navigate) {
    return (
        <RequireAuth>
            <DashboardPage
                messagesActive={route.messagesActive}
                onNavigate={navigate}
                searchPlaceholder={route.searchPlaceholder}
                title={route.title}
                variant={route.variant}
            >
                {renderRoutePage(route, navigate)}
            </DashboardPage>
        </RequireAuth>
    );
}

function renderRoutePage(route, navigate) {
    const Page = route.Component;
    const pageProps = {
        ...(route.pageProps || {}),
        ...(route.withNavigation ? { onNavigate: navigate } : {}),
    };

    return <Page {...pageProps} />;
}

function RequireAuth({ children }) {
    const location = useLocation();
    const currentUser = useSessionStore((state) => state.currentUser);
    const hasHydrated = useSessionStore((state) => state.hasHydrated);
    const hydrateSession = useSessionStore((state) => state.hydrateSession);

    useEffect(() => {
        if (!hasHydrated) {
            hydrateSession();
        }
    }, [hasHydrated, hydrateSession]);

    if (!hasHydrated) {
        return (
            <main className="dashboard-auth-loading">
                <span
                    className="dashboard-auth-spinner"
                    role="progressbar"
                    aria-label="Checking your session"
                ></span>
            </main>
        );
    }

    if (!currentUser?.authenticated) {
        const redirect = encodeURIComponent(
            `${location.pathname}${location.search}`,
        );

        return <Navigate to={`/?auth=login&redirect=${redirect}`} replace />;
    }

    return children;
}

export default AppRoutes;
