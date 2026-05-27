import { useEffect, useMemo, useState } from "react";
import {
    initialsFromName,
    profilePathForSeller,
} from "../../utils/profilePaths.js";
import FavoriteButton from "../common/FavoriteButton.jsx";
import { Icon, Rating } from "../common/Icons.jsx";
import { useTranslation } from "react-i18next";
import { useMarketplaceStore } from "../../stores/useMarketplaceStore.js";
import { useSessionStore } from "../../stores/useSessionStore.js";
import {
    readRecentlyViewedGigs,
    subscribeToRecentlyViewedGigs,
} from "../../utils/recentlyViewedGigs.js";

function FeaturedServices({ onNavigate }) {
    const { t } = useTranslation();
    const currentUser = useSessionStore((state) => state.currentUser);
    const listingGigs = useMarketplaceStore((state) => state.listingGigs);
    const fetchGigs = useMarketplaceStore((state) => state.fetchGigs);
    const toggleSavedService = useMarketplaceStore(
        (state) => state.toggleSavedService,
    );
    const [recentlyViewed, setRecentlyViewed] = useState(() =>
        readRecentlyViewedGigs(),
    );
    const visibleServices = useMemo(() => {
        const featured = listingGigs.filter((service) => service.featured);
        const indexedGigs = new Map(listingGigs.map((gig) => [gig.id, gig]));
        const hydratedRecent = recentlyViewed.map((gig) => ({
            ...gig,
            ...(indexedGigs.get(gig.id) || {}),
        }));

        return uniqueById([...hydratedRecent, ...featured]).slice(0, 5);
    }, [listingGigs, recentlyViewed]);

    useEffect(() => {
        fetchGigs();
    }, [fetchGigs]);

    useEffect(
        () => subscribeToRecentlyViewedGigs(setRecentlyViewed),
        [],
    );

    if (visibleServices.length === 0) {
        return null;
    }

    const toggleFavorite = async (service) => {
        if (!currentUser?.authenticated) {
            onNavigate(
                `/?auth=login&redirect=${encodeURIComponent(`/gigs/${service.id}`)}`,
            );
            return null;
        }

        return toggleSavedService(service);
    };
    const openService = (service) => {
        onNavigate(`/gigs/${service.id}`);
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
                                    <FavoriteButton
                                        active={Boolean(service.saved)}
                                        className="gig-favorite-button"
                                        label={`${service.saved ? "Remove" : "Save"} ${service.title}`}
                                        stopPropagation
                                        onToggle={() =>
                                            toggleFavorite(service)
                                        }
                                    />
                                </div>

                                <div className="gig-seller-row">
                                    <span className="gig-seller-avatar">
                                        {service.avatar ? (
                                            <img
                                                src={service.avatar}
                                                alt=""
                                                loading="lazy"
                                                decoding="async"
                                            />
                                        ) : (
                                            service.sellerInitials ||
                                            initialsFromName(service.seller)
                                        )}
                                    </span>
                                    <a
                                        href={
                                            service.sellerProfilePath ||
                                            profilePathForSeller(
                                                service.seller,
                                                service.sellerUsername,
                                            )
                                        }
                                        onClick={(event) => {
                                            event.preventDefault();
                                            onNavigate(
                                                service.sellerProfilePath ||
                                                    profilePathForSeller(
                                                    service.seller,
                                                    service.sellerUsername,
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
                                        href={`/gigs/${service.id}`}
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
                                    ${service.price}
                                </strong>
                                {service.consultation ? (
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

function uniqueById(items) {
    const seen = new Set();

    return items.filter((item) => {
        if (!item?.id || seen.has(item.id)) {
            return false;
        }

        seen.add(item.id);
        return true;
    });
}
export default FeaturedServices;
