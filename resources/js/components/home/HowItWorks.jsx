import { useRef } from "react";
import {
    aiDirectors,
    creatorServiceCards,
    marketplaceBenefits,
} from "../../data/homeData.js";
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
    const cardsRef = useRef(null);
    const scrollCards = () => {
        cardsRef.current?.scrollBy({
            left: 250,
            behavior: "smooth",
        });
    };
    return (
        <>
            <section className="creator-marketplace-section" id="how-it-works">
                <div className="container">
                    <div className="creator-card-row" ref={cardsRef}>
                        {creatorServiceCards.map((card) => (
                            <a
                                className="creator-service-card"
                                href={
                                    creatorRoutes[card.title] ||
                                    "/search/gigs?source=creator-card"
                                }
                                key={card.title}
                                style={{
                                    "--card-bg": card.color,
                                }}
                                onClick={(event) => {
                                    event.preventDefault();
                                    onNavigate(
                                        creatorRoutes[card.title] ||
                                            "/search/gigs?source=creator-card",
                                    );
                                }}
                            >
                                <h3>{card.title}</h3>
                                <span className="creator-service-image">
                                    <img
                                        src={card.image}
                                        alt=""
                                        loading="eager"
                                        decoding="async"
                                    />
                                </span>
                            </a>
                        ))}
                    </div>

                    <button
                        className="creator-carousel-button"
                        type="button"
                        aria-label={t(
                            "components.home.howitworks.viewMoreFreelancerServices",
                        )}
                        onClick={scrollCards}
                    >
                        <Icon name="arrowRight" />
                    </button>

                    <div className="freelancer-benefit-header">
                        <h2>
                            {t(
                                "components.home.howitworks.makeItAllHappenWithFreelancers",
                            )}
                        </h2>
                        <a href="/?auth=register">
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
                                <BrandMark />{" "}
                                {t("components.home.howitworks.bdgigsPro")}{" "}
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
export default HowItWorks;
