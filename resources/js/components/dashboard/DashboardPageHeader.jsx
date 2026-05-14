function DashboardPageHeader({
    actions,
    className = "",
    description,
    eyebrow,
    stats = [],
    title,
    titleId,
}) {
    return (
        <section
            className={`dashboard-page-header${className ? ` ${className}` : ""}`}
            aria-labelledby={titleId}
        >
            <div className="dashboard-page-heading">
                {eyebrow ? (
                    <span className="dashboard-hero-eyebrow">{eyebrow}</span>
                ) : null}
                <h1 id={titleId}>{title}</h1>
                <p>{description}</p>
            </div>

            {(stats.length > 0 || actions) && (
                <div className="dashboard-page-header-side">
                    {stats.length > 0 ? (
                        <div
                            className="dashboard-page-stats"
                            aria-label={`${title} summary`}
                        >
                            {stats.map((stat) => (
                                <span key={stat.label}>
                                    <strong>{stat.value}</strong>
                                    {stat.label}
                                </span>
                            ))}
                        </div>
                    ) : null}
                    {actions ? (
                        <div className="dashboard-page-actions">{actions}</div>
                    ) : null}
                </div>
            )}
        </section>
    );
}

export default DashboardPageHeader;
