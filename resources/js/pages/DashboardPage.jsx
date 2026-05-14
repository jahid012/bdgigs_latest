import { useState } from "react";
import {
    buyerNotifications,
    messages,
    sellerMessages,
    sellerNotifications,
    sellerSidebarItems,
} from "../data/dashboardData.js";
import Sidebar from "../components/dashboard/Sidebar.jsx";
import Topbar from "../components/dashboard/Topbar.jsx";
import { useTranslation } from "react-i18next";
function DashboardPage({
    children,
    messagesActive = false,
    onNavigate,
    searchPlaceholder,
    title,
    variant = "buyer",
}) {
    const { t } = useTranslation();
    const [isSidebarOpen, setIsSidebarOpen] = useState(false);
    const isSeller = variant === "seller";
    const dashboardTitle = title || (isSeller ? "Seller Overview" : "Overview");
    const dashboardSearchPlaceholder =
        searchPlaceholder ||
        (isSeller
            ? "Search orders, buyers, gigs..."
            : "Search orders, sellers, services...");
    return (
        <>
            <a className="skip-link" href="#dashboardMain">
                {" "}
                {t("pages.dashboardpage.skipToDashboardContent")}{" "}
            </a>

            <div
                className={`dashboard-layout ${isSeller ? "seller-dashboard" : "buyer-dashboard"}`}
            >
                <Sidebar
                    isOpen={isSidebarOpen}
                    onClose={() => setIsSidebarOpen(false)}
                    onNavigate={onNavigate}
                    items={isSeller ? sellerSidebarItems : undefined}
                    label={isSeller ? "Seller tools" : "Workspace"}
                    upgradeEyebrow={isSeller ? "Growth insight" : undefined}
                    upgradeTitle={isSeller ? "Seller Plus" : undefined}
                    upgradeCopy={
                        isSeller
                            ? "Unlock advanced gig analytics, promoted visibility, and buyer intent signals."
                            : undefined
                    }
                    upgradeAction={isSeller ? "Grow sales" : undefined}
                />

                <div className="dashboard-main" id="dashboardMain">
                    <Topbar
                        isSidebarOpen={isSidebarOpen}
                        onSidebarOpen={() => setIsSidebarOpen(true)}
                        sectionLabel={
                            isSeller ? "Seller dashboard" : "Dashboard"
                        }
                        title={dashboardTitle}
                        searchPlaceholder={dashboardSearchPlaceholder}
                        messagesActive={messagesActive}
                        onMessagesOpen={() =>
                            onNavigate(
                                isSeller ? "seller-messages" : "messages",
                            )
                        }
                        messageItems={isSeller ? sellerMessages : messages}
                        messageActionLabel="View all messages"
                        notificationItems={
                            isSeller ? sellerNotifications : buyerNotifications
                        }
                        notificationActionLabel="View all updates"
                        profileLinks={
                            isSeller
                                ? [
                                      {
                                          label: "Seller profile",
                                          href: "/dashboard/seller/profile",
                                      },
                                      {
                                          label: "Manage gigs",
                                          href: "/dashboard/seller/services",
                                      },
                                      {
                                          label: "Payout settings",
                                          href: "/dashboard/seller/earnings",
                                      },
                                  ]
                                : undefined
                        }
                    />
                    {children}
                </div>
            </div>
        </>
    );
}
export default DashboardPage;
