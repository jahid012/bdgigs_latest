import { useState } from "react";
import { FinanceNotice } from "./FinanceControls.jsx";
import { Icon, Rating } from "../common/Icons.jsx";
import { useTranslation } from "react-i18next";
function MinimalServiceList({ content, onNavigate, seller = false, services }) {
    const { t } = useTranslation();
    const [activeFilter, setActiveFilter] = useState("all");
    const [notice, setNotice] = useState("");
    const filters = seller
        ? [
              {
                  id: "all",
                  label: "All services",
              },
              {
                  id: "live",
                  label: "Live",
              },
              {
                  id: "optimize",
                  label: "Optimize",
              },
          ]
        : [
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
          ];
    const filteredServices = services.filter((service) => {
        if (activeFilter === "all") return true;
        if (seller) return service.status.toLowerCase() === activeFilter;
        if (activeFilter === "fast")
            return Number.parseInt(service.delivery, 10) <= 3;
        if (activeFilter === "budget")
            return Number(service.price.replace(/[^0-9.]/g, "")) < 150;
        return true;
    });
    return (
        <section className="minimal-services-section">
            <FinanceNotice message={notice} />
            <article className="service-list-panel">
                <div className="service-list-header">
                    <div>
                        <span className="card-kicker">{content.kicker}</span>
                        <h2>{content.heading}</h2>
                    </div>
                    <div className="service-list-actions">
                        <div
                            className="service-list-tabs"
                            aria-label={
                                seller
                                    ? "Filter seller services"
                                    : "Filter saved services"
                            }
                        >
                            {filters.map((filter) => (
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
                        <a
                            href={
                                seller
                                    ? "/dashboard/seller/orders"
                                    : "/#services"
                            }
                            onClick={(event) => {
                                event.preventDefault();
                                onNavigate(
                                    seller ? "seller-orders" : "home",
                                    seller ? "" : "#services",
                                );
                            }}
                        >
                            {seller ? "View orders" : "Browse marketplace"}
                        </a>
                    </div>
                </div>

                <div className="minimal-service-list">
                    {filteredServices.map((service) => (
                        <article
                            className="minimal-service-row"
                            key={service.title}
                        >
                            <a
                                className="minimal-service-thumb"
                                href={
                                    seller
                                        ? "/dashboard/seller/services"
                                        : "/#services"
                                }
                                onClick={(event) => {
                                    event.preventDefault();
                                    setNotice(
                                        seller
                                            ? `${service.title} opened for editing.`
                                            : `${service.title} opened for comparison.`,
                                    );
                                }}
                            >
                                <img
                                    src={service.image}
                                    alt={`${service.title} preview`}
                                    loading="lazy"
                                    decoding="async"
                                />
                            </a>
                            <div className="minimal-service-main">
                                <h3>{service.title}</h3>
                                <p>
                                    {seller ? service.category : service.seller}{" "}
                                    <Rating value={service.rating} />
                                </p>
                                <div className="minimal-service-meta">
                                    <span>{service.tag}</span>
                                    <span>{service.delivery}</span>
                                    {seller ? (
                                        <span>{service.orders}</span>
                                    ) : null}
                                    {seller ? (
                                        <span>{service.conversion}</span>
                                    ) : null}
                                </div>
                            </div>
                            <div className="minimal-service-side">
                                {seller ? (
                                    <span
                                        className={`status-badge ${service.statusClass}`}
                                    >
                                        {service.status}
                                    </span>
                                ) : null}
                                <strong>{service.price}</strong>
                                <span>{seller ? "Starts at" : "From"}</span>
                            </div>
                            <div className="minimal-service-controls">
                                <button
                                    className="service-text-button"
                                    type="button"
                                    onClick={() =>
                                        setNotice(
                                            seller
                                                ? `${service.title} is ready to edit.`
                                                : `${service.title} added to compare list.`,
                                        )
                                    }
                                >
                                    {seller ? "Edit" : "Compare"}
                                </button>
                                <button
                                    className="service-icon-button"
                                    type="button"
                                    aria-label={
                                        seller
                                            ? "Service options"
                                            : "Remove saved service"
                                    }
                                    onClick={() =>
                                        setNotice(
                                            seller
                                                ? `${service.title} options opened.`
                                                : `${service.title} removed from this view.`,
                                        )
                                    }
                                >
                                    <Icon
                                        name={
                                            seller ? "moreHorizontal" : "heart"
                                        }
                                    />
                                </button>
                            </div>
                        </article>
                    ))}
                </div>

                {filteredServices.length === 0 ? (
                    <div className="minimal-service-empty">
                        <Icon name="search" />
                        <h3>
                            {t(
                                "components.dashboard.minimalservicelist.noServicesMatchThisFilter",
                            )}
                        </h3>
                        <p>
                            {t(
                                "components.dashboard.minimalservicelist.tryAnotherFilterToSeeMoreSavedOr",
                            )}
                        </p>
                    </div>
                ) : null}
            </article>
        </section>
    );
}
export default MinimalServiceList;
