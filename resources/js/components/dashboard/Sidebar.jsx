import { NavLink } from "react-router-dom";
import { sidebarItems } from "../../data/dashboardData.js";
import { Icon } from "../common/Icons.jsx";

function Sidebar({
  isOpen,
  onClose,
  onNavigate,
  items = sidebarItems,
  label = "Workspace",
  upgradeEyebrow = "Pro insight",
  upgradeTitle = "BDGigs Pro",
  upgradeCopy = "Unlock priority talent matching and advanced buyer insights.",
  upgradeAction = "Upgrade",
}) {
  return (
    <>
      <aside
        className={`dashboard-sidebar${isOpen ? " is-open" : ""}`}
        id="dashboardSidebar"
        aria-label="Dashboard navigation"
      >
        <div className="sidebar-header">
          <a
            className="dashboard-brand"
            href="/"
            aria-label="BDGigs home"
            onClick={(event) => {
              event.preventDefault();
              onNavigate("home");
            }}
          >
            <span aria-hidden="true">
              <Icon name="brand" />
            </span>
            BDGigs
          </a>
          <button className="sidebar-close" type="button" aria-label="Close sidebar" onClick={onClose}>
            <Icon name="close" />
          </button>
        </div>

        <span className="sidebar-label">{label}</span>
        <nav className="sidebar-menu">
          {items.map((item) =>
            item.path ? (
              <NavLink className={({ isActive }) => (isActive ? "active" : "")} to={item.path} end={item.end} key={item.label} onClick={onClose}>
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

        <div className="sidebar-upgrade">
          <span>{upgradeEyebrow}</span>
          <h3>{upgradeTitle}</h3>
          <p>{upgradeCopy}</p>
          <a className="btn btn-primary" href="#">
            {upgradeAction}
          </a>
        </div>
      </aside>

      <div className={`sidebar-overlay${isOpen ? " is-open" : ""}`} onClick={onClose}></div>
    </>
  );
}

export default Sidebar;
