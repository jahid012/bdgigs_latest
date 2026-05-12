function DashboardPageHeader({ title, description, titleId, stats = [], actions }) {
  return (
    <section className="dashboard-page-header" aria-labelledby={titleId}>
      <div className="dashboard-page-heading">
        <h1 id={titleId}>{title}</h1>
        <p>{description}</p>
      </div>

      {(stats.length > 0 || actions) && (
        <div className="dashboard-page-header-side">
          {stats.length > 0 ? (
            <div className="dashboard-page-stats" aria-label={`${title} summary`}>
              {stats.map((stat) => (
                <span key={stat.label}>
                  <strong>{stat.value}</strong>
                  {stat.label}
                </span>
              ))}
            </div>
          ) : null}
        </div>
      )}
    </section>
  );
}

export default DashboardPageHeader;
