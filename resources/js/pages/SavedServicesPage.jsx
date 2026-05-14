import { useMemo, useState } from "react";
import { recommendedServices } from "../data/dashboardData.js";
import { dashboardDetailCopy } from "../data/dashboardPageData.js";
import DashboardPageHeader from "../components/dashboard/DashboardPageHeader.jsx";
import { FinanceNotice } from "../components/dashboard/FinanceControls.jsx";
import { Icon, Rating } from "../components/common/Icons.jsx";
import { useTranslation } from "react-i18next";
const savedServiceDetails = [
    {
        match: "96%",
        response: "Replies in 1h",
        savedAt: "Saved 2 days ago",
        note: "Best for the landing page refresh brief.",
        signal: "Strong SaaS fit",
    },
    {
        match: "91%",
        response: "Replies today",
        savedAt: "Saved yesterday",
        note: "Good paid acquisition support after launch.",
        signal: "Campaign ready",
    },
    {
        match: "94%",
        response: "Replies in 2h",
        savedAt: "Saved this week",
        note: "Clear fit for investor-facing collateral.",
        signal: "Fast delivery",
    },
    {
        match: "88%",
        response: "Replies in 4h",
        savedAt: "Saved Apr 30",
        note: "Useful for cleanup before publishing.",
        signal: "Budget friendly",
    },
];
const savedFilters = [
    {
        id: "all",
        label: "All saved",
    },
    {
        id: "fast",
        label: "Fast delivery",
    },
    {
        id: "budget",
        label: "Under $150",
    },
    {
        id: "topRated",
        label: "Top rated",
    },
];
function SavedServicesPage({ onNavigate }) {
    const { t } = useTranslation();
    const content = dashboardDetailCopy.buyer.savedServices;
    const services = useMemo(
        () =>
            recommendedServices.map((service, index) => ({
                ...service,
                ...savedServiceDetails[index],
            })),
        [],
    );
    const [activeFilter, setActiveFilter] = useState("all");
    const [searchTerm, setSearchTerm] = useState("");
    const [notice, setNotice] = useState("");
    const [selectedServices, setSelectedServices] = useState(
        () => new Set(services.slice(0, 2).map((service) => service.title)),
    );
    const filteredServices = services.filter((service) =>
        matchesSavedServiceFilter(service, activeFilter, searchTerm),
    );
    const selectedList = services.filter((service) =>
        selectedServices.has(service.title),
    );
    const topMatch = services.reduce(
        (best, service) =>
            Number.parseInt(service.match, 10) > Number.parseInt(best.match, 10)
                ? service
                : best,
        services[0],
    );
    const toggleSelected = (serviceTitle) => {
        setSelectedServices((current) => {
            const next = new Set(current);
            if (next.has(serviceTitle)) {
                next.delete(serviceTitle);
            } else {
                next.add(serviceTitle);
            }
            return next;
        });
    };
    const browseMarketplace = (event) => {
        event.preventDefault();
        onNavigate("home", "#services");
    };
    return (
        <main className="dashboard-content detail-page saved-services-page">
            <DashboardPageHeader
                title={content.title}
                titleId={content.titleId}
                description={content.description}
                stats={content.stats}
            />

            <section
                className="saved-services-summary-grid"
                aria-label={t("pages.savedservicespage.savedServiceInsights")}
            >
                <SavedInsightCard
                    icon="heart"
                    label="Ready to compare"
                    value={selectedList.length}
                    detail="Selected shortlist"
                />
                <SavedInsightCard
                    icon="star"
                    label="Best match"
                    value={topMatch.match}
                    detail={topMatch.title}
                />
                <SavedInsightCard
                    icon="payment"
                    label="Lowest price"
                    value="$95"
                    detail="WordPress cleanup"
                />
            </section>

            <FinanceNotice message={notice} />

            <section
                className="saved-services-workspace"
                aria-label={t("pages.savedservicespage.savedServicesWorkspace")}
            >
                <div className="saved-services-main">
                    <div className="saved-services-toolbar">
                        <div>
                            <span className="card-kicker">
                                {t("pages.savedservicespage.shortlistedTalent")}
                            </span>
                            <h2>
                                {t(
                                    "pages.savedservicespage.servicesReadyToCompare",
                                )}
                            </h2>
                        </div>
                        <form
                            className="saved-services-search"
                            role="search"
                            onSubmit={(event) => event.preventDefault()}
                        >
                            <Icon name="search" />
                            <label
                                className="sr-only"
                                htmlFor="savedServicesSearch"
                            >
                                {" "}
                                {t(
                                    "pages.savedservicespage.searchSavedServices",
                                )}{" "}
                            </label>
                            <input
                                id="savedServicesSearch"
                                type="search"
                                value={searchTerm}
                                placeholder={t(
                                    "pages.savedservicespage.searchServicesSellersCategories",
                                )}
                                onChange={(event) =>
                                    setSearchTerm(event.target.value)
                                }
                            />
                        </form>
                    </div>

                    <div className="saved-services-filter-row">
                        <div
                            className="service-list-tabs"
                            aria-label={t(
                                "pages.savedservicespage.filterSavedServices",
                            )}
                        >
                            {savedFilters.map((filter) => (
                                <button
                                    className={
                                        activeFilter === filter.id
                                            ? "active"
                                            : ""
                                    }
                                    type="button"
                                    aria-pressed={activeFilter === filter.id}
                                    key={filter.id}
                                    onClick={() => setActiveFilter(filter.id)}
                                >
                                    {filter.label}
                                </button>
                            ))}
                        </div>
                        <a href="/#services" onClick={browseMarketplace}>
                            {" "}
                            {t(
                                "pages.savedservicespage.browseMarketplace",
                            )}{" "}
                        </a>
                    </div>

                    <div className="saved-service-list">
                        {filteredServices.length > 0 ? (
                            filteredServices.map((service) => (
                                <SavedServiceRow
                                    key={service.title}
                                    selected={selectedServices.has(
                                        service.title,
                                    )}
                                    service={service}
                                    onOpen={() =>
                                        setNotice(
                                            `${service.title} opened for comparison.`,
                                        )
                                    }
                                    onRemove={() =>
                                        setNotice(
                                            `${service.title} removed from this view.`,
                                        )
                                    }
                                    onSelect={() =>
                                        toggleSelected(service.title)
                                    }
                                />
                            ))
                        ) : (
                            <div className="minimal-service-empty">
                                <Icon name="search" />
                                <h3>
                                    {t(
                                        "pages.savedservicespage.noSavedServicesFound",
                                    )}
                                </h3>
                                <p>
                                    {t(
                                        "pages.savedservicespage.tryADifferentFilterOrSearchTermTo",
                                    )}
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                <aside
                    className="saved-services-aside"
                    aria-label={t("pages.savedservicespage.shortlistTools")}
                >
                    <section className="saved-compare-panel">
                        <div className="saved-aside-heading">
                            <span className="card-kicker">
                                {t("pages.savedservicespage.compareTray")}
                            </span>
                            <h2>
                                {selectedList.length}{" "}
                                {t("pages.savedservicespage.selected")}
                            </h2>
                        </div>
                        <div className="saved-compare-list">
                            {selectedList.map((service) => (
                                <article key={service.title}>
                                    <img src={service.image} alt="" />
                                    <div>
                                        <strong>{service.title}</strong>
                                        <span>
                                            {service.price} - {service.delivery}
                                        </span>
                                    </div>
                                </article>
                            ))}
                        </div>
                        <button
                            className="settings-dark-button"
                            type="button"
                            onClick={() =>
                                setNotice(
                                    "Compare view is ready for your selected services.",
                                )
                            }
                        >
                            {" "}
                            {t("pages.savedservicespage.compareSelected")}{" "}
                        </button>
                    </section>

                    <section className="saved-next-step-panel">
                        <div className="saved-aside-heading">
                            <span className="card-kicker">
                                {t("pages.savedservicespage.nextStep")}
                            </span>
                            <h2>
                                {t(
                                    "pages.savedservicespage.turnShortlistIntoABrief",
                                )}
                            </h2>
                        </div>
                        <p>
                            {t(
                                "pages.savedservicespage.shareScopeTimelineAndBudgetWithYourTop",
                            )}
                        </p>
                        <div className="saved-check-list">
                            <span>
                                <Icon name="verifiedUser" />{" "}
                                {t(
                                    "pages.savedservicespage.compareAtLeastTwoOptions",
                                )}
                            </span>
                            <span>
                                <Icon name="document" />{" "}
                                {t(
                                    "pages.savedservicespage.attachProjectNotes",
                                )}
                            </span>
                            <span>
                                <Icon name="payment" />{" "}
                                {t(
                                    "pages.savedservicespage.confirmBudgetRange",
                                )}
                            </span>
                        </div>
                        <button
                            className="settings-light-button"
                            type="button"
                            onClick={() =>
                                setNotice(
                                    "Brief builder opened for saved services.",
                                )
                            }
                        >
                            {" "}
                            {t("pages.savedservicespage.createBrief")}{" "}
                        </button>
                    </section>
                </aside>
            </section>
        </main>
    );
}
function SavedInsightCard({ detail, icon, label, value }) {
    return (
        <article className="saved-insight-card">
            <span className="stat-icon" aria-hidden="true">
                <Icon name={icon} />
            </span>
            <div>
                <span>{label}</span>
                <strong>{value}</strong>
                <small>{detail}</small>
            </div>
        </article>
    );
}
function SavedServiceRow({ onOpen, onRemove, onSelect, selected, service }) {
    const { t } = useTranslation();
    return (
        <article
            className={`saved-service-row${selected ? " is-selected" : ""}`}
        >
            <button
                className="saved-select-button"
                type="button"
                aria-pressed={selected}
                aria-label={`Select ${service.title} for comparison`}
                onClick={onSelect}
            >
                <Icon name={selected ? "packageCheck" : "plus"} />
            </button>
            <a
                className="saved-service-thumb"
                href="/#services"
                onClick={(event) => {
                    event.preventDefault();
                    onOpen();
                }}
            >
                <img
                    src={service.image}
                    alt={`${service.title} preview`}
                    loading="lazy"
                    decoding="async"
                />
            </a>
            <div className="saved-service-main">
                <div className="saved-service-title-row">
                    <div>
                        <h3>{service.title}</h3>
                        <p>
                            {service.seller} <Rating value={service.rating} />
                        </p>
                    </div>
                    <span className="status-badge status-completed">
                        {service.signal}
                    </span>
                </div>
                <p className="saved-service-note">{service.note}</p>
                <div className="saved-service-meta">
                    <span>{service.tag}</span>
                    <span>{service.delivery}</span>
                    <span>{service.response}</span>
                    <span>{service.savedAt}</span>
                    <span>
                        {service.match} {t("pages.savedservicespage.match")}
                    </span>
                </div>
            </div>
            <div className="saved-service-side">
                <strong>{service.price}</strong>
                <span>{t("pages.savedservicespage.from")}</span>
                <button
                    className="service-text-button"
                    type="button"
                    onClick={onOpen}
                >
                    {" "}
                    {t("pages.savedservicespage.compare")}{" "}
                </button>
                <button
                    className="service-icon-button"
                    type="button"
                    aria-label={`Remove ${service.title} from saved services`}
                    onClick={onRemove}
                >
                    <Icon name="heart" />
                </button>
            </div>
        </article>
    );
}
function matchesSavedServiceFilter(service, activeFilter, searchTerm) {
    const query = searchTerm.trim().toLowerCase();
    const searchable = [
        service.title,
        service.seller,
        service.tag,
        service.signal,
    ]
        .join(" ")
        .toLowerCase();
    if (query && !searchable.includes(query)) {
        return false;
    }
    if (activeFilter === "fast") {
        return Number.parseInt(service.delivery, 10) <= 3;
    }
    if (activeFilter === "budget") {
        return Number(service.price.replace(/[^0-9.]/g, "")) < 150;
    }
    if (activeFilter === "topRated") {
        return Number(service.rating) >= 4.9;
    }
    return true;
}
export default SavedServicesPage;
