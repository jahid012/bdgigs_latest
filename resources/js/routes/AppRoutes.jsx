import { Navigate, Route, Routes } from "react-router-dom";
import DashboardPage from "../pages/DashboardPage.jsx";
import { DASHBOARD_ROUTES, HOME_ROUTE } from "./routeConfig.js";
import { usePageNavigation } from "./usePageNavigation.js";
import { useRouteEffects } from "./useRouteEffects.js";

function AppRoutes() {
  const navigate = usePageNavigation();

  useRouteEffects();

  return (
    <Routes>
      <Route path={HOME_ROUTE.path} element={renderRoutePage(HOME_ROUTE, navigate)} />
      {DASHBOARD_ROUTES.map((route) => (
        <Route key={route.key} path={route.path} element={renderDashboardRoute(route, navigate)} />
      ))}
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

function renderDashboardRoute(route, navigate) {
  return (
    <DashboardPage
      messagesActive={route.messagesActive}
      onNavigate={navigate}
      searchPlaceholder={route.searchPlaceholder}
      title={route.title}
      variant={route.variant}
    >
      {renderRoutePage(route, navigate)}
    </DashboardPage>
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

export default AppRoutes;
