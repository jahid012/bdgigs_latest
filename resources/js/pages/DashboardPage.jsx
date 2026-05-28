import { useEffect, useState } from "react";
import { sellerSidebarItems } from "../data/dashboardData.js";
import Sidebar from "../components/dashboard/Sidebar.jsx";
import Topbar from "../components/dashboard/Topbar.jsx";
import { useTranslation } from "react-i18next";
import { useDashboardStore } from "../stores/useDashboardStore.js";
import { useSessionStore } from "../stores/useSessionStore.js";
import { apiRequest } from "../api/apiClient.js";
import { useToast } from "../components/common/ToastProvider.jsx";
function DashboardPage({
    children,
    messagesActive = false,
    onNavigate,
    searchPlaceholder,
    title,
    variant = "buyer",
}) {
    const { t } = useTranslation();
    const notify = useToast();
    const [isSidebarOpen, setIsSidebarOpen] = useState(false);
    const [verificationSending, setVerificationSending] = useState(false);
    const isSeller = variant === "seller";
    const currentUser = useSessionStore((state) => state.currentUser);
    const messageItems = useDashboardStore((state) =>
        isSeller ? state.sellerMessages : state.messages,
    );
    const notificationItems = useDashboardStore((state) =>
        isSeller ? state.sellerNotifications : state.buyerNotifications,
    );
    const fetchConversations = useDashboardStore(
        (state) => state.fetchConversations,
    );
    const fetchNotifications = useDashboardStore(
        (state) => state.fetchNotifications,
    );
    const hydrateSession = useSessionStore((state) => state.hydrateSession);
    const dashboardTitle = title || (isSeller ? "Seller Overview" : "Overview");
    const dashboardSearchPlaceholder =
        searchPlaceholder ||
        (isSeller
            ? "Search orders, buyers, gigs..."
            : "Search orders, sellers, services...");

    const resendVerification = async () => {
        setVerificationSending(true);

        try {
            const response = await apiRequest(
                "/api/email/verification-notification",
                { body: {} },
            );
            notify.success(response.message || "Verification email sent.");
        } catch (error) {
            notify.error(error.message || "Verification email could not be sent.");
        } finally {
            setVerificationSending(false);
        }
    };

    useEffect(() => {
        hydrateSession();
        fetchNotifications();
        fetchConversations();
    }, [fetchConversations, fetchNotifications, hydrateSession, isSeller]);

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
                        onMessagesOpen={() => onNavigate("messages")}
                        messageItems={messageItems}
                        messageActionLabel="View all messages"
                        notificationItems={notificationItems}
                        notificationActionLabel="View all updates"
                        profileName={currentUser?.name || "Guest"}
                        profileInitials={currentUser?.initials || "GU"}
                        profileAvatar={currentUser?.avatar || ""}
                        profileOnline={currentUser?.online ?? true}
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
                                      {
                                          label: "Buyer dashboard",
                                          href: "/dashboard",
                                      },
                                  ]
                                : undefined
                        }
                    />
                    {currentUser?.authenticated && currentUser.emailVerified === false ? (
                        <section className="dashboard-verification-banner" role="status">
                            <div>
                                <strong>Verify your email</strong>
                                <p>
                                    Protected checkout, order payments, and sensitive account actions require a verified email address.
                                </p>
                            </div>
                            <button
                                type="button"
                                disabled={verificationSending}
                                onClick={resendVerification}
                            >
                                {verificationSending ? "Sending..." : "Resend email"}
                            </button>
                        </section>
                    ) : null}
                    {children}
                </div>
            </div>
        </>
    );
}
export default DashboardPage;
