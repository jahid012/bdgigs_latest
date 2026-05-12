import AccountSettingsPanel from "../components/dashboard/settings/AccountSettingsPanel.jsx";

function DashboardSettingsPage({ onNavigate, variant = "buyer" }) {
  return <AccountSettingsPanel onNavigate={onNavigate} variant={variant} />;
}

export default DashboardSettingsPage;
