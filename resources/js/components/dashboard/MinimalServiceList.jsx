import { useRef, useState } from "react";
import { Icon, Rating } from "../common/Icons.jsx";
import LoadingSkeleton from "../common/LoadingSkeleton.jsx";
import { useToast } from "../common/ToastProvider.jsx";
import { useTranslation } from "react-i18next";
import { useDismissOnInteractOutside } from "../../hooks/useDismissOnInteractOutside.js";
function MinimalServiceList({
    content,
    loading = false,
    onNavigate,
    onServiceDelete,
    onServiceStatusChange,
    seller = false,
    services,
}) {
    const { t } = useTranslation();
    const notify = useToast();
    const [activeFilter, setActiveFilter] = useState("all");
    const [busyServiceId, setBusyServiceId] = useState("");
    const [openServiceMenuId, setOpenServiceMenuId] = useState("");
    const serviceSectionRef = useRef(null);
    useDismissOnInteractOutside(
        serviceSectionRef,
        Boolean(openServiceMenuId),
        () => setOpenServiceMenuId(""),
    );
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
                  id: "draft",
                  label: "Draft",
              },
              {
                  id: "paused",
                  label: "Paused",
              },
              {
                  id: "review",
                  label: "Review",
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
        if (seller) return sellerServiceMatchesFilter(service, activeFilter);
        if (activeFilter === "fast")
            return Number.parseInt(service.delivery, 10) <= 3;
        if (activeFilter === "budget")
            return Number(service.price.replace(/[^0-9.]/g, "")) < 150;
        return true;
    });
    const openSellerGigEditor = (service) => {
        onNavigate(`/dashboard/seller/services/${service.id}/edit`);
    };
    const runSellerAction = async (service, action) => {
        if (busyServiceId) return;

        setBusyServiceId(service.id);

        try {
            if (action === "delete") {
                if (
                    !window.confirm(
                        `Delete ${service.title}? It will be hidden from the marketplace and kept for admin review.`,
                    )
                ) {
                    return;
                }

                await onServiceDelete?.(service);
                notify.success(`${service.title} moved out of your services.`);
                return;
            }

            await onServiceStatusChange?.(service, action);
            notify.success(
                action === "activate"
                    ? `${service.title} is live.`
                    : `${service.title} is paused.`,
            );
        } catch (error) {
            notify.error(error.message || "Unable to update this service.");
        } finally {
            setBusyServiceId("");
            setOpenServiceMenuId("");
        }
    };

    return (
        <section className="minimal-services-section" ref={serviceSectionRef}>
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
                    {loading && services.length === 0 ? (
                        <MinimalServiceRowsSkeleton />
                    ) : null}
                    {filteredServices.map((service) => (
                        <article
                            className="minimal-service-row"
                            key={service.id || service.title}
                        >
                            <a
                                className="minimal-service-thumb"
                                href={
                                    seller
                                        ? `/dashboard/seller/services/${service.id}/edit`
                                        : "/#services"
                                }
                                onClick={(event) => {
                                    event.preventDefault();
                                    if (seller) {
                                        openSellerGigEditor(service);
                                        return;
                                    }

                                    notify.info(
                                        `${service.title} opened for comparison.`,
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
                                    onClick={() => {
                                        if (seller) {
                                            openSellerGigEditor(service);
                                            return;
                                        }

                                        notify.success(
                                            `${service.title} added to compare list.`,
                                        );
                                    }}
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
                                    aria-expanded={
                                        seller
                                            ? openServiceMenuId === service.id
                                            : undefined
                                    }
                                    onClick={(event) => {
                                        if (!seller) {
                                            notify.info(
                                                `${service.title} removed from this view.`,
                                            );
                                            return;
                                        }

                                        event.stopPropagation();
                                        setOpenServiceMenuId((current) =>
                                            current === service.id
                                                ? ""
                                                : service.id,
                                        );
                                    }}
                                >
                                    <Icon
                                        name={
                                            seller ? "moreHorizontal" : "heart"
                                        }
                                    />
                                </button>
                                {seller ? (
                                    <SellerServiceActionMenu
                                        busy={busyServiceId === service.id}
                                        isOpen={
                                            openServiceMenuId === service.id
                                        }
                                        onActivate={() =>
                                            runSellerAction(service, "activate")
                                        }
                                        onDelete={() =>
                                            runSellerAction(service, "delete")
                                        }
                                        onEdit={() =>
                                            openSellerGigEditor(service)
                                        }
                                        onPause={() =>
                                            runSellerAction(service, "pause")
                                        }
                                        onPreview={() =>
                                            onNavigate(`/gigs/${service.id}`)
                                        }
                                        service={service}
                                    />
                                ) : null}
                            </div>
                        </article>
                    ))}
                </div>

                {!loading && filteredServices.length === 0 ? (
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

function SellerServiceActionMenu({
    busy,
    isOpen,
    onActivate,
    onDelete,
    onEdit,
    onPause,
    onPreview,
    service,
}) {
    const paused = sellerServiceStatusKey(service) === "paused";

    return (
        <div
            className={`seller-service-action-menu${isOpen ? " is-open" : ""}`}
            role="menu"
        >
            <button
                disabled={busy}
                type="button"
                role="menuitem"
                onClick={onPreview}
            >
                <Icon name="eye" /> Preview
            </button>
            <button
                disabled={busy}
                type="button"
                role="menuitem"
                onClick={onEdit}
            >
                <Icon name="edit" /> Edit
            </button>
            {paused ? (
                <button
                    disabled={busy}
                    type="button"
                    role="menuitem"
                    onClick={onActivate}
                >
                    <Icon name="play" /> Activate
                </button>
            ) : (
                <button
                    disabled={busy}
                    type="button"
                    role="menuitem"
                    onClick={onPause}
                >
                    <Icon name="archive" /> Pause
                </button>
            )}
            <button
                className="danger"
                disabled={busy}
                type="button"
                role="menuitem"
                onClick={onDelete}
            >
                <Icon name="trash" /> Delete
            </button>
        </div>
    );
}

function MinimalServiceRowsSkeleton() {
    return Array.from({ length: 3 }, (_, index) => (
        <article
            className="minimal-service-row minimal-service-skeleton"
            key={index}
        >
            <LoadingSkeleton className="minimal-service-skeleton-thumb" />
            <div className="minimal-service-skeleton-copy">
                <LoadingSkeleton className="minimal-service-skeleton-title" />
                <LoadingSkeleton className="minimal-service-skeleton-line" />
                <div>
                    <LoadingSkeleton />
                    <LoadingSkeleton />
                </div>
            </div>
            <LoadingSkeleton className="minimal-service-skeleton-price" />
            <LoadingSkeleton className="minimal-service-skeleton-action" />
        </article>
    ));
}

function sellerServiceMatchesFilter(service, filter) {
    const status = sellerServiceStatusKey(service);

    if (filter === "live") return ["live", "published", "approved"].includes(status);
    if (filter === "review") {
        return ["needs-edit", "pending", "rejected", "review"].includes(status);
    }

    return status === filter;
}

function sellerServiceStatusKey(service) {
    return String(service.statusKey || service.status || "")
        .trim()
        .toLowerCase()
        .replace(/\s+/g, "-");
}
export default MinimalServiceList;
