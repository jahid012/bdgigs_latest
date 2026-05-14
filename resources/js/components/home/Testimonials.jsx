import { useRef } from "react";
import { guideCards } from "../../data/homeData.js";
import { Icon } from "../common/Icons.jsx";
import { useTranslation } from "react-i18next";
function Testimonials({ onNavigate }) {
    const { t } = useTranslation();
    const guidesRef = useRef(null);
    const scrollGuides = () => {
        guidesRef.current?.scrollBy({
            left: 360,
            behavior: "smooth",
        });
    };
    return (
        <>
            <section
                className="guides-section"
                id="guides"
                aria-labelledby="guidesTitle"
            >
                <div className="container">
                    <div className="guides-heading">
                        <h2 id="guidesTitle">
                            {t(
                                "components.home.testimonials.guidesToHelpYouGrow",
                            )}
                        </h2>
                        <a
                            href="/search/gigs?query=guides&source=home"
                            onClick={(event) => {
                                event.preventDefault();
                                onNavigate(
                                    "/search/gigs?query=guides&source=home",
                                );
                            }}
                        >
                            {" "}
                            {t(
                                "components.home.testimonials.seeMoreGuides",
                            )}{" "}
                        </a>
                    </div>

                    <div className="guide-card-row" ref={guidesRef}>
                        {guideCards.map((guide) => (
                            <article className="guide-card" key={guide.title}>
                                <a
                                    href={`/search/gigs?query=${encodeURIComponent(guide.title)}&source=guide-card`}
                                    onClick={(event) => {
                                        event.preventDefault();
                                        onNavigate(
                                            `/search/gigs?query=${encodeURIComponent(guide.title)}&source=guide-card`,
                                        );
                                    }}
                                >
                                    <img
                                        src={guide.image}
                                        alt=""
                                        loading="eager"
                                        decoding="async"
                                    />
                                    <h3>{guide.title}</h3>
                                </a>
                            </article>
                        ))}
                    </div>

                    <button
                        className="guide-carousel-button"
                        type="button"
                        aria-label={t(
                            "components.home.testimonials.viewMoreGuides",
                        )}
                        onClick={scrollGuides}
                    >
                        <Icon name="arrowRight" />
                    </button>
                </div>
            </section>

            <section
                className="fingerprints-cta-section"
                aria-labelledby="fingerprintsTitle"
            >
                <div className="container">
                    <div className="fingerprints-cta-panel">
                        <h2 id="fingerprintsTitle">
                            {" "}
                            {t(
                                "components.home.testimonials.freelanceServicesAtYour",
                            )}{" "}
                            <span>
                                {t("components.home.testimonials.fingertips")}
                            </span>
                        </h2>
                        <a href="/register">
                            {t("components.home.testimonials.joinBdgigs")}
                        </a>
                    </div>
                </div>
            </section>
        </>
    );
}
export default Testimonials;
