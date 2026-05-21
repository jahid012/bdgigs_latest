import { useCallback, useRef, useState } from "react";
import { Link } from "react-router-dom";
import { useDismissOnInteractOutside } from "../../hooks/useDismissOnInteractOutside.js";
import { Icon } from "../common/Icons.jsx";
import { useTranslation } from "react-i18next";
const defaultProfileLinks = [
    {
        label: "View profile",
        href: "/dashboard/profile",
    },
    {
        label: "Account settings",
        href: "/dashboard/settings",
    },
    {
        label: "Billing",
        href: "/dashboard/payments",
    },
];
function Topbar({
    isSidebarOpen,
    onSidebarOpen,
    sectionLabel = "Dashboard",
    title = "Overview",
    searchPlaceholder = "Search orders, sellers, services...",
    profileName = "Guest",
    profileInitials = "JA",
    profileLinks = defaultProfileLinks,
    profileActionLabel = "Sign out",
    messagesActive = false,
    onMessagesOpen,
    messageItems = [],
    messageActionLabel = "View all messages",
    notificationItems = [],
    notificationActionLabel = "View all updates",
}) {
    const { t } = useTranslation();
    const [openMenu, setOpenMenu] = useState(null);
    const topbarActionsRef = useRef(null);
    const closeMenus = useCallback(() => setOpenMenu(null), []);
    useDismissOnInteractOutside(
        topbarActionsRef,
        openMenu !== null,
        closeMenus,
    );
    const toggleMenu = (event, menu) => {
        event.stopPropagation();
        setOpenMenu((currentMenu) => (currentMenu === menu ? null : menu));
    };
    const openMessagesPage = () => {
        setOpenMenu(null);
        onMessagesOpen?.();
    };
    return (
        <header className="dashboard-topbar">
            <div className="topbar-left">
                <button
                    className="sidebar-toggle"
                    type="button"
                    aria-label={t("components.dashboard.topbar.openSidebar")}
                    aria-expanded={isSidebarOpen}
                    aria-controls="dashboardSidebar"
                    onClick={onSidebarOpen}
                >
                    <Icon name="menu" />
                </button>
                <div className="topbar-title">
                    <span>{sectionLabel}</span>
                    <strong>{title}</strong>
                </div>
            </div>

            <form
                className="topbar-search"
                role="search"
                aria-label={t("components.dashboard.topbar.searchDashboard")}
                onSubmit={(event) => event.preventDefault()}
            >
                <Icon name="search" />
                <label className="sr-only" htmlFor="dashboardSearch">
                    {" "}
                    {t("components.dashboard.topbar.searchDashboard")}{" "}
                </label>
                <input
                    id="dashboardSearch"
                    type="search"
                    placeholder={searchPlaceholder}
                    autoComplete="off"
                />
            </form>

            <div className="topbar-actions" ref={topbarActionsRef}>
                <div className="topbar-menu">
                    <button
                        className={`icon-button has-dot${openMenu === "notifications" ? " active" : ""}`}
                        type="button"
                        aria-label={t(
                            "components.dashboard.topbar.notifications",
                        )}
                        aria-haspopup="true"
                        aria-expanded={openMenu === "notifications"}
                        onClick={(event) => toggleMenu(event, "notifications")}
                    >
                        <Icon name="bell" />
                    </button>
                    <div
                        className={`topbar-dropdown feed-dropdown${openMenu === "notifications" ? " is-open" : ""}`}
                    >
                        <div className="topbar-dropdown-heading">
                            <div>
                                <span>
                                    {t(
                                        "components.dashboard.topbar.notifications",
                                    )}
                                </span>
                                <strong>
                                    {t(
                                        "components.dashboard.topbar.latestUpdates",
                                    )}
                                </strong>
                            </div>
                            <span className="status-badge status-completed">
                                {notificationItems.length}{" "}
                                {t("components.dashboard.topbar.new")}
                            </span>
                        </div>
                        <div className="topbar-feed-list">
                            {notificationItems.map((item) => (
                                <article
                                    className="topbar-feed-item"
                                    key={`${item.title}-${item.time}`}
                                >
                                    <span
                                        className="topbar-feed-icon"
                                        aria-hidden="true"
                                    >
                                        <Icon name="bell" />
                                    </span>
                                    <span>
                                        <strong>{item.title}</strong>
                                        <small>{item.detail}</small>
                                        <em>
                                            {item.type} - {item.time}
                                        </em>
                                    </span>
                                </article>
                            ))}
                        </div>
                        <button
                            className="topbar-dropdown-action"
                            type="button"
                            onClick={closeMenus}
                        >
                            {notificationActionLabel}
                        </button>
                    </div>
                </div>

                <div className="topbar-menu">
                    <button
                        className={`icon-button has-dot${messagesActive || openMenu === "messages" ? " active" : ""}`}
                        type="button"
                        aria-label={t("components.dashboard.topbar.messages")}
                        aria-haspopup="true"
                        aria-expanded={openMenu === "messages"}
                        aria-pressed={messagesActive}
                        onClick={(event) => toggleMenu(event, "messages")}
                    >
                        <Icon name="message" />
                    </button>
                    <div
                        className={`topbar-dropdown feed-dropdown${openMenu === "messages" ? " is-open" : ""}`}
                    >
                        <div className="topbar-dropdown-heading">
                            <div>
                                <span>
                                    {t("components.dashboard.topbar.messages")}
                                </span>
                                <strong>
                                    {t(
                                        "components.dashboard.topbar.recentConversations",
                                    )}
                                </strong>
                            </div>
                            <span className="status-badge status-delivered">
                                {messageItems.length}{" "}
                                {t("components.dashboard.topbar.threads")}
                            </span>
                        </div>
                        <div className="topbar-feed-list">
                            {messageItems.map((item) => (
                                <article
                                    className="topbar-feed-item message-feed-item"
                                    key={`${item.name}-${item.time}`}
                                >
                                    <span className="avatar">
                                        {item.initials}
                                    </span>
                                    <span>
                                        <strong>{item.name}</strong>
                                        <small>{item.message}</small>
                                        <em>{item.time}</em>
                                    </span>
                                </article>
                            ))}
                        </div>
                        <button
                            className="topbar-dropdown-action"
                            type="button"
                            onClick={openMessagesPage}
                        >
                            {messageActionLabel}
                        </button>
                    </div>
                </div>

                <div className="profile-menu">
                    <button
                        className="profile-button"
                        type="button"
                        aria-haspopup="true"
                        aria-expanded={openMenu === "profile"}
                        onClick={(event) => toggleMenu(event, "profile")}
                    >
                        <span className="avatar">{profileInitials}</span>
                        <strong>{profileName}</strong>
                        <Icon name="chevronDown" width="18" height="18" />
                    </button>
                    <div
                        className={`profile-dropdown${openMenu === "profile" ? " is-open" : ""}`}
                    >
                        {profileLinks.map((link) => (
                            <Link
                                to={link.href}
                                key={link.label}
                                onClick={closeMenus}
                            >
                                {link.label}
                            </Link>
                        ))}
                        <button type="button">{profileActionLabel}</button>
                    </div>
                </div>
            </div>
        </header>
    );
}
export default Topbar;
