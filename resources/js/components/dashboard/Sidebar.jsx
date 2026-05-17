import { NavLink } from "react-router-dom";
import { sidebarItems } from "../../data/dashboardData.js";
import { Icon } from "../common/Icons.jsx";
import { useTranslation } from "react-i18next";
function Sidebar({
    isOpen,
    onClose,
    onNavigate,
    items = sidebarItems,
    label = "Workspace",
    upgradeEyebrow = "Pro insight",
    upgradeTitle = "bdgigs Pro",
    upgradeCopy = "Unlock priority talent matching and advanced buyer insights.",
    upgradeAction = "Upgrade",
}) {
    const { t } = useTranslation();
    return (
        <>
            <aside
                className={`dashboard-sidebar${isOpen ? " is-open" : ""}`}
                id="dashboardSidebar"
                aria-label={t(
                    "components.dashboard.sidebar.dashboardNavigation",
                )}
            >
                <div className="sidebar-header">
                    <a
                        className="dashboard-brand"
                        href="/"
                        aria-label={t(
                            "components.dashboard.sidebar.bdgigsHome",
                        )}
                        onClick={(event) => {
                            event.preventDefault();
                            onNavigate("home");
                        }}
                    >
                        <span aria-hidden="true">
                            <Icon name="brand" />
                        </span>
                        bdgigs
                    </a>
                    <button
                        className="sidebar-close"
                        type="button"
                        aria-label={t(
                            "components.dashboard.sidebar.closeSidebar",
                        )}
                        onClick={onClose}
                    >
                        <Icon name="close" />
                    </button>
                </div>

                <span className="sidebar-label">{label}</span>
                <nav className="sidebar-menu">
                    {items.map((item) =>
                        item.path ? (
                            <NavLink
                                className={({ isActive }) =>
                                    isActive ? "active" : ""
                                }
                                to={item.path}
                                end={item.end}
                                key={item.label}
                                onClick={onClose}
                            >
                                <Icon name={item.icon} />
                                {item.label}
                            </NavLink>
                        ) : (
                            <a
                                href="#"
                                key={item.label}
                                onClick={(event) => {
                                    event.preventDefault();
                                }}
                            >
                                <Icon name={item.icon} />
                                {item.label}
                            </a>
                        ),
                    )}
                </nav>
            </aside>

            <div
                className={`sidebar-overlay${isOpen ? " is-open" : ""}`}
                onClick={onClose}
            ></div>
        </>
    );
}
export default Sidebar;
