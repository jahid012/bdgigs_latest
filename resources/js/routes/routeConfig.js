import BuyerDashboardPage from "../pages/BuyerDashboardPage.jsx";
import BuyerMessagesPage from "../pages/BuyerMessagesPage.jsx";
import BuyerOrdersPage from "../pages/BuyerOrdersPage.jsx";
import DashboardProfilePage from "../pages/DashboardProfilePage.jsx";
import DashboardSettingsPage from "../pages/DashboardSettingsPage.jsx";
import EarningsPage from "../pages/EarningsPage.jsx";
import GigListingPage from "../pages/GigListingPage.jsx";
import HomePage from "../pages/HomePage.jsx";
import OrderDetailsPage from "../pages/OrderDetailsPage.jsx";
import PaymentsPage from "../pages/PaymentsPage.jsx";
import SavedServicesPage from "../pages/SavedServicesPage.jsx";
import SellerDashboardPage from "../pages/SellerDashboardPage.jsx";
import SellerMessagesPage from "../pages/SellerMessagesPage.jsx";
import SellerOrdersPage from "../pages/SellerOrdersPage.jsx";
import SellerServicesPage from "../pages/SellerServicesPage.jsx";

export const HOME_ROUTE = {
  key: "home",
  path: "/",
  documentTitle: "BDGigs | Freelance Services Marketplace",
  Component: HomePage,
  withNavigation: true,
};

export const DASHBOARD_ROUTES = [
  {
    key: "dashboard",
    path: "/dashboard",
    documentTitle: "Dashboard | BDGigs",
    title: "Overview",
    searchPlaceholder: "Search orders, sellers, services...",
    Component: BuyerDashboardPage,
    withNavigation: true,
  },
  {
    key: "orders",
    path: "/dashboard/orders",
    documentTitle: "Orders | BDGigs",
    title: "Orders",
    searchPlaceholder: "Search orders, services, people...",
    Component: BuyerOrdersPage,
  },
  {
    key: "order-details",
    path: "/dashboard/orders/:orderId",
    documentTitle: "Order Details | BDGigs",
    title: "Order Details",
    searchPlaceholder: "Search order activity, requirements, messages...",
    Component: OrderDetailsPage,
    pageProps: { variant: "buyer" },
  },
  {
    key: "messages",
    path: "/dashboard/messages",
    documentTitle: "Messages | BDGigs",
    title: "Messages",
    searchPlaceholder: "Search conversations, people, services...",
    messagesActive: true,
    Component: BuyerMessagesPage,
  },
  {
    key: "saved-services",
    path: "/dashboard/saved-services",
    documentTitle: "Saved Services | BDGigs",
    title: "Saved Services",
    searchPlaceholder: "Search saved services, sellers, categories...",
    Component: SavedServicesPage,
    withNavigation: true,
  },
  {
    key: "payments",
    path: "/dashboard/payments",
    documentTitle: "Payments | BDGigs",
    title: "Payments",
    searchPlaceholder: "Search payments, payouts, invoices...",
    Component: PaymentsPage,
    withNavigation: true,
  },
  {
    key: "profile",
    path: "/dashboard/profile",
    documentTitle: "Profile | BDGigs",
    title: "Profile",
    searchPlaceholder: "Search profile fields, portfolio, skills...",
    Component: DashboardProfilePage,
    pageProps: { variant: "buyer" },
    withNavigation: true,
  },
  {
    key: "settings",
    path: "/dashboard/settings",
    documentTitle: "Settings | BDGigs",
    title: "Settings",
    searchPlaceholder: "Search settings and preferences...",
    Component: DashboardSettingsPage,
    pageProps: { variant: "buyer" },
    withNavigation: true,
  },
  {
    key: "settings-page",
    path: "/dashboard/settings/:settingsPage",
    documentTitle: "Settings | BDGigs",
    title: "Settings",
    searchPlaceholder: "Search settings and preferences...",
    Component: DashboardSettingsPage,
    pageProps: { variant: "buyer" },
    withNavigation: true,
  },
  {
    key: "seller-dashboard",
    path: "/dashboard/seller",
    documentTitle: "Seller Dashboard | BDGigs",
    title: "Seller Overview",
    searchPlaceholder: "Search orders, buyers, gigs...",
    variant: "seller",
    Component: SellerDashboardPage,
    withNavigation: true,
  },
  {
    key: "seller-orders",
    path: "/dashboard/seller/orders",
    documentTitle: "Seller Orders | BDGigs",
    title: "Orders",
    searchPlaceholder: "Search orders, services, people...",
    variant: "seller",
    Component: SellerOrdersPage,
  },
  {
    key: "seller-order-details",
    path: "/dashboard/seller/orders/:orderId",
    documentTitle: "Seller Order Details | BDGigs",
    title: "Order Details",
    searchPlaceholder: "Search order activity, requirements, messages...",
    variant: "seller",
    Component: OrderDetailsPage,
    pageProps: { variant: "seller" },
  },
  {
    key: "seller-messages",
    path: "/dashboard/seller/messages",
    documentTitle: "Seller Messages | BDGigs",
    title: "Messages",
    searchPlaceholder: "Search conversations, people, services...",
    messagesActive: true,
    variant: "seller",
    Component: SellerMessagesPage,
  },
  {
    key: "seller-services",
    path: "/dashboard/seller/services",
    documentTitle: "My Services | BDGigs",
    title: "My Services",
    searchPlaceholder: "Search gigs, packages, buyers...",
    variant: "seller",
    Component: SellerServicesPage,
    withNavigation: true,
  },
  {
    key: "seller-earnings",
    path: "/dashboard/seller/earnings",
    documentTitle: "Seller Earnings | BDGigs",
    title: "Earnings",
    searchPlaceholder: "Search payments, payouts, invoices...",
    variant: "seller",
    Component: EarningsPage,
  },
  {
    key: "seller-profile",
    path: "/dashboard/seller/profile",
    documentTitle: "Seller Profile | BDGigs",
    title: "Seller Profile",
    searchPlaceholder: "Search profile fields, portfolio, skills...",
    variant: "seller",
    Component: DashboardProfilePage,
    pageProps: { variant: "seller" },
    withNavigation: true,
  },
  {
    key: "seller-settings",
    path: "/dashboard/seller/settings",
    documentTitle: "Seller Settings | BDGigs",
    title: "Settings",
    searchPlaceholder: "Search settings and preferences...",
    variant: "seller",
    Component: DashboardSettingsPage,
    pageProps: { variant: "seller" },
    withNavigation: true,
  },
  {
    key: "seller-settings-page",
    path: "/dashboard/seller/settings/:settingsPage",
    documentTitle: "Seller Settings | BDGigs",
    title: "Settings",
    searchPlaceholder: "Search settings and preferences...",
    variant: "seller",
    Component: DashboardSettingsPage,
    pageProps: { variant: "seller" },
    withNavigation: true,
  },
];

export const MARKETPLACE_ROUTES = [
  {
    key: "gig-search",
    path: "/search/gigs",
    documentTitle: "Search Gigs | BDGigs",
    Component: GigListingPage,
    withNavigation: true,
  },
  {
    key: "category-listing",
    path: "/categories/*",
    documentTitle: "Category Gigs | BDGigs",
    Component: GigListingPage,
    withNavigation: true,
  },
];

const APP_ROUTES = [HOME_ROUTE, ...MARKETPLACE_ROUTES, ...DASHBOARD_ROUTES];
const ROUTES_BY_PATH = new Map(APP_ROUTES.map((route) => [route.path, route]));

export const PAGE_PATHS = APP_ROUTES.reduce((paths, route) => {
  paths[route.key] = route.path;
  return paths;
}, {});

export function getPageKind(pathname) {
  const path = normalizePath(pathname);

  if (path.startsWith("/dashboard/seller")) {
    return "seller-dashboard";
  }

  if (path.startsWith("/dashboard")) {
    return "dashboard";
  }

  if (path.startsWith("/search/gigs") || path.startsWith("/categories")) {
    return "marketplace";
  }

  return "home";
}

export function getDocumentTitle(pathname) {
  const path = normalizePath(pathname);
  const exactRoute = ROUTES_BY_PATH.get(path);

  if (exactRoute) {
    return exactRoute.documentTitle;
  }

  if (path.startsWith("/dashboard/seller/settings")) {
    return ROUTES_BY_PATH.get("/dashboard/seller/settings").documentTitle;
  }

  if (path.startsWith("/dashboard/seller/orders/")) {
    return "Seller Order Details | BDGigs";
  }

  if (path.startsWith("/dashboard/seller/orders")) {
    return ROUTES_BY_PATH.get("/dashboard/seller/orders").documentTitle;
  }

  if (path.startsWith("/dashboard/settings")) {
    return ROUTES_BY_PATH.get("/dashboard/settings").documentTitle;
  }

  if (path.startsWith("/dashboard/orders/")) {
    return "Order Details | BDGigs";
  }

  if (path.startsWith("/dashboard/orders")) {
    return ROUTES_BY_PATH.get("/dashboard/orders").documentTitle;
  }

  if (path.startsWith("/search/gigs")) {
    return "Search Gigs | BDGigs";
  }

  if (path.startsWith("/categories")) {
    return "Category Gigs | BDGigs";
  }

  if (path.startsWith("/dashboard/seller")) {
    return ROUTES_BY_PATH.get("/dashboard/seller").documentTitle;
  }

  if (path.startsWith("/dashboard")) {
    return ROUTES_BY_PATH.get("/dashboard").documentTitle;
  }

  return ROUTES_BY_PATH.get("/").documentTitle;
}

function normalizePath(pathname) {
  const path = pathname.toLowerCase();
  return path.length > 1 ? path.replace(/\/$/, "") : path;
}
