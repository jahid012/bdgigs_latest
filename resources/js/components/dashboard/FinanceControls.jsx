import { Icon } from "../common/Icons.jsx";
import { useTranslation } from "react-i18next";
export function FinanceTabs({ tabs, activeTab, onChange }) {
    const { t } = useTranslation();
    return (
        <div
            className="finance-tabs"
            role="tablist"
            aria-label={t(
                "components.dashboard.financecontrols.financePageSections",
            )}
        >
            {tabs.map((tab) => (
                <button
                    className={activeTab === tab.id ? "active" : ""}
                    type="button"
                    role="tab"
                    aria-selected={activeTab === tab.id}
                    key={tab.id}
                    onClick={() => onChange(tab.id)}
                >
                    {tab.label}
                </button>
            ))}
        </div>
    );
}
export function FinanceNotice({ message }) {
    if (!message) return null;
    return (
        <div className="finance-notice" role="status">
            {message}
        </div>
    );
}
export function FilterButton({ label, value, onClick }) {
    return (
        <button
            className="finance-filter-button"
            type="button"
            onClick={onClick}
        >
            {value || label}
            <Icon name="chevronDown" />
        </button>
    );
}
export function FinanceEmptyState({
    title,
    description,
    actionLabel,
    onAction,
}) {
    return (
        <div className="finance-empty-state">
            <div className="finance-empty-illustration" aria-hidden="true">
                <Icon name="document" />
            </div>
            <h3>{title}</h3>
            <p>{description}</p>
            {actionLabel ? (
                <button
                    className="finance-primary-button"
                    type="button"
                    onClick={onAction}
                >
                    {actionLabel}
                </button>
            ) : null}
        </div>
    );
}
