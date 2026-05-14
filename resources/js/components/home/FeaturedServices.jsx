import { useState } from "react";
import { services } from "../../data/homeData.js";
import { profilePathForSeller } from "../../data/userProfileData.js";
import { Icon, Rating } from "../common/Icons.jsx";
import { useTranslation } from "react-i18next";
const serviceDetailRoutes = {
    "brand-identity": "/gigs/ai-website-chatbot",
    "web-dashboard": "/gigs/codecanyon-install",
    "seo-growth": "/gigs/codecanyon-hosting",
    "ai-assistant": "/gigs/ai-website-chatbot",
    "product-video": "/gigs/wix-redesign",
    "wordpress-speed": "/gigs/wordpress-transfer",
};
function FeaturedServices({ onNavigate }) {
    const { t } = useTranslation();
    const [favorites, setFavorites] = useState(() => new Set());
    const visibleServices = services.slice(0, 5);
    const toggleFavorite = (serviceId) => {
        setFavorites((current) => {
            const next = new Set(current);
            if (next.has(serviceId)) {
                next.delete(serviceId);
            } else {
                next.add(serviceId);
            }
            return next;
        });
    };
    const openService = (service) => {
        onNavigate(
            serviceDetailRoutes[service.id] ||
                `/search/gigs?query=${encodeURIComponent(service.title)}&source=home-card`,
        );
    };
    return (
        <section className="recently-viewed-section" id="services">
            <div className="container">
                <div className="recently-viewed-heading">
                    <h2>
                        {t(
                            "components.home.featuredservices.recentlyViewedAndMore",
                        )}
                    </h2>
                </div>

                <div className="recently-viewed-row">
                    {visibleServices.map((service) => {
                        const isFavorite = favorites.has(service.id);
                        return (
                            <article className="gig-card" key={service.id}>
                                <div
                                    className="gig-thumb"
                                    role="link"
                                    tabIndex={0}
                                    onClick={() => openService(service)}
                                    onKeyDown={(event) => {
                                        if (
                                            event.key === "Enter" ||
                                            event.key === " "
                                        ) {
                                            event.preventDefault();
                                            openService(service);
                                        }
                                    }}
                                >
                                    <img
                                        src={service.image}
                                        alt={service.imageAlt}
                                        loading="lazy"
                                        decoding="async"
                                    />
                                    <button
                                        className="gig-play-button"
                                        type="button"
                                        aria-label={`Preview ${service.title}`}
                                        onClick={(event) =>
                                            event.stopPropagation()
                                        }
                                    >
                                        <Icon name="play" />
                                    </button>
                                    <button
                                        className={`gig-favorite-button${isFavorite ? " is-favorite" : ""}`}
                                        type="button"
                                        aria-label={`Save ${service.title}`}
                                        aria-pressed={isFavorite}
                                        onClick={(event) => {
                                            event.stopPropagation();
                                            toggleFavorite(service.id);
                                        }}
                                    >
                                        <Icon name="heart" />
                                    </button>
                                </div>

                                <div className="gig-seller-row">
                                    <span className="gig-seller-avatar">
                                        {service.initials}
                                    </span>
                                    <a
                                        href={profilePathForSeller(
                                            service.seller,
                                        )}
                                        onClick={(event) => {
                                            event.preventDefault();
                                            onNavigate(
                                                profilePathForSeller(
                                                    service.seller,
                                                ),
                                            );
                                        }}
                                    >
                                        <strong>{service.seller}</strong>
                                    </a>
                                    <span>{service.level}</span>
                                </div>

                                <h3>
                                    <a
                                        href={
                                            serviceDetailRoutes[service.id] ||
                                            `/search/gigs?query=${encodeURIComponent(service.title)}&source=home-card`
                                        }
                                        onClick={(event) => {
                                            event.preventDefault();
                                            openService(service);
                                        }}
                                    >
                                        {service.title}
                                    </a>
                                </h3>

                                <div className="gig-rating-row">
                                    <Rating
                                        value={service.rating}
                                        reviews={service.reviews}
                                    />
                                </div>

                                <strong className="gig-price">
                                    {t("components.home.featuredservices.from")}{" "}
                                    {service.price}
                                </strong>
                                {service.id === "brand-identity" ? (
                                    <span className="gig-consultation">
                                        {t(
                                            "components.home.featuredservices.offersVideoConsultations",
                                        )}
                                    </span>
                                ) : null}
                            </article>
                        );
                    })}

                    <button
                        className="gig-carousel-button"
                        type="button"
                        aria-label={t(
                            "components.home.featuredservices.viewMoreServices",
                        )}
                        onClick={() =>
                            onNavigate("/search/gigs?source=recently-viewed")
                        }
                    >
                        <Icon name="arrowRight" />
                    </button>
                </div>
            </div>
        </section>
    );
}
export default FeaturedServices;
