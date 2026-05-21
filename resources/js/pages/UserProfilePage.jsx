import { useEffect, useRef, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { Icon } from "../components/common/Icons.jsx";
import Footer from "../components/layout/Footer.jsx";
import Header from "../components/layout/Header.jsx";
import { getUserProfile } from "../data/userProfileData.js";
import { useConversationLauncher } from "../hooks/useConversationLauncher.js";
import { useTranslation } from "react-i18next";
const profileTabs = [
    {
        id: "about",
        label: "About Me",
    },
    {
        id: "services",
        label: "Services",
    },
    {
        id: "portfolio",
        label: "Portfolio",
    },
    {
        id: "reviews",
        label: "Reviews",
    },
];
function UserProfilePage({ onNavigate }) {
    const { username } = useParams();
    const profile = getUserProfile(username);
    const [activeSection, setActiveSection] = useState("about");
    const [isStickyNavVisible, setIsStickyNavVisible] = useState(false);
    const [activeProfileDialog, setActiveProfileDialog] = useState("");
    const [isAboutSheetClosing, setIsAboutSheetClosing] = useState(false);
    const summaryRef = useRef(null);
    const openContactPopup = () => {
        setIsAboutSheetClosing(false);
        setActiveProfileDialog("contact");
    };
    const openAboutSheet = () => {
        setIsAboutSheetClosing(false);
        setActiveProfileDialog("about");
    };
    const closeProfileDialog = () => {
        if (activeProfileDialog === "about") {
            setIsAboutSheetClosing(true);
            return;
        }

        setActiveProfileDialog("");
    };
    const finishAboutSheetClose = () => {
        setActiveProfileDialog("");
        setIsAboutSheetClosing(false);
    };

    useEffect(() => {
        const observers = profileTabs
            .map(({ id }) => document.getElementById(id))
            .filter(Boolean)
            .map((section) => {
                const observer = new IntersectionObserver(
                    ([entry]) => {
                        if (entry.isIntersecting) {
                            setActiveSection(section.id);
                        }
                    },
                    {
                        rootMargin: "-170px 0px -55% 0px",
                        threshold: 0.01,
                    },
                );
                observer.observe(section);
                return observer;
            });
        return () => observers.forEach((observer) => observer.disconnect());
    }, [profile.slug]);
    useEffect(() => {
        const updateStickyNavVisibility = () => {
            const summary = summaryRef.current;
            if (!summary) return;
            const headerOffset = window.matchMedia("(max-width: 980px)").matches
                ? 58
                : 66;
            setIsStickyNavVisible(
                summary.getBoundingClientRect().bottom <= headerOffset,
            );
        };
        updateStickyNavVisibility();
        window.addEventListener("scroll", updateStickyNavVisibility, {
            passive: true,
        });
        window.addEventListener("resize", updateStickyNavVisibility);
        return () => {
            window.removeEventListener("scroll", updateStickyNavVisibility);
            window.removeEventListener("resize", updateStickyNavVisibility);
        };
    }, [profile.slug]);
    useEffect(() => {
        if (!activeProfileDialog) {
            return undefined;
        }

        const closeOnEscape = (event) => {
            if (event.key === "Escape") {
                closeProfileDialog();
            }
        };

        window.addEventListener("keydown", closeOnEscape);

        return () => window.removeEventListener("keydown", closeOnEscape);
    }, [activeProfileDialog]);

    return (
        <div
            className={`user-profile-page${isStickyNavVisible ? " has-profile-sticky-nav" : ""}`}
        >
            <Header
                enableMarketplaceHeader={false}
                forceSearch
                onNavigate={onNavigate}
            />

            <main>
                <section className="public-profile-shell">
                    <div className="container">
                        <ProfileHero
                            onContact={openContactPopup}
                            onMoreAbout={openAboutSheet}
                            profile={profile}
                            summaryRef={summaryRef}
                        />
                    </div>
                </section>

                <ProfileStickyNav
                    activeSection={activeSection}
                    isVisible={isStickyNavVisible}
                    onContact={openContactPopup}
                    profile={profile}
                />

                <div className="container public-profile-content">
                    <ServicesSection profile={profile} />
                    <PortfolioSection profile={profile} />
                    <WorkExperienceSection profile={profile} />
                    <ProfileSourcingCTA />
                    <ReviewsSection profile={profile} />
                </div>

                <ProfileTalentSection />
            </main>

            {activeProfileDialog === "contact" ? (
                <ProfileContactPopup
                    profile={profile}
                    onClose={closeProfileDialog}
                />
            ) : null}

            {activeProfileDialog === "about" ? (
                <ProfileAboutSheet
                    isClosing={isAboutSheetClosing}
                    profile={profile}
                    onClose={closeProfileDialog}
                    onClosed={finishAboutSheetClose}
                    onContact={openContactPopup}
                />
            ) : null}

            <ProfileMessageBubble profile={profile} />
            <Footer />
        </div>
    );
}
function ProfileHero({ onContact, onMoreAbout, profile, summaryRef }) {
    const { t } = useTranslation();
    return (
        <header className="public-profile-hero" id="about">
            <div className="public-profile-primary">
                <div className="public-profile-avatar-wrap">
                    <img src={profile.avatar} alt={`${profile.name} profile`} />
                    <span
                        className="profile-online-dot"
                        aria-label={t("pages.userprofilepage.online")}
                    ></span>
                </div>

                <div className="public-profile-summary" ref={summaryRef}>
                    <div className="public-profile-name-row">
                        <h1>{profile.name}</h1>
                        <span>{profile.handle}</span>
                    </div>

                    <div className="public-profile-rating-row">
                        <ProfileRating
                            rating={profile.rating}
                            reviews={profile.reviews}
                        />
                        <span>{profile.level}</span>
                    </div>

                    <strong className="public-profile-title">
                        {profile.title}
                    </strong>

                    <div className="public-profile-meta">
                        <span>
                            <Icon name="location" />
                            {profile.location}
                        </span>
                        <span>
                            <Icon name="message" />
                            {profile.languages.join(", ")}
                        </span>
                    </div>
                </div>

                <section className="public-about-block">
                    <h2>{t("pages.userprofilepage.aboutMe")}</h2>
                    <p>
                        {profile.about}{" "}
                        <a href="#portfolio">
                            {t("pages.userprofilepage.readMore")}
                        </a>
                    </p>
                </section>

                <section
                    className="public-skills-block"
                    aria-labelledby="profileSkillsTitle"
                >
                    <h2 id="profileSkillsTitle">
                        {t("pages.userprofilepage.skills")}
                    </h2>
                    <div>
                        {profile.skills.map((skill) => (
                            <Link
                                to={`/search/gigs?query=${encodeURIComponent(skill)}&source=profile-skill`}
                                key={skill}
                            >
                                {skill}
                            </Link>
                        ))}
                    </div>
                </section>
            </div>

            <aside
                className="public-profile-contact-column"
                aria-label={`Contact ${profile.name}`}
            >
                <div className="profile-top-actions">
                    <button type="button" onClick={onMoreAbout}>
                        {t("pages.userprofilepage.moreAboutMe")}
                    </button>
                    <button type="button" aria-label={`Save ${profile.name}`}>
                        <Icon name="heart" />
                    </button>
                </div>
                <article className="profile-contact-card">
                    <div>
                        <img src={profile.avatar} alt="" />
                        <span
                            className="profile-online-dot"
                            aria-hidden="true"
                        ></span>
                        <div>
                            <strong>{profile.name}</strong>
                            <p>
                                {t("pages.userprofilepage.online2")}{" "}
                                {profile.localTime}{" "}
                                {t("pages.userprofilepage.localTime")}
                            </p>
                        </div>
                    </div>
                    <button type="button" onClick={onContact}>
                        <Icon name="send" />{" "}
                        {t("pages.userprofilepage.contactMe")}{" "}
                    </button>
                    <p>
                        {t("pages.userprofilepage.averageResponseTime")}{" "}
                        {profile.responseTime}
                    </p>
                </article>
            </aside>
        </header>
    );
}
function ProfileStickyNav({ activeSection, isVisible, onContact, profile }) {
    const { t } = useTranslation();
    return (
        <div
            className={`profile-sticky-nav${isVisible ? " is-visible" : ""}`}
            aria-hidden={!isVisible}
            aria-label={t("pages.userprofilepage.profileSections")}
        >
            <div className="container">
                <div className="sticky-profile-person">
                    <div className="sticky-profile-avatar">
                        <img src={profile.avatar} alt="" />
                        <span
                            className="profile-online-dot"
                            aria-hidden="true"
                        ></span>
                    </div>
                    <div>
                        <strong>{profile.name}</strong>
                        <ProfileRating
                            rating={profile.rating}
                            reviews={profile.reviews}
                        />
                        <span>
                            {t("pages.userprofilepage.online2")}{" "}
                            {profile.localTime}{" "}
                            {t("pages.userprofilepage.localTime")}
                        </span>
                    </div>
                </div>

                <nav className="sticky-profile-tabs">
                    {profileTabs.map((tab) => (
                        <a
                            className={
                                activeSection === tab.id ? "is-active" : ""
                            }
                            href={`#${tab.id}`}
                            key={tab.id}
                        >
                            {tab.label}
                        </a>
                    ))}
                </nav>

                <div className="sticky-profile-action">
                    <button type="button" onClick={onContact}>
                        <Icon name="send" />{" "}
                        {t("pages.userprofilepage.contactMe")}{" "}
                    </button>
                    <span>
                        {t("pages.userprofilepage.averageResponseTime")}{" "}
                        {profile.responseTime}
                    </span>
                </div>
            </div>
        </div>
    );
}
function ServicesSection({ profile }) {
    const { t } = useTranslation();
    return (
        <section
            className="profile-section profile-services-section"
            id="services"
        >
            <h2>{t("pages.userprofilepage.seeMyServices")}</h2>
            <div className="profile-service-list">
                {profile.services.map((service) => (
                    <article className="profile-service-card" key={service.id}>
                        <div className="profile-service-main">
                            <img src={service.image} alt="" />
                            <div>
                                <h3>{service.title}</h3>
                                <p>{service.description}</p>
                            </div>
                        </div>
                        <div className="profile-service-footer">
                            <span>
                                {" "}
                                {t("pages.userprofilepage.from")}{" "}
                                <strong>
                                    ${service.price}{" "}
                                    {t("pages.userprofilepage.project")}
                                </strong>
                            </span>
                            <Link to={`/gigs/${service.id}`}>
                                {t("pages.userprofilepage.moreDetails")}
                            </Link>
                        </div>
                    </article>
                ))}
            </div>
        </section>
    );
}
function PortfolioSection({ profile }) {
    const { t } = useTranslation();
    const { portfolio } = profile;
    return (
        <section
            className="profile-section profile-portfolio-section"
            id="portfolio"
        >
            <h2>{t("pages.userprofilepage.portfolio")}</h2>
            <div className="profile-portfolio-layout">
                <article className="profile-portfolio-card">
                    <div className="profile-portfolio-image">
                        <img src={portfolio.image} alt="" />
                        <span>
                            <Icon name="camera" />
                            {portfolio.thumbnails.length + 2}
                        </span>
                    </div>
                    <div className="profile-portfolio-copy">
                        <span>{portfolio.date}</span>
                        <h3>{portfolio.title}</h3>
                        <p>{portfolio.description}</p>
                        <div className="profile-portfolio-tags">
                            {portfolio.tags.map((tag) => (
                                <span key={tag}>{tag}</span>
                            ))}
                        </div>
                        <dl>
                            <div>
                                <dt>
                                    {t("pages.userprofilepage.projectCost")}
                                </dt>
                                <dd>{portfolio.cost}</dd>
                            </div>
                            <div>
                                <dt>
                                    {t("pages.userprofilepage.projectDuration")}
                                </dt>
                                <dd>{portfolio.duration}</dd>
                            </div>
                        </dl>
                    </div>
                </article>

                <div
                    className="profile-portfolio-thumbs"
                    aria-label={t("pages.userprofilepage.portfolioThumbnails")}
                >
                    {portfolio.thumbnails.slice(0, 2).map((image, index) => (
                        <button
                            className={index === 0 ? "is-active" : ""}
                            type="button"
                            key={`${image}-${index}`}
                        >
                            <img src={image} alt="" />
                        </button>
                    ))}
                    <button className="profile-project-count" type="button">
                        {" "}
                        {t("pages.userprofilepage.3")}{" "}
                        <span>{t("pages.userprofilepage.projects")}</span>
                    </button>
                </div>
            </div>
        </section>
    );
}
function WorkExperienceSection({ profile }) {
    const { t } = useTranslation();
    return (
        <section className="profile-section profile-work-section">
            <h2>{t("pages.userprofilepage.workExperience")}</h2>
            <div className="profile-work-list">
                {profile.workExperience.map((item) => (
                    <article
                        className="profile-work-item"
                        key={`${item.role}-${item.company}`}
                    >
                        <span>
                            <Icon name="building" />
                        </span>
                        <div>
                            <h3>{item.role}</h3>
                            <p>
                                {item.company} - {item.type}
                            </p>
                            <small>
                                {item.period} - {item.duration}
                            </small>
                            <p>{item.description}</p>
                        </div>
                    </article>
                ))}
            </div>
        </section>
    );
}
function ProfileSourcingCTA() {
    const { t } = useTranslation();
    return (
        <section className="profile-sourcing-cta">
            <div>
                <h2>
                    {t(
                        "pages.userprofilepage.getTheRightFreelancerWithoutTheSearch",
                    )}
                </h2>
                <p>
                    {t(
                        "pages.userprofilepage.wellHandleTheSourcingInterviewingAndVettingSo",
                    )}
                </p>
                <button type="button">
                    {" "}
                    {t("pages.userprofilepage.sourceForMe")}{" "}
                    <Icon name="arrowRight" />
                </button>
            </div>
            <div className="profile-sourcing-stack" aria-hidden="true">
                {["Eugene Cherniak", "Alina Cruz", "P Musilenko"].map(
                    (name, index) => (
                        <article key={name}>
                            <img
                                src={`https://images.pexels.com/photos/${[220453, 774909, 614810][index]}/pexels-photo-${[220453, 774909, 614810][index]}.jpeg?auto=compress&cs=tinysrgb&w=120`}
                                alt=""
                            />
                            <strong>{name}</strong>
                            <span></span>
                        </article>
                    ),
                )}
            </div>
        </section>
    );
}
function ReviewsSection({ profile }) {
    const { t } = useTranslation();
    const reviews = profile.reviewsData;
    const sample = reviews.sample;
    return (
        <section
            className="profile-section profile-reviews-section"
            id="reviews"
        >
            <div className="profile-reviews-summary">
                <div>
                    <h2>
                        {reviews.count} {t("pages.userprofilepage.reviews")}
                    </h2>
                    {reviews.breakdown.map((row) => (
                        <div
                            className="profile-review-breakdown-row"
                            key={row.label}
                        >
                            <span>{row.label}</span>
                            <i>
                                <b
                                    style={{
                                        width: `${row.value}%`,
                                    }}
                                ></b>
                            </i>
                            <strong>({row.count})</strong>
                        </div>
                    ))}
                </div>
                <div>
                    <ProfileRating rating={reviews.rating} reviews={0} />
                    <h3>{t("pages.userprofilepage.ratingBreakdown")}</h3>
                    {reviews.ratings.map((row) => (
                        <div
                            className="profile-rating-breakdown-row"
                            key={row.label}
                        >
                            <span>{row.label}</span>
                            <strong>
                                <Icon name="star" />
                                {row.value.toFixed(0)}
                            </strong>
                        </div>
                    ))}
                </div>
            </div>

            <div className="profile-review-tools">
                <form
                    className="profile-review-search"
                    role="search"
                    onSubmit={(event) => event.preventDefault()}
                >
                    <label className="sr-only" htmlFor="profileReviewSearch">
                        {" "}
                        {t("pages.userprofilepage.searchReviews")}{" "}
                    </label>
                    <input
                        id="profileReviewSearch"
                        type="search"
                        placeholder={t("pages.userprofilepage.searchReviews")}
                    />
                    <button
                        type="submit"
                        aria-label={t("pages.userprofilepage.searchReviews")}
                    >
                        <Icon name="search" />
                    </button>
                </form>
                <div>
                    <span>
                        {t("pages.userprofilepage.15OutOf")} {reviews.count}{" "}
                        {t("pages.userprofilepage.reviews")}
                    </span>
                    <span>
                        {" "}
                        {t("pages.userprofilepage.sortBy")}{" "}
                        <strong>
                            {t("pages.userprofilepage.mostRelevant")}
                        </strong>
                        <Icon name="chevronDown" />
                    </span>
                </div>
            </div>

            <article className="profile-review-card">
                <header>
                    <span className="profile-review-initial">
                        {sample.name.slice(0, 1).toUpperCase()}
                    </span>
                    <div>
                        <strong>{sample.name}</strong>
                        {sample.badge ? (
                            <em>
                                <Icon name="reply" />
                                {sample.badge}
                            </em>
                        ) : null}
                        <p>{sample.country}</p>
                    </div>
                </header>
                <div className="profile-review-body">
                    <div>
                        <ProfileRating rating={sample.rating} reviews={0} />
                        <span>{sample.date}</span>
                    </div>
                    <p>
                        {sample.text}{" "}
                        <a href="#reviews">
                            {t("pages.userprofilepage.seeMore")}
                        </a>
                    </p>
                    <div className="profile-review-order-row">
                        <dl>
                            <div>
                                <dt>{sample.price}</dt>
                                <dd>{t("pages.userprofilepage.price")}</dd>
                            </div>
                            <div>
                                <dt>{sample.duration}</dt>
                                <dd>{t("pages.userprofilepage.duration")}</dd>
                            </div>
                        </dl>
                        <Link
                            to={`/gigs/${profile.services[0]?.id || "ai-website-chatbot"}`}
                        >
                            <img
                                src={
                                    sample.serviceImage ||
                                    profile.services[0]?.image
                                }
                                alt=""
                            />
                            <span>
                                {sample.serviceTitle ||
                                    profile.services[0]?.title}
                            </span>
                        </Link>
                    </div>
                </div>
                <button className="profile-seller-response" type="button">
                    <span>{profile.name.slice(0, 1)}</span>{" "}
                    {t("pages.userprofilepage.sellersResponse")}{" "}
                    <Icon name="chevronDown" />
                </button>
            </article>

            <div className="profile-review-helpful">
                <span>{t("pages.userprofilepage.helpful")}</span>
                <button type="button">
                    <Icon name="thumbsUp" />{" "}
                    {t("pages.userprofilepage.yes")}{" "}
                </button>
                <button type="button">
                    <Icon name="thumbsDown" />{" "}
                    {t("pages.userprofilepage.no")}{" "}
                </button>
            </div>

            <button className="profile-show-more-reviews" type="button">
                {" "}
                {t("pages.userprofilepage.showMoreReviews")}{" "}
            </button>
        </section>
    );
}
function ProfileTalentSection() {
    const { t } = useTranslation();
    return (
        <section className="profile-talent-section">
            <div className="container">
                <h2>{t("pages.userprofilepage.findFreelanceTalentYourWay")}</h2>
                <div className="talent-way-grid">
                    <ProfileTalentCard
                        action="Post a brief"
                        copy="Generate a brief with AI to receive a curated shortlist of freelancer offers."
                        icon="document"
                        title={t("pages.userprofilepage.postAProjectBrief")}
                    />
                    <ProfileTalentCard
                        action="Get started"
                        copy="Save the endless search - we'll source, interview, and vet freelancers for you."
                        icon="user"
                        meta="Only $89"
                        title={t(
                            "pages.userprofilepage.letUsFindYourFreelancer",
                        )}
                    />
                    <ProfileTalentCard
                        action="Book free consultation"
                        copy="Big project? No problem. We'll build a freelance team and fully execute your project."
                        icon="verifiedUser"
                        meta="Custom pricing"
                        title={t("pages.userprofilepage.getATeamBuiltForYou")}
                    />
                </div>
            </div>
        </section>
    );
}
function ProfileTalentCard({ action, copy, icon, meta = "", title }) {
    return (
        <article className="talent-way-card">
            <Icon name={icon} />
            <h3>{title}</h3>
            <p>{copy}</p>
            <div>
                {meta ? <strong>{meta}</strong> : <span></span>}
                <Link to="/search/gigs?source=profile-talent-way">
                    {action}
                </Link>
            </div>
        </article>
    );
}
function ProfileRating({ rating, reviews }) {
    return (
        <span className="profile-rating-line">
            {Array.from(
                {
                    length: 5,
                },
                (_, index) => (
                    <Icon name="star" key={index} />
                ),
            )}
            <strong>{rating.toFixed(1)}</strong>
            {reviews ? <a href="#reviews">({reviews})</a> : null}
        </span>
    );
}

function ProfileContactPopup({ profile, onClose }) {
    const { t } = useTranslation();
    const launchConversation = useConversationLauncher();
    const [messageDraft, setMessageDraft] = useState("");
    const [sendStatus, setSendStatus] = useState("");
    const [isSending, setIsSending] = useState(false);
    const minimumMessageLength = 40;
    const maxMessageLength = 2500;
    const messageLength = messageDraft.trim().length;
    const canSend = messageLength >= minimumMessageLength;
    const promptStarters = [
        `Hey ${profile.name}, can you help me with my project?`,
        "Can you provide your hourly rates for this work?",
        "Do you think you can deliver an order by this week?",
    ];

    const sendMessage = async () => {
        if (!canSend || isSending) {
            return;
        }

        setIsSending(true);
        setSendStatus("Opening conversation...");

        try {
            await launchConversation({
                targetUserId: profile.userId,
                targetName: profile.name,
                targetSlug: profile.slug,
                contextType: "profile",
                contextId: profile.slug,
                message: messageDraft.trim(),
            });
            onClose();
        } catch (error) {
            setSendStatus(
                error.message || "This profile is not available for messaging.",
            );
            setIsSending(false);
        }
    };

    return (
        <div className="profile-dialog-layer" role="presentation">
            <button
                className="profile-dialog-backdrop"
                type="button"
                aria-label="Close contact popup"
                onClick={onClose}
            ></button>
            <section
                className="profile-contact-popup"
                role="dialog"
                aria-modal="true"
                aria-label={`Message ${profile.name}`}
            >
                <div className="profile-contact-timebar">
                    <span aria-hidden="true"></span>
                    It's {profile.localTime} for {profile.name}. It might take
                    some time to get a response
                </div>
                <header>
                    <img src={profile.avatar} alt="" />
                    <span
                        className="profile-online-dot"
                        aria-hidden="true"
                    ></span>
                    <div>
                        <strong>
                            {t("pages.userprofilepage.message")} {profile.name}
                        </strong>
                        <span>
                            Away <i aria-hidden="true">.</i> Avg. response time:{" "}
                            <b>{profile.responseTime}</b>
                        </span>
                    </div>
                    <button
                        type="button"
                        aria-label="Close contact popup"
                        onClick={onClose}
                    >
                        <Icon name="close" />
                    </button>
                </header>

                <div className="profile-contact-popup-body">
                    <label className="sr-only" htmlFor="profileContactMessage">
                        Message
                    </label>
                    <textarea
                        id="profileContactMessage"
                        maxLength={maxMessageLength}
                        placeholder={`Ask ${profile.name} a question or share your project details (requirements, timeline, budget, etc.)`}
                        value={messageDraft}
                        onChange={(event) => {
                            setMessageDraft(event.target.value);
                            setSendStatus("");
                        }}
                    />
                    <button
                        className="profile-message-ai"
                        type="button"
                        aria-label="Improve message"
                        onClick={() =>
                            setMessageDraft((current) =>
                                current.trim().length
                                    ? current
                                    : `Hi ${profile.name}, I would like to discuss a project and understand your availability, timeline, and pricing.`,
                            )
                        }
                    >
                        <Icon name="spark" />
                    </button>
                    <span
                        className="profile-message-secure"
                        aria-label="Secure message"
                    >
                        <Icon name="archive" />
                    </span>
                </div>

                <div className="profile-message-prompts">
                    {promptStarters.map((prompt) => (
                        <button
                            type="button"
                            key={prompt}
                            onClick={() => {
                                setMessageDraft(prompt);
                                setSendStatus("");
                            }}
                        >
                            {prompt}
                        </button>
                    ))}
                </div>

                <div className="profile-message-count-row">
                    <span>Use at least {minimumMessageLength} characters</span>
                    <strong>
                        {messageLength}/{maxMessageLength}
                    </strong>
                </div>

                <footer>
                    <div>
                        <button type="button" aria-label="Add emoji">
                            <Icon name="smile" />
                        </button>
                        <button type="button" aria-label="Attach file">
                            <Icon name="paperclip" />
                        </button>
                    </div>
                    <button
                        className="profile-message-send"
                        type="button"
                        disabled={!canSend || isSending}
                        onClick={sendMessage}
                    >
                        <Icon name="send" /> Send message
                    </button>
                </footer>

                {sendStatus ? (
                    <p className="profile-message-status">{sendStatus}</p>
                ) : null}
            </section>
        </div>
    );
}

function ProfileAboutSheet({
    isClosing = false,
    onClose,
    onClosed,
    onContact,
    profile,
}) {
    const languageLevels = profile.languages.map((language, index) => ({
        language,
        level:
            index === 0
                ? "Basic"
                : index === 1
                  ? "Conversational"
                  : "Conversational",
    }));
    const education = profile.education || {
        school: "The Islamia University Bahawalpur",
        degree: "M.A. Degree. Artificial Intelligence",
        year: "Graduated 2021",
    };
    const certifications = profile.certifications || [
        {
            name: "IDEOVERSITY Web Development",
            year: "Graduated 2022",
        },
        {
            name: "IDEOVERSITY Python",
            year: "Graduated 2020",
        },
    ];

    useEffect(() => {
        if (!isClosing) {
            return undefined;
        }

        const closeTimer = window.setTimeout(onClosed, 320);

        return () => window.clearTimeout(closeTimer);
    }, [isClosing, onClosed]);

    return (
        <div
            className={`profile-about-sheet-layer${isClosing ? " is-closing" : " is-opening"}`}
            role="presentation"
        >
            <button
                className="profile-dialog-backdrop"
                type="button"
                aria-label="Close more about me"
                onClick={onClose}
            ></button>
            <section
                className="profile-about-sheet"
                role="dialog"
                aria-modal="true"
                aria-label={`More about ${profile.name}`}
                onAnimationEnd={(event) => {
                    if (isClosing && event.target === event.currentTarget) {
                        onClosed();
                    }
                }}
            >
                <header className="profile-about-sheet-head">
                    <div className="profile-about-sheet-person">
                        <img src={profile.avatar} alt="" />
                        <div>
                            <h2>
                                {profile.name} <span>{profile.handle}</span>
                            </h2>
                            <ProfileRating
                                rating={profile.rating}
                                reviews={profile.reviews}
                            />
                            <strong>{profile.title}</strong>
                            <p>
                                <span>
                                    <Icon name="location" /> {profile.location}
                                </span>
                                <span>
                                    <Icon name="message" />{" "}
                                    {profile.languages.join(", ")}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div className="profile-about-sheet-actions">
                        <button type="button" onClick={onContact}>
                            <Icon name="send" /> Contact me
                        </button>
                        <button
                            type="button"
                            aria-label={`Save ${profile.name}`}
                        >
                            <Icon name="heart" />
                        </button>
                        <button
                            type="button"
                            aria-label="Close more about me"
                            onClick={onClose}
                        >
                            <Icon name="close" />
                        </button>
                    </div>
                </header>

                <div className="profile-about-sheet-content">
                    <div className="profile-about-sheet-main">
                        <section>
                            <h3>About me</h3>
                            <p>{profile.about}</p>
                        </section>

                        <section>
                            <h3>Skills</h3>
                            <div className="profile-about-skill-list">
                                {profile.skills.map((skill) => (
                                    <span key={skill}>{skill}</span>
                                ))}
                            </div>
                        </section>

                        <section className="profile-learn-row">
                            <h3>Successfully completed online</h3>
                            <article>
                                <span>
                                    <Icon name="verifiedUser" />
                                </span>
                                <div>
                                    <strong>
                                        Online Freelancing Essentials: be a
                                        successful Fiverr seller
                                    </strong>
                                    <p>Oct 2023</p>
                                </div>
                                <b>fiverr learn.</b>
                            </article>
                        </section>

                        <section>
                            <h3>Education</h3>
                            <article className="profile-about-timeline-item">
                                <span>
                                    <Icon name="graduation" />
                                </span>
                                <div>
                                    <strong>{education.school}</strong>
                                    <p>{education.degree}</p>
                                    <p>{education.year}</p>
                                </div>
                            </article>
                        </section>

                        <section>
                            <h3>Certifications</h3>
                            {certifications.map((item) => (
                                <article
                                    className="profile-about-timeline-item"
                                    key={`${item.name}-${item.year}`}
                                >
                                    <span>
                                        <Icon name="star" />
                                    </span>
                                    <div>
                                        <strong>{item.name}</strong>
                                        <p>{item.year}</p>
                                    </div>
                                </article>
                            ))}
                        </section>
                    </div>

                    <aside className="profile-about-sheet-card">
                        <strong>
                            <Icon name="user" /> On Fiverr since Aug 2020
                        </strong>
                        <hr />
                        <h3>I speak</h3>
                        <dl>
                            {languageLevels.map((item) => (
                                <div key={item.language}>
                                    <dt>{item.language}</dt>
                                    <dd>{item.level}</dd>
                                </div>
                            ))}
                        </dl>
                    </aside>
                </div>
            </section>
        </div>
    );
}

function ProfileMessageBubble({ profile }) {
    const { t } = useTranslation();
    const launchConversation = useConversationLauncher();
    const [isComposerOpen, setIsComposerOpen] = useState(false);
    const [messageDraft, setMessageDraft] = useState("Hi,\nThis is my message.");
    const [sendStatus, setSendStatus] = useState("");
    const [isSending, setIsSending] = useState(false);
    const minimumMessageLength = 40;
    const maxMessageLength = 2500;
    const messageLength = messageDraft.trim().length;
    const canSend = messageLength >= minimumMessageLength;
    const promptStarters = [
        `Hey ${profile.name}, can you help me with my project?`,
        "Would it be possible to get a custom offer for this?",
        "Do you think you can deliver an order by this week?",
    ];

    useEffect(() => {
        if (!isComposerOpen) {
            return undefined;
        }

        const closeOnEscape = (event) => {
            if (event.key === "Escape") {
                setIsComposerOpen(false);
            }
        };

        window.addEventListener("keydown", closeOnEscape);

        return () => window.removeEventListener("keydown", closeOnEscape);
    }, [isComposerOpen]);

    const openComposer = () => {
        setIsComposerOpen(true);
        setSendStatus("");
    };

    const sendMessage = async () => {
        if (!canSend || isSending) {
            return;
        }

        setIsSending(true);
        setSendStatus("Opening conversation...");

        try {
            await launchConversation({
                targetUserId: profile.userId,
                targetName: profile.name,
                targetSlug: profile.slug,
                contextType: "profile",
                contextId: profile.slug,
                message: messageDraft.trim(),
            });
        } catch (error) {
            setSendStatus(
                error.message || "This profile is not available for messaging.",
            );
            setIsSending(false);
        }
    };

    if (isComposerOpen) {
        return (
            <aside
                className="profile-message-composer"
                aria-label={`Send a message to ${profile.name}`}
            >
                <header>
                    <img src={profile.avatar} alt="" />
                    <span
                        className="profile-online-dot"
                        aria-hidden="true"
                    ></span>
                    <div>
                        <strong>
                            {t("pages.userprofilepage.message")} {profile.name}
                        </strong>
                        <span>
                            Online <i aria-hidden="true">.</i> Avg. response
                            time: <b>{profile.responseTime}</b>
                        </span>
                    </div>
                    <button
                        type="button"
                        aria-label="Close message box"
                        onClick={() => setIsComposerOpen(false)}
                    >
                        <Icon name="close" />
                    </button>
                </header>

                <div className="profile-message-composer-body">
                    <label className="sr-only" htmlFor="profileMessageDraft">
                        Message
                    </label>
                    <textarea
                        id="profileMessageDraft"
                        maxLength={maxMessageLength}
                        value={messageDraft}
                        onChange={(event) => {
                            setMessageDraft(event.target.value);
                            setSendStatus("");
                        }}
                    />
                    <span
                        className="profile-message-secure"
                        aria-label="Secure message"
                    >
                        <Icon name="archive" />
                    </span>
                </div>

                <div className="profile-message-prompts">
                    {promptStarters.map((prompt) => (
                        <button
                            type="button"
                            key={prompt}
                            onClick={() => {
                                setMessageDraft(prompt);
                                setSendStatus("");
                            }}
                        >
                            {prompt}
                        </button>
                    ))}
                </div>

                <div className="profile-message-count-row">
                    <span>Use at least {minimumMessageLength} characters</span>
                    <strong>
                        {messageLength}/{maxMessageLength}
                    </strong>
                </div>

                <footer>
                    <div>
                        <button type="button" aria-label="Add emoji">
                            <Icon name="smile" />
                        </button>
                        <button type="button" aria-label="Attach file">
                            <Icon name="paperclip" />
                        </button>
                    </div>
                    <button
                        className="profile-message-send"
                        type="button"
                        disabled={!canSend || isSending}
                        onClick={sendMessage}
                    >
                        <Icon name="send" /> Send message
                    </button>
                </footer>

                {sendStatus ? (
                    <p className="profile-message-status">{sendStatus}</p>
                ) : null}
            </aside>
        );
    }

    return (
        <button
            className="profile-message-bubble"
            aria-label={`Message ${profile.name}`}
            type="button"
            onClick={openComposer}
        >
            <img src={profile.avatar} alt="" />
            <span className="profile-online-dot" aria-hidden="true"></span>
            <div>
                <strong>
                    {t("pages.userprofilepage.message")} {profile.name}
                </strong>
                <span>
                    {t("pages.userprofilepage.onlineAvgResponseTime")}{" "}
                    {profile.responseTime}
                </span>
            </div>
        </button>
    );
}
export default UserProfilePage;
