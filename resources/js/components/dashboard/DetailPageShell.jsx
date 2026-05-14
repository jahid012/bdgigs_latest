import DashboardPageHeader from "./DashboardPageHeader.jsx";
import { Icon } from "../common/Icons.jsx";
import { useTranslation } from "react-i18next";
function HeaderActions({ content, onNavigate }) {
    if (!content.actionLabel) return null;
    if (content.actionPage) {
        const href =
            content.actionPage === "home"
                ? `/${content.actionHash || ""}`
                : `/dashboard/${content.actionPage}`;
        return (
            <a
                className="btn btn-primary"
                href={href}
                onClick={(event) => {
                    event.preventDefault();
                    onNavigate(content.actionPage, content.actionHash || "");
                }}
            >
                {content.actionLabel}
            </a>
        );
    }
    return (
        <button className="btn btn-primary" type="button">
            {content.actionLabel}
        </button>
    );
}
function DetailKpis({ variant, mode }) {
    const { t } = useTranslation();
    const seller = variant === "seller";
    const kpis = {
        services: seller
            ? [
                  {
                      label: "Best seller",
                      value: "Landing page",
                      icon: "star",
                      note: "18% conversion",
                  },
                  {
                      label: "Fastest delivery",
                      value: "2 days",
                      icon: "bolt",
                      note: "AI audit gig",
                  },
                  {
                      label: "Avg rating",
                      value: "4.9",
                      icon: "verifiedUser",
                      note: "Across live gigs",
                  },
              ]
            : [
                  {
                      label: "Ready to hire",
                      value: "7",
                      icon: "heart",
                      note: "Strong fit",
                  },
                  {
                      label: "Avg delivery",
                      value: "4 days",
                      icon: "packageCheck",
                      note: "Shortlisted",
                  },
                  {
                      label: "Price range",
                      value: "$95-$220",
                      icon: "payment",
                      note: "Flexible scope",
                  },
              ],
        profile: seller
            ? [
                  {
                      label: "Profile score",
                      value: "98%",
                      icon: "verifiedUser",
                      note: "Excellent",
                  },
                  {
                      label: "Reviews",
                      value: "186",
                      icon: "star",
                      note: "4.9 average",
                  },
                  {
                      label: "Repeat buyers",
                      value: "42%",
                      icon: "user",
                      note: "Trust signal",
                  },
              ]
            : [
                  {
                      label: "Profile score",
                      value: "92%",
                      icon: "verifiedUser",
                      note: "Almost done",
                  },
                  {
                      label: "Completed orders",
                      value: "42",
                      icon: "packageCheck",
                      note: "Trusted buyer",
                  },
                  {
                      label: "Saved briefs",
                      value: "8",
                      icon: "document",
                      note: "Reusable",
                  },
              ],
        settings: seller
            ? [
                  {
                      label: "Security",
                      value: "Strong",
                      icon: "settings",
                      note: "2FA enabled",
                  },
                  {
                      label: "Availability",
                      value: "Live",
                      icon: "bolt",
                      note: "Accepting work",
                  },
                  {
                      label: "Notifications",
                      value: "Priority",
                      icon: "bell",
                      note: "Buyer first",
                  },
              ]
            : [
                  {
                      label: "Security",
                      value: "Strong",
                      icon: "settings",
                      note: "2FA enabled",
                  },
                  {
                      label: "Notifications",
                      value: "Daily",
                      icon: "bell",
                      note: "Digest on",
                  },
                  {
                      label: "Privacy",
                      value: "Team only",
                      icon: "user",
                      note: "Controlled",
                  },
              ],
    }[mode];
    return (
        <section
            className="detail-kpi-grid"
            aria-label={t(
                "components.dashboard.detailpageshell.pageHighlights",
            )}
        >
            {kpis.map((kpi) => (
                <article
                    className="card order-kpi-card detail-kpi-card"
                    key={kpi.label}
                >
                    <span className="stat-icon" aria-hidden="true">
                        <Icon name={kpi.icon} />
                    </span>
                    <div>
                        <span>{kpi.label}</span>
                        <strong>{kpi.value}</strong>
                        <small>{kpi.note}</small>
                    </div>
                </article>
            ))}
        </section>
    );
}
function DetailPageShell({ children, content, onNavigate, variant }) {
    return (
        <main className="dashboard-content detail-page">
            <DashboardPageHeader
                eyebrow={content.eyebrow}
                title={content.title}
                titleId={content.titleId}
                description={content.description}
                stats={content.stats}
                actions={
                    <HeaderActions content={content} onNavigate={onNavigate} />
                }
            />

            <DetailKpis variant={variant} mode={content.mode} />
            {children}
        </main>
    );
}
export default DetailPageShell;
