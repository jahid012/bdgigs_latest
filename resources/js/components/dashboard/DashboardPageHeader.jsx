function DashboardPageHeader({
    actions,
    className = "",
    stats = [],
    titleId,
}) {
    if (!actions && stats.length === 0) {
        return null;
    }

    return (
        <section
            className={`dashboard-page-header${className ? ` ${className}` : ""}`}
            aria-labelledby={titleId}
        >
            {(stats.length > 0 || actions) && (
                <div className="dashboard-page-header-side">
                    {actions ? (
                        <div className="dashboard-page-actions">{actions}</div>
                    ) : null}
                </div>
            )}
        </section>
    );
}

export default DashboardPageHeader;
