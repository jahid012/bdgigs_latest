export const sidebarItems = [
    { label: "Overview", icon: "dashboard", path: "/dashboard", end: true },
    { label: "Orders", icon: "orders", path: "/dashboard/orders" },
    { label: "Messages", icon: "message", path: "/dashboard/messages" },
    {
        label: "Saved Services",
        icon: "heart",
        path: "/dashboard/saved-services",
    },
    { label: "Payments", icon: "payment", path: "/dashboard/payments" },
    { label: "Profile", icon: "user", path: "/dashboard/profile" },
    { label: "Settings", icon: "settings", path: "/dashboard/settings" },
];

export const sellerSidebarItems = [
    {
        label: "Overview",
        icon: "dashboard",
        path: "/dashboard/seller",
        end: true,
    },
    { label: "Orders", icon: "orders", path: "/dashboard/seller/orders" },
    { label: "Messages", icon: "message", path: "/dashboard/seller/messages" },
    {
        label: "My Services",
        icon: "packageCheck",
        path: "/dashboard/seller/services",
    },
    { label: "Earnings", icon: "payment", path: "/dashboard/seller/earnings" },
    {
        label: "Seller Profile",
        icon: "user",
        path: "/dashboard/seller/profile",
    },
    { label: "Settings", icon: "settings", path: "/dashboard/seller/settings" },
];
