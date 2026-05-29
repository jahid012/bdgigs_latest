import { useEffect, useMemo, useRef, useState } from "react";
import Slider from "react-slick";
import "slick-carousel/slick/slick-theme.css";
import "slick-carousel/slick/slick.css";
import {
    aiDirectors,
    creatorServiceCards,
    marketplaceBenefits,
} from "../../data/homeData.js";
const SlickSlider = Slider?.default || Slider;
import { apiRequest } from "../../api/apiClient.js";
import { BrandMark, Icon } from "../common/Icons.jsx";
import { useTranslation } from "react-i18next";
const creatorRoutes = {
    "Vibe Coding": "/search/gigs?query=vibe%20coding&source=creator-card",
    "Website Development": "/categories/programming-tech/website-development",
    "Video Editing": "/categories/video-animation/video-editing",
    "Software Development": "/categories/programming-tech/website-development",
    "Book Publishing":
        "/search/gigs?query=book%20publishing&source=creator-card",
    "Architecture & Interior Design":
        "/search/gigs?query=architecture%20interior%20design&source=creator-card",
};
function HowItWorks({ onNavigate }) {
    const { t } = useTranslation();
    const [creatorItems, setCreatorItems] = useState([]);
    const [isCreatorLoading, setIsCreatorLoading] = useState(true);
    const sliderItems = useMemo(
        () =>
            creatorItems.length
                ? creatorItems.map((item) => ({
                      title: item.title,
                      image: item.image,
                      color: item.color || "#f4f6f8",
                      description: item.description,
                      linkUrl:
                          item.linkUrl ||
                          `/search/gigs?query=${encodeURIComponent(item.title)}&source=creator-card`,
                  }))
                : creatorServiceCards.map((card) => ({
                      ...card,
                      linkUrl:
                          creatorRoutes[card.title] ||
                          "/search/gigs?source=creator-card",
                  })),
        [creatorItems],
    );
    const dragState = useRef({ moved: false, startX: 0, startY: 0 });

    const sliderSettings = {
        arrows: true,
        dots: true,
        infinite: sliderItems.length > 4,
        swipeToSlide: true,
        draggable: true,
        touchMove: true,
        touchThreshold: 8,
        cssEase: "ease-out",
        useTransform: true,
        pauseOnHover: false,
        nextArrow: <CreatorSliderArrow direction="next" />,
        prevArrow: <CreatorSliderArrow direction="prev" />,
        responsive: [
            {
                breakpoint: 1100,
                settings: { slidesToShow: 3 },
            },
            {
                breakpoint: 760,
                settings: { slidesToShow: 2 },
            },
            {
                breakpoint: 560,
                settings: { slidesToShow: 1 },
            },
        ],
        slidesToScroll: 1,
        slidesToShow: Math.min(4, Math.max(1, sliderItems.length)),
        speed: 320,
    };

    const resetDrag = (event) => {
        const x = event.touches ? event.touches[0].clientX : event.clientX;
        const y = event.touches ? event.touches[0].clientY : event.clientY;
        dragState.current = { moved: false, startX: x, startY: y };
    };

    const trackDrag = (event) => {
        const state = dragState.current;
        if (!state || state.moved) {
            return;
        }
        const x = event.touches ? event.touches[0].clientX : event.clientX;
        const y = event.touches ? event.touches[0].clientY : event.clientY;
        if (Math.hypot(x - state.startX, y - state.startY) > 8) {
            state.moved = true;
        }
    };

    const handleCreatorLinkClick = (event, link) => {
        if (dragState.current?.moved) {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        onNavigate(link);
    };

    useEffect(() => {
        let active = true;

        apiRequest("/api/home/creator-marketplace")
            .then((items) => {
                if (active) {
                    setCreatorItems(items || []);
                }
            })
            .catch(() => {
                if (active) {
                    setCreatorItems([]);
                }
            })
            .finally(() => {
                if (active) {
                    setIsCreatorLoading(false);
                }
            });

        return () => {
            active = false;
        };
    }, []);

    return (
        <>
            <section className="creator-marketplace-section" id="how-it-works">
                <div className="container">
                    <div
                        className={`creator-card-row creator-slick-slider${isCreatorLoading ? " is-loading" : ""}`}
                    >
                        <SlickSlider {...sliderSettings}>
                            {sliderItems.map((card) => (
                                <article
                                    className="creator-service-card"
                                    key={card.title}
                                    style={{
                                        "--card-bg": card.color,
                                    }}
                                >
                                    <a
                                        className="creator-card-link"
                                        href={
                                            card.linkUrl ||
                                            creatorRoutes[card.title] ||
                                            "/search/gigs?source=creator-card"
                                        }
                                        onMouseDown={resetDrag}
                                        onMouseMove={trackDrag}
                                        onTouchStart={resetDrag}
                                        onTouchMove={trackDrag}
                                        onClick={(event) =>
                                            handleCreatorLinkClick(
                                                event,
                                                card.linkUrl ||
                                                    creatorRoutes[card.title] ||
                                                    "/search/gigs?source=creator-card",
                                            )
                                        }
                                    >
                                        <h3>{card.title}</h3>
                                        <span className="creator-service-image">
                                            <img
                                                src={card.image}
                                                alt={card.title}
                                                loading="eager"
                                                decoding="async"
                                            />
                                        </span>
                                    </a>
                                    {card.description ? (
                                        <p>{card.description}</p>
                                    ) : null}
                                </article>
                            ))}
                        </SlickSlider>
                    </div>

                    <div className="freelancer-benefit-header">
                        <h2>
                            {t(
                                "components.home.howitworks.makeItAllHappenWithFreelancers",
                            )}
                        </h2>
                        <a
                            href="/?auth=register"
                            onClick={(event) => {
                                event.preventDefault();
                                onNavigate("/", "?auth=register");
                            }}
                        >
                            {t("components.home.howitworks.joinNow")}
                        </a>
                    </div>

                    <div className="freelancer-benefit-grid">
                        {marketplaceBenefits.map((benefit) => (
                            <article
                                className="freelancer-benefit"
                                key={benefit.title}
                            >
                                <span aria-hidden="true">
                                    <Icon name={benefit.icon} />
                                </span>
                                <h3>{benefit.title}</h3>
                                <p>{benefit.copy}</p>
                            </article>
                        ))}
                    </div>
                </div>
            </section>

            <section
                className="ai-director-section"
                aria-labelledby="aiDirectorTitle"
            >
                <div className="container">
                    <div className="ai-director-panel">
                        <div className="ai-director-copy">
                            <h2 id="aiDirectorTitle">
                                {t(
                                    "components.home.howitworks.theAiDirectorEraHasArrived",
                                )}
                            </h2>
                            <p>
                                {" "}
                                {t(
                                    "components.home.howitworks.fromVisionToFinalFrameWorkWithRenowned",
                                )}{" "}
                            </p>
                            <a
                                href="/categories/ai-services/ai-applications"
                                onClick={(event) => {
                                    event.preventDefault();
                                    onNavigate(
                                        "/categories/ai-services/ai-applications",
                                    );
                                }}
                            >
                                {" "}
                                {t(
                                    "components.home.howitworks.findYourAiDirector",
                                )}{" "}
                            </a>
                        </div>

                        <div
                            className="ai-director-stack"
                            aria-label={t(
                                "components.home.howitworks.featuredAiDirectors",
                            )}
                        >
                            {aiDirectors.map((director) => (
                                <article
                                    className={`ai-director-card${director.featured ? " is-featured" : ""}`}
                                    key={director.name}
                                >
                                    <img
                                        src={director.image}
                                        alt=""
                                        loading="lazy"
                                        decoding="async"
                                    />
                                    <strong>{director.name}</strong>
                                </article>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            <section
                className="expert-sourcing-section"
                aria-labelledby="expertSourcingTitle"
            >
                <div className="container">
                    <div className="expert-sourcing-panel">
                        <div className="expert-sourcing-copy">
                            <span className="pro-brand">
                                <BrandMark />
                            </span>
                            <h2 id="expertSourcingTitle">
                                {t(
                                    "components.home.howitworks.letExpertsFindTheRightFreelancerForYou",
                                )}
                            </h2>
                            <ul>
                                <li>
                                    {t(
                                        "components.home.howitworks.workWithExpertsWhoWillSourceInterviewAnd",
                                    )}
                                </li>
                                <li>
                                    {t(
                                        "components.home.howitworks.getAReportWithClearRecommendations",
                                    )}
                                </li>
                                <li>
                                    {t(
                                        "components.home.howitworks.hireVettedFreelanceTalentWithConfidence",
                                    )}
                                </li>
                            </ul>
                            <a
                                href="/search/gigs?query=expert%20sourcing&source=home"
                                onClick={(event) => {
                                    event.preventDefault();
                                    onNavigate(
                                        "/search/gigs?query=expert%20sourcing&source=home",
                                    );
                                }}
                            >
                                {" "}
                                {t(
                                    "components.home.howitworks.discoverExpertSourcing",
                                )}{" "}
                            </a>
                            <p className="money-back">
                                <Icon name="payment" />{" "}
                                {t(
                                    "components.home.howitworks.100MoneyBackGuarantee",
                                )}{" "}
                            </p>
                        </div>

                        <div
                            className="expert-profile-stack"
                            aria-hidden="true"
                        >
                            <article className="expert-profile-card ghost-card">
                                <img
                                    src="https://images.pexels.com/photos/3769021/pexels-photo-3769021.jpeg?auto=compress&cs=tinysrgb&w=360"
                                    alt=""
                                />
                            </article>
                            <article className="expert-profile-card">
                                <span className="expert-chat-bubble">...</span>
                                <img
                                    src="https://images.pexels.com/photos/774909/pexels-photo-774909.jpeg?auto=compress&cs=tinysrgb&w=360"
                                    alt=""
                                />
                                <strong>
                                    {t("components.home.howitworks.lillian")}
                                </strong>
                                <small>
                                    {t(
                                        "components.home.howitworks.websiteDeveloper",
                                    )}
                                </small>
                            </article>
                            <article className="expert-profile-card ghost-card right">
                                <img
                                    src="https://images.pexels.com/photos/2379004/pexels-photo-2379004.jpeg?auto=compress&cs=tinysrgb&w=360"
                                    alt=""
                                />
                            </article>
                            <span className="expert-cursor"></span>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}

function CreatorSliderArrow({ className = "", direction, onClick, style }) {
    return (
        <button
            className={`${className} creator-carousel-button ${direction}`}
            type="button"
            aria-label={direction === "next" ? "Next services" : "Previous services"}
            onClick={onClick}
            style={style}
        >
            <Icon name="arrowRight" />
        </button>
    );
}
export default HowItWorks;
