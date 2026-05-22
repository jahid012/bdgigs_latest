import { useEffect, useMemo, useRef, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import {
    createDetailFromListingGig,
} from "../data/gigDetailsData.js";
import {
    profilePathForSeller,
    slugifySellerName,
} from "../data/userProfileData.js";
import { useDismissOnInteractOutside } from "../hooks/useDismissOnInteractOutside.js";
import { useConversationLauncher } from "../hooks/useConversationLauncher.js";
import { Icon } from "../components/common/Icons.jsx";
import Footer from "../components/layout/Footer.jsx";
import Header from "../components/layout/Header.jsx";
import { useTranslation } from "react-i18next";
import { apiRequest } from "../api/apiClient.js";
import { useMarketplaceStore } from "../stores/useMarketplaceStore.js";
import { useSessionStore } from "../stores/useSessionStore.js";
const packageFeatureRows = [
    "Functional Web App",
    "Desktop Application",
    "Integration of an AI model to existing app",
    "AI Model Fine-tuning",
    "Chatbot integration",
    "Source Code",
];
function GigDetailsPage({ onNavigate }) {
    const { t } = useTranslation();
    const { gigId } = useParams();
    const apiGig = useMarketplaceStore((state) => state.gigsById[gigId]);
    const fetchGig = useMarketplaceStore((state) => state.fetchGig);
    const fetchGigs = useMarketplaceStore((state) => state.fetchGigs);
    const toggleSavedService = useMarketplaceStore(
        (state) => state.toggleSavedService,
    );
    const currentUser = useSessionStore((state) => state.currentUser);
    const launchConversation = useConversationLauncher();
    const detail = useMemo(
        () => (apiGig ? createDetailFromListingGig(apiGig) : null),
        [apiGig],
    );
    const [activeImage, setActiveImage] = useState(0);
    const [activePackage, setActivePackage] = useState("basic");
    const [openFaq, setOpenFaq] = useState(null);
    const [isReportOpen, setIsReportOpen] = useState(false);
    const [isCheckoutOpen, setIsCheckoutOpen] = useState(false);
    const [contactStatus, setContactStatus] = useState("");
    const [gigLoadError, setGigLoadError] = useState("");
    const reportRef = useRef(null);
    const selectedPackage =
        detail?.packages.find((pkg) => pkg.id === activePackage) ||
        detail?.packages[0];
    useDismissOnInteractOutside(reportRef, isReportOpen, () =>
        setIsReportOpen(false),
    );

    useEffect(() => {
        let active = true;

        setGigLoadError("");
        fetchGigs();
        fetchGig(gigId).then((gig) => {
            if (active && !gig) {
                setGigLoadError("This gig is not available.");
            }
        });

        return () => {
            active = false;
        };
    }, [fetchGig, fetchGigs, gigId]);

    useEffect(() => {
        const firstPackageId = detail?.packages[0]?.id;

        if (
            firstPackageId &&
            !detail.packages.some((pkg) => pkg.id === activePackage)
        ) {
            setActivePackage(firstPackageId);
        }
    }, [activePackage, detail]);

    const changeImage = (direction) => {
        setActiveImage(
            (current) =>
                (current + direction + detail.gallery.length) %
                detail.gallery.length,
        );
    };
    const startGigConversation = async (message = "") => {
        setContactStatus("Opening conversation...");

        try {
            const conversation = await launchConversation({
                targetUserId: apiGig?.sellerUserId || detail.seller.userId,
                targetName: detail.seller.name,
                targetSlug:
                    detail.seller.username || slugifySellerName(detail.seller.name),
                contextType: "gig",
                contextId: apiGig?.id || gigId,
                message,
            });

            return { conversation };
        } catch (error) {
            const errorMessage =
                error.message || "This seller is not available for messaging.";
            setContactStatus(errorMessage);

            return { error: errorMessage };
        }
    };
    const toggleSavedGig = async () => {
        if (!currentUser?.authenticated) {
            onNavigate(
                `/?auth=login&redirect=${encodeURIComponent(`/gigs/${gigId}`)}`,
            );
            return;
        }

        await toggleSavedService(apiGig);
    };
    const openManualCheckout = () => {
        if (!currentUser?.authenticated) {
            onNavigate(
                `/?auth=login&redirect=${encodeURIComponent(`/gigs/${gigId}`)}`,
            );
            return;
        }

        setIsCheckoutOpen(true);
    };

    if (!detail || !selectedPackage) {
        return (
            <GigDetailStatus
                error={gigLoadError}
                onNavigate={onNavigate}
            />
        );
    }

    return (
        <div className="gig-detail-page">
            <Header
                enableMarketplaceHeader={false}
                forceSearch
                onNavigate={onNavigate}
            />

            <main className="gig-detail-main">
                <div className="container">
                    <div className="gig-detail-layout">
                        <article className="gig-detail-content">
                            <GigHero
                                activeImage={activeImage}
                                detail={detail}
                                onChangeImage={changeImage}
                                onSelectImage={setActiveImage}
                            />
                            <AboutGig detail={detail} />
                            <SellerProfile
                                contactStatus={contactStatus}
                                onContact={() => startGigConversation()}
                                seller={detail.seller}
                            />
                            <PortfolioSection portfolio={detail.portfolio} />
                            <ComparePackages
                                packages={detail.packages}
                                onSelect={setActivePackage}
                            />
                            <RecommendedSection currentId={detail.id} />
                            <FAQSection
                                detail={detail}
                                onToggle={setOpenFaq}
                                openFaq={openFaq}
                            />
                            <SourcingCTA />
                            <ReviewsSection detail={detail} />
                        </article>

                        <aside
                            className="gig-detail-sidebar"
                            aria-label={t("pages.gigdetailspage.gigPackages")}
                        >
                            <TopActions
                                isReportOpen={isReportOpen}
                                isSaved={Boolean(apiGig?.saved)}
                                onSave={toggleSavedGig}
                                onToggleReport={() =>
                                    setIsReportOpen((open) => !open)
                                }
                                reportRef={reportRef}
                                reviewCount={detail.reviews.count}
                            />
                            <PackageCard
                                activePackage={activePackage}
                                onContact={() =>
                                    startGigConversation(
                                        `Hi ${detail.seller.name}, I am interested in ${detail.title}.`,
                                    )
                                }
                                onContinue={openManualCheckout}
                                onPackageChange={setActivePackage}
                                packageData={selectedPackage}
                                packages={detail.packages}
                            />
                        </aside>
                    </div>
                </div>

                <GigDetailBottomSections
                    detail={detail}
                    onNavigate={onNavigate}
                />
            </main>

            <MessageBubble
                onSend={startGigConversation}
                seller={detail.seller}
            />
            {isCheckoutOpen ? (
                <ManualCheckoutDialog
                    gig={apiGig}
                    onClose={() => setIsCheckoutOpen(false)}
                    onNavigate={onNavigate}
                    packageData={selectedPackage}
                />
            ) : null}
            <Footer />
        </div>
    );
}
function GigHero({ activeImage, detail, onChangeImage, onSelectImage }) {
    const { t } = useTranslation();
    return (
        <section className="gig-detail-hero" aria-labelledby="gigTitle">
            <nav
                className="gig-detail-breadcrumb"
                aria-label={t("pages.gigdetailspage.breadcrumb")}
            >
                <Link to="/" aria-label={t("pages.gigdetailspage.home")}>
                    <Icon name="home" />
                </Link>
                {detail.breadcrumbs.map((crumb) => (
                    <span key={crumb}>
                        <span aria-hidden="true">/</span>
                        <Link
                            to={`/search/gigs?query=${encodeURIComponent(crumb)}&source=detail-breadcrumb`}
                        >
                            {crumb}
                        </Link>
                    </span>
                ))}
            </nav>

            <h1 id="gigTitle">{detail.title}</h1>
            <SellerMini seller={detail.seller} />

            <div className="gig-gallery">
                <button
                    type="button"
                    aria-label={t("pages.gigdetailspage.previousImage")}
                    onClick={() => onChangeImage(-1)}
                >
                    <Icon name="arrowRight" />
                </button>
                <img
                    src={detail.gallery[activeImage]}
                    alt={`${detail.title} preview ${activeImage + 1}`}
                />
                <button
                    type="button"
                    aria-label={t("pages.gigdetailspage.nextImage")}
                    onClick={() => onChangeImage(1)}
                >
                    <Icon name="arrowRight" />
                </button>
            </div>

            <div
                className="gig-gallery-thumbs"
                aria-label={t("pages.gigdetailspage.gigPreviewThumbnails")}
            >
                {detail.gallery.map((image, index) => (
                    <button
                        className={activeImage === index ? "is-active" : ""}
                        type="button"
                        key={image}
                        onClick={() => onSelectImage(index)}
                    >
                        <img src={image} alt="" />
                    </button>
                ))}
            </div>
        </section>
    );
}
function GigDetailStatus({ error, onNavigate }) {
    return (
        <div className="gig-detail-page">
            <Header
                enableMarketplaceHeader={false}
                forceSearch
                onNavigate={onNavigate}
            />
            <main className="gig-detail-main">
                <div className="container profile-data-empty">
                    <h1>{error || "Loading gig..."}</h1>
                    <p>
                        {error
                            ? "Browse the live gig catalog to open a service that is still published."
                            : "Loading the live gig record and package details."}
                    </p>
                </div>
            </main>
            <Footer />
        </div>
    );
}

function sellerProfilePath(seller) {
    return (
        seller.profilePath ||
        profilePathForSeller(seller.name, seller.username)
    );
}

function SellerMini({ seller }) {
    return (
        <div className="gig-seller-mini">
            <Link
                className="gig-seller-mini-avatar"
                to={sellerProfilePath(seller)}
                aria-label={`View ${seller.name} profile`}
            >
                <img src={seller.avatar} alt="" />
            </Link>
            <div>
                <Link
                    className="gig-seller-mini-name"
                    to={sellerProfilePath(seller)}
                >
                    <strong>{seller.name}</strong>
                </Link>
                <span>{seller.level}</span>
                <RatingLine rating={seller.rating} reviews={seller.reviews} />
            </div>
        </div>
    );
}
function RatingLine({ rating, reviews, reviewsLink = true }) {
    const { t } = useTranslation();
    const reviewLabel = (
        <>
            ({reviews} {t("pages.gigdetailspage.reviews")})
        </>
    );

    return (
        <span className="detail-rating-line">
            {Array.from(
                {
                    length: 5,
                },
                (_, index) => (
                    <Icon name="star" key={index} />
                ),
            )}
            <strong>{rating.toFixed(1)}</strong>
            {reviews && reviewsLink ? <Link to="#reviews">{reviewLabel}</Link> : null}
            {reviews && !reviewsLink ? (
                <span className="review-count">{reviewLabel}</span>
            ) : null}
        </span>
    );
}
function TopActions({
    isReportOpen,
    isSaved,
    onSave,
    onToggleReport,
    reportRef,
    reviewCount,
}) {
    const { t } = useTranslation();
    return (
        <div className="gig-detail-actions" ref={reportRef}>
            <button
                type="button"
                aria-label={t("pages.gigdetailspage.openMenu")}
            >
                <Icon name="menu" />
            </button>
            <button
                className={isSaved ? "is-favorite" : undefined}
                type="button"
                aria-label={t("pages.gigdetailspage.saveGig")}
                aria-pressed={isSaved}
                onClick={onSave}
            >
                <Icon name="heart" />
            </button>
            <span>{reviewCount}</span>
            <button
                type="button"
                aria-label={t("pages.gigdetailspage.shareGig")}
            >
                <Icon name="share" />
            </button>
            <button
                type="button"
                aria-label={t("pages.gigdetailspage.moreOptions")}
                aria-expanded={isReportOpen}
                onClick={onToggleReport}
            >
                <Icon name="moreHorizontal" />
            </button>
            {isReportOpen ? (
                <div className="report-popover">
                    <button type="button">
                        <Icon name="flag" />{" "}
                        {t("pages.gigdetailspage.reportAnIssue")}{" "}
                    </button>
                </div>
            ) : null}
        </div>
    );
}
function PackageCard({
    activePackage,
    onContact,
    onContinue,
    onPackageChange,
    packageData,
    packages,
}) {
    const { t } = useTranslation();
    return (
        <section className="package-card">
            <div
                className="package-tabs"
                role="tablist"
                aria-label={t("pages.gigdetailspage.packageTiers")}
            >
                {packages.map((pkg) => (
                    <button
                        className={activePackage === pkg.id ? "is-active" : ""}
                        type="button"
                        role="tab"
                        aria-selected={activePackage === pkg.id}
                        key={pkg.id}
                        onClick={() => onPackageChange(pkg.id)}
                    >
                        {pkg.name}
                    </button>
                ))}
            </div>

            <div className="package-card-body">
                <h2>{packageData.title}</h2>
                <strong className="package-price">
                    ${packageData.price.toLocaleString()}
                </strong>
                <p>{packageData.description}</p>
                <div className="package-meta-row">
                    <span>
                        <Icon name="packageCheck" />
                        {packageData.delivery}
                    </span>
                    <span>
                        <Icon name="reply" />
                        {packageData.revisions}
                    </span>
                </div>

                <ul className="package-feature-list">
                    {packageFeatureRows.map((feature) => (
                        <li
                            className={
                                packageData.features[feature] ? "" : "is-muted"
                            }
                            key={feature}
                        >
                            <span aria-hidden="true"></span>
                            {feature}
                        </li>
                    ))}
                </ul>

                <button
                    className="package-continue-button"
                    type="button"
                    onClick={onContinue}
                >
                    {" "}
                    {t("pages.gigdetailspage.continue")}{" "}
                    <Icon name="arrowRight" />
                </button>
                <button
                    className="package-contact-button"
                    type="button"
                    onClick={onContact}
                >
                    {" "}
                    {t("pages.gigdetailspage.contactMe")}{" "}
                    <Icon name="chevronDown" />
                </button>
            </div>

            <div className="hourly-offer-card">
                <div>
                    <img src="/assets/img/gig_images/18.png" alt="" />
                    <strong>
                        {t("pages.gigdetailspage.needFlexibilityWhenHiring")}
                    </strong>
                </div>
                <p>{t("pages.gigdetailspage.hireByTheHourIdealForLongTerm")}</p>
                <div>
                    <strong>{t("pages.gigdetailspage.15Hour")}</strong>
                    <button type="button">
                        {t("pages.gigdetailspage.requestHourlyOffer")}
                    </button>
                </div>
            </div>
        </section>
    );
}

function ManualCheckoutDialog({ gig, onClose, onNavigate, packageData }) {
    const [methods, setMethods] = useState([]);
    const [methodId, setMethodId] = useState("");
    const [reference, setReference] = useState("");
    const [proofReference, setProofReference] = useState("");
    const [note, setNote] = useState("");
    const [status, setStatus] = useState("loading");
    const [error, setError] = useState("");

    useEffect(() => {
        let active = true;

        apiRequest("/api/manual-payment-methods")
            .then((nextMethods) => {
                if (!active) return;

                setMethods(nextMethods);
                setMethodId(String(nextMethods[0]?.id || ""));
                setStatus("ready");
            })
            .catch((nextError) => {
                if (!active) return;

                setError(
                    nextError.message || "Unable to load payment methods.",
                );
                setStatus("error");
            });

        return () => {
            active = false;
        };
    }, []);

    const selectedMethod = methods.find(
        (method) => String(method.id) === methodId,
    );
    const submitCheckout = async (event) => {
        event.preventDefault();
        setStatus("submitting");
        setError("");

        try {
            const order = await apiRequest(
                `/api/gigs/${gig.id}/manual-checkout`,
                {
                    body: {
                        packageId: packageData.id,
                        manualPaymentMethodId: Number(methodId),
                        reference,
                        proofReference: proofReference || undefined,
                        note: note || undefined,
                    },
                },
            );

            onClose();
            onNavigate(`/dashboard/orders/${order.orderNumber}`);
        } catch (nextError) {
            setError(nextError.message || "Unable to submit checkout.");
            setStatus("ready");
        }
    };

    return (
        <div className="manual-checkout-backdrop" role="presentation">
            <section
                className="manual-checkout-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="manualCheckoutTitle"
            >
                <button
                    className="manual-checkout-close"
                    type="button"
                    aria-label="Close checkout"
                    onClick={onClose}
                >
                    <Icon name="close" />
                </button>
                <div>
                    <span>Manual payment checkout</span>
                    <h2 id="manualCheckoutTitle">{packageData.title}</h2>
                    <p>
                        ${packageData.price.toLocaleString()} for{" "}
                        {gig.title}
                    </p>
                </div>
                <form onSubmit={submitCheckout}>
                    <label>
                        <span>Payment method</span>
                        <select
                            value={methodId}
                            onChange={(event) =>
                                setMethodId(event.target.value)
                            }
                            disabled={status !== "ready"}
                            required
                        >
                            {methods.map((method) => (
                                <option value={method.id} key={method.id}>
                                    {method.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    {selectedMethod ? (
                        <div className="manual-method-note">
                            <strong>
                                {selectedMethod.accountName}{" "}
                                {selectedMethod.accountNumber}
                            </strong>
                            <p>{selectedMethod.instructions}</p>
                        </div>
                    ) : null}
                    <label>
                        <span>Transaction reference</span>
                        <input
                            value={reference}
                            onChange={(event) =>
                                setReference(event.target.value)
                            }
                            placeholder="Bank or wallet transaction ID"
                            required
                        />
                    </label>
                    <label>
                        <span>Proof reference</span>
                        <input
                            value={proofReference}
                            onChange={(event) =>
                                setProofReference(event.target.value)
                            }
                            placeholder="Optional receipt link or note"
                        />
                    </label>
                    <label>
                        <span>Buyer note</span>
                        <textarea
                            value={note}
                            onChange={(event) => setNote(event.target.value)}
                            placeholder="Optional note for the payment reviewer"
                        />
                    </label>
                    {error ? <p className="manual-checkout-error">{error}</p> : null}
                    {status === "ready" && methods.length === 0 ? (
                        <p className="manual-checkout-error">
                            No active manual payment methods are available.
                        </p>
                    ) : null}
                    <button
                        className="btn btn-primary"
                        type="submit"
                        disabled={
                            status !== "ready" ||
                            methods.length === 0 ||
                            !reference.trim()
                        }
                    >
                        Submit order for payment review
                    </button>
                </form>
            </section>
        </div>
    );
}
function AboutGig({ detail }) {
    const { t } = useTranslation();
    return (
        <section className="detail-section about-gig-section">
            <h2>{t("pages.gigdetailspage.aboutThisGig")}</h2>
            <div className="about-gig-copy">
                <strong>{detail.about.heading}</strong>
                {detail.about.paragraphs.map((paragraph) => (
                    <p key={paragraph}>{paragraph}</p>
                ))}
                <ul>
                    {detail.about.bullets.map((item) => (
                        <li key={item.label}>
                            <strong>{item.label}:</strong> {item.text}
                        </li>
                    ))}
                </ul>
                <strong>{t("pages.gigdetailspage.whyWorkWithUs")}</strong>
                <ul>
                    {detail.about.why.map((item) => (
                        <li key={item}>{item}</li>
                    ))}
                </ul>
                <p>
                    <strong>{detail.about.closing}</strong>
                </p>
            </div>

            <div className="gig-spec-grid">
                {detail.specs.map((spec) => (
                    <div key={spec.label}>
                        <span>{spec.label}</span>
                        <strong>{spec.value}</strong>
                    </div>
                ))}
            </div>
        </section>
    );
}
function SellerProfile({ contactStatus = "", onContact, seller }) {
    const { t } = useTranslation();
    return (
        <section className="detail-section seller-profile-section">
            <h2>
                {" "}
                {t("pages.gigdetailspage.getToKnow")}{" "}
                <Link to={sellerProfilePath(seller)}>{seller.name}</Link>
            </h2>
            <div className="seller-profile-header">
                <Link
                    to={sellerProfilePath(seller)}
                    aria-label={`View ${seller.name} profile`}
                >
                    <img src={seller.avatar} alt="" />
                </Link>
                <div>
                    <Link to={sellerProfilePath(seller)}>
                        <strong>{seller.name}</strong>
                    </Link>
                    <p>{seller.tagline}</p>
                    <RatingLine
                        rating={seller.rating}
                        reviews={seller.reviews}
                    />
                </div>
            </div>
            <div className="seller-profile-actions">
                <button type="button" onClick={onContact}>
                    {t("pages.gigdetailspage.contactMe")}
                </button>
                <button type="button">
                    <Icon name="video" />{" "}
                    {t("pages.gigdetailspage.bookAConsultation")}{" "}
                </button>
            </div>
            <div className="seller-profile-card">
                {contactStatus ? (
                    <p className="profile-message-status">{contactStatus}</p>
                ) : null}
                <dl>
                    <div>
                        <dt>{t("pages.gigdetailspage.from")}</dt>
                        <dd>{seller.from}</dd>
                    </div>
                    <div>
                        <dt>{t("pages.gigdetailspage.memberSince")}</dt>
                        <dd>{seller.memberSince}</dd>
                    </div>
                    <div>
                        <dt>{t("pages.gigdetailspage.avgResponseTime")}</dt>
                        <dd>{seller.responseTime}</dd>
                    </div>
                    <div>
                        <dt>{t("pages.gigdetailspage.lastDelivery")}</dt>
                        <dd>{seller.lastDelivery}</dd>
                    </div>
                    <div>
                        <dt>{t("pages.gigdetailspage.languages")}</dt>
                        <dd>{seller.languages}</dd>
                    </div>
                </dl>
                <p>{seller.bio}</p>
            </div>
        </section>
    );
}
function PortfolioSection({ portfolio }) {
    const { t } = useTranslation();
    return (
        <section className="detail-section portfolio-section">
            <h2>{t("pages.gigdetailspage.myPortfolio")}</h2>
            <article className="portfolio-card">
                <img src={portfolio.image} alt="" />
                <div>
                    <span>{portfolio.date}</span>
                    <h3>{portfolio.title}</h3>
                    <p>{portfolio.description}</p>
                    <div className="portfolio-tags">
                        {portfolio.tags.map((tag) => (
                            <span key={tag}>{tag}</span>
                        ))}
                    </div>
                    <dl>
                        <div>
                            <dt>{t("pages.gigdetailspage.projectCost")}</dt>
                            <dd>{portfolio.cost}</dd>
                        </div>
                        <div>
                            <dt>{t("pages.gigdetailspage.projectDuration")}</dt>
                            <dd>{portfolio.duration}</dd>
                        </div>
                    </dl>
                </div>
            </article>
            <div className="portfolio-thumbs">
                {portfolio.thumbnails.map((image, index) => (
                    <button
                        className={index === 0 ? "is-active" : ""}
                        type="button"
                        key={`${image}-${index}`}
                    >
                        <img src={image} alt="" />
                    </button>
                ))}
                <button type="button">
                    {t("pages.gigdetailspage.2Projects")}
                </button>
            </div>
        </section>
    );
}
function ComparePackages({ packages, onSelect }) {
    const { t } = useTranslation();
    return (
        <section className="detail-section compare-section">
            <h2>{t("pages.gigdetailspage.comparePackages")}</h2>
            <div className="compare-table-wrap">
                <table className="compare-table">
                    <thead>
                        <tr>
                            <th>{t("pages.gigdetailspage.package")}</th>
                            {packages.map((pkg) => (
                                <th key={pkg.id}>
                                    <strong>
                                        ${pkg.price.toLocaleString()}
                                    </strong>
                                    <span>{pkg.name}</span>
                                    <small>{pkg.title}</small>
                                    <p>{pkg.description}</p>
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {packageFeatureRows.map((feature) => (
                            <tr key={feature}>
                                <td>{feature}</td>
                                {packages.map((pkg) => (
                                    <td key={pkg.id}>
                                        <span
                                            className={`compare-check${pkg.features[feature] ? "" : " is-muted"}`}
                                            aria-label={
                                                pkg.features[feature]
                                                    ? "Included"
                                                    : "Not included"
                                            }
                                        ></span>
                                    </td>
                                ))}
                            </tr>
                        ))}
                        <tr>
                            <td>{t("pages.gigdetailspage.revisions")}</td>
                            {packages.map((pkg) => (
                                <td key={pkg.id}>
                                    {pkg.revisions.replace(" Revisions", "")}
                                </td>
                            ))}
                        </tr>
                        <tr>
                            <td>{t("pages.gigdetailspage.deliveryTime")}</td>
                            {packages.map((pkg) => (
                                <td key={pkg.id}>{pkg.deliveryTime}</td>
                            ))}
                        </tr>
                        <tr>
                            <td>{t("pages.gigdetailspage.total")}</td>
                            {packages.map((pkg) => (
                                <td key={pkg.id}>
                                    ${pkg.price.toLocaleString()}
                                </td>
                            ))}
                        </tr>
                        <tr>
                            <td></td>
                            {packages.map((pkg) => (
                                <td key={pkg.id}>
                                    <button
                                        type="button"
                                        onClick={() => onSelect(pkg.id)}
                                    >
                                        {" "}
                                        {t("pages.gigdetailspage.select")}{" "}
                                    </button>
                                </td>
                            ))}
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    );
}
function RecommendedSection({ currentId }) {
    const { t } = useTranslation();
    const listingGigs = useMarketplaceStore((state) => state.listingGigs);
    const recommended = listingGigs
        .filter((gig) => gig.id !== currentId)
        .slice(0, 2);
    return (
        <section className="detail-section recommended-detail-section">
            <h2>{t("pages.gigdetailspage.recommendedForYou")}</h2>
            <div className="detail-recommend-grid">
                {recommended.map((gig) => (
                    <Link
                        className="detail-recommend-card"
                        to={`/gigs/${gig.id}`}
                        key={gig.id}
                    >
                        <img src={gig.image} alt="" />
                        <div>
                            <strong>{gig.seller}</strong>
                            <span>{gig.level}</span>
                        </div>
                        <p>{gig.title}</p>
                        <RatingLine
                            rating={gig.rating}
                            reviews={gig.reviews}
                            reviewsLink={false}
                        />
                        <b>
                            {t("pages.gigdetailspage.from2")}
                            {gig.price}
                        </b>
                        {gig.consultation ? (
                            <small>
                                <Icon name="video" />{" "}
                                {t(
                                    "pages.gigdetailspage.offersVideoConsultations",
                                )}{" "}
                            </small>
                        ) : null}
                    </Link>
                ))}
            </div>
        </section>
    );
}
function FAQSection({ detail, onToggle, openFaq }) {
    const { t } = useTranslation();
    return (
        <section className="detail-section faq-section">
            <h2>{t("pages.gigdetailspage.faq")}</h2>
            {detail.faq.map((item, index) => (
                <div className="faq-item" key={item.question}>
                    <button
                        type="button"
                        aria-expanded={openFaq === index}
                        onClick={() =>
                            onToggle(openFaq === index ? null : index)
                        }
                    >
                        {item.question}
                        <Icon name="chevronDown" />
                    </button>
                    {openFaq === index ? <p>{item.answer}</p> : null}
                </div>
            ))}
        </section>
    );
}
function SourcingCTA() {
    const { t } = useTranslation();
    return (
        <section className="detail-sourcing-cta">
            <div>
                <h2>
                    {" "}
                    {t("pages.gigdetailspage.getTheRightFreelancer")}{" "}
                    <span> {t("pages.gigdetailspage.withoutTheSearch")}</span>
                </h2>
                <p>
                    {t(
                        "pages.gigdetailspage.wellHandleTheSourcingInterviewingAndVettingSo",
                    )}
                </p>
                <button type="button">
                    {" "}
                    {t("pages.gigdetailspage.sourceForMe")}{" "}
                    <Icon name="arrowRight" />
                </button>
            </div>
            <div className="sourcing-profile-stack" aria-hidden="true">
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
function ReviewsSection({ detail }) {
    const { t } = useTranslation();
    const { reviews } = detail;
    const relatedTags = detail.relatedTags || [
        "Ai chatbot",
        "Ai developer",
        "Full stack website",
        "Ai website",
        "Ai software",
    ];
    return (
        <section className="detail-section reviews-section" id="reviews">
            <h2>{t("pages.gigdetailspage.reviews2")}</h2>
            <div className="reviews-summary">
                <div>
                    <h3>
                        {reviews.count}{" "}
                        {t("pages.gigdetailspage.reviewsForThisGig")}
                    </h3>
                    {reviews.breakdown.map((row) => (
                        <div className="review-breakdown-row" key={row.label}>
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
                    <RatingLine rating={reviews.rating} reviews={0} />
                    <h3>{t("pages.gigdetailspage.ratingBreakdown")}</h3>
                    {reviews.ratings.map((row) => (
                        <div className="rating-breakdown-row" key={row.label}>
                            <span>{row.label}</span>
                            <strong>
                                <Icon name="star" />
                                {row.value.toFixed(1)}
                            </strong>
                        </div>
                    ))}
                </div>
            </div>

            <form
                className="review-search"
                role="search"
                onSubmit={(event) => event.preventDefault()}
            >
                <label className="sr-only" htmlFor="reviewSearch">
                    {" "}
                    {t("pages.gigdetailspage.searchReviews")}{" "}
                </label>
                <input
                    id="reviewSearch"
                    type="search"
                    placeholder={t("pages.gigdetailspage.searchReviews")}
                />
                <button
                    type="submit"
                    aria-label={t("pages.gigdetailspage.searchReviews")}
                >
                    <Icon name="search" />
                </button>
            </form>
            <div className="review-controls">
                <span>
                    {" "}
                    {t("pages.gigdetailspage.sortBy")}{" "}
                    <strong>{t("pages.gigdetailspage.mostRelevant")}</strong>
                    <Icon name="chevronDown" />
                </span>
                <label>
                    <input type="checkbox" />{" "}
                    {t("pages.gigdetailspage.onlyShowReviewsWithFiles10")}{" "}
                </label>
            </div>

            <article className="review-card">
                <div className="review-card-header">
                    <img
                        src="https://images.pexels.com/photos/91227/pexels-photo-91227.jpeg?auto=compress&cs=tinysrgb&w=80"
                        alt=""
                    />
                    <div>
                        <strong>{reviews.sample.name}</strong>
                        <span>{reviews.sample.country}</span>
                    </div>
                </div>
                <div className="review-card-body">
                    <div>
                        <RatingLine
                            rating={reviews.sample.rating}
                            reviews={0}
                        />
                        <span>{reviews.sample.date}</span>
                    </div>
                    <p>{reviews.sample.text}</p>
                    <dl>
                        <div>
                            <dt>{reviews.sample.price}</dt>
                            <dd>{t("pages.gigdetailspage.price")}</dd>
                        </div>
                        <div>
                            <dt>{reviews.sample.duration}</dt>
                            <dd>{t("pages.gigdetailspage.duration")}</dd>
                        </div>
                    </dl>
                </div>
                <img
                    className="review-delivery-image"
                    src={reviews.sample.image}
                    alt=""
                />
                <button className="seller-response-toggle" type="button">
                    {" "}
                    {t("pages.gigdetailspage.sellersResponse")}{" "}
                    <Icon name="chevronDown" />
                </button>
            </article>

            <div className="review-helpful-row">
                <span>{t("pages.gigdetailspage.helpful")}</span>
                <button type="button">
                    <Icon name="thumbsUp" />{" "}
                    {t("pages.gigdetailspage.yes")}{" "}
                </button>
                <button type="button">
                    <Icon name="thumbsDown" />{" "}
                    {t("pages.gigdetailspage.no")}{" "}
                </button>
            </div>

            <button className="show-more-reviews-button" type="button">
                {" "}
                {t("pages.gigdetailspage.showMoreReviews")}{" "}
            </button>

            <section
                className="related-tags-section"
                aria-labelledby="relatedTagsTitle"
            >
                <h2 id="relatedTagsTitle">
                    {t("pages.gigdetailspage.relatedTags")}
                </h2>
                <div className="related-tag-list">
                    {relatedTags.map((tag) => (
                        <Link
                            to={`/search/gigs?query=${encodeURIComponent(tag)}&source=related-tags`}
                            key={tag}
                        >
                            {tag}
                        </Link>
                    ))}
                </div>
            </section>
        </section>
    );
}
function GigDetailBottomSections({ detail, onNavigate }) {
    const { t } = useTranslation();
    const listingGigs = useMarketplaceStore((state) => state.listingGigs);
    const [isHistoryHidden, setIsHistoryHidden] = useState(false);
    const moreFromRef = useRef(null);
    const viewedRef = useRef(null);
    const historyRef = useRef(null);
    const baseGigs = listingGigs.filter((gig) => gig.id !== detail.id);
    const moreFromSeller = baseGigs
        .filter((gig) => gig.sellerUserId === detail.seller.userId)
        .slice(0, 4);
    const viewedAlso = baseGigs.slice(0, 5);
    const historyGigs = baseGigs.slice(5, 13);
    const scrollStrip = (ref, direction) => {
        ref.current?.scrollBy({
            left: direction * 290,
            behavior: "smooth",
        });
    };
    const goTo = (path) => {
        if (onNavigate) {
            onNavigate(path);
            return;
        }
        window.location.assign(path);
    };
    return (
        <section
            className="gig-detail-bottom-surface"
            aria-label={t("pages.gigdetailspage.moreServicesAndHiringOptions")}
        >
            <div className="container">
                {moreFromSeller.length ? (
                    <DetailGigStrip
                        actionLabel={`More from ${detail.seller.name}`}
                        heading={
                            <>
                                {" "}
                                {t("pages.gigdetailspage.moreFrom")}{" "}
                                <Link to={sellerProfilePath(detail.seller)}>
                                    {detail.seller.name}
                                </Link>
                            </>
                        }
                        gigs={moreFromSeller}
                        rowRef={moreFromRef}
                        onScroll={(direction) =>
                            scrollStrip(moreFromRef, direction)
                        }
                    />
                ) : null}

                {viewedAlso.length ? (
                    <DetailGigStrip
                        actionLabel="People who viewed this service also viewed"
                        heading="People Who Viewed This Service Also Viewed"
                        gigs={viewedAlso}
                        rowRef={viewedRef}
                        onScroll={(direction) =>
                            scrollStrip(viewedRef, direction)
                        }
                    />
                ) : null}

                {!isHistoryHidden && historyGigs.length ? (
                    <DetailHistoryStrip
                        gigs={historyGigs}
                        onClear={() => setIsHistoryHidden(true)}
                        onScroll={(direction) =>
                            scrollStrip(historyRef, direction)
                        }
                        rowRef={historyRef}
                    />
                ) : null}

                <section
                    className="talent-way-section detail-talent-section"
                    aria-labelledby="detailTalentWayTitle"
                >
                    <h2 id="detailTalentWayTitle">
                        {t("pages.gigdetailspage.findFreelanceTalentYourWay")}
                    </h2>
                    <div className="talent-way-grid">
                        <DetailTalentWayCard
                            action="Post a brief"
                            copy="Generate a brief with AI to receive a curated shortlist of freelancer offers."
                            icon="document"
                            onClick={() =>
                                goTo(
                                    "/search/gigs?query=project%20brief&source=detail-talent-way",
                                )
                            }
                            title={t("pages.gigdetailspage.postAProjectBrief")}
                        />
                        <DetailTalentWayCard
                            action="Get started"
                            copy="Save the endless search - we'll source, interview, and vet freelancers for you."
                            icon="user"
                            meta="Only $89"
                            onClick={() =>
                                goTo(
                                    "/search/gigs?query=expert%20sourcing&source=detail-talent-way",
                                )
                            }
                            title={t(
                                "pages.gigdetailspage.letUsFindYourFreelancer",
                            )}
                        />
                        <DetailTalentWayCard
                            action="Book free consultation"
                            copy="Big project? No problem. We'll build a freelance team and fully execute your project."
                            icon="verifiedUser"
                            meta="Custom pricing"
                            onClick={() =>
                                goTo(
                                    "/search/gigs?query=freelance%20team&source=detail-talent-way",
                                )
                            }
                            title={t(
                                "pages.gigdetailspage.getATeamBuiltForYou",
                            )}
                        />
                    </div>
                </section>
            </div>
        </section>
    );
}
function DetailGigStrip({ actionLabel, gigs, heading, onScroll, rowRef }) {
    return (
        <section className="detail-market-strip" aria-label={actionLabel}>
            <div className="detail-strip-heading">
                <h2>{heading}</h2>
                <div className="detail-strip-actions">
                    <button
                        type="button"
                        aria-label={`Scroll ${actionLabel} left`}
                        onClick={() => onScroll(-1)}
                    >
                        <Icon name="arrowRight" />
                    </button>
                    <button
                        type="button"
                        aria-label={`Scroll ${actionLabel} right`}
                        onClick={() => onScroll(1)}
                    >
                        <Icon name="arrowRight" />
                    </button>
                </div>
            </div>
            <div className="detail-strip-row" ref={rowRef}>
                {gigs.map((gig) => (
                    <DetailGigStripCard
                        gig={gig}
                        key={`${actionLabel}-${gig.id}-${gig.title}`}
                    />
                ))}
            </div>
        </section>
    );
}
function DetailGigStripCard({ gig }) {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const currentUser = useSessionStore((state) => state.currentUser);
    const toggleSavedService = useMarketplaceStore(
        (state) => state.toggleSavedService,
    );
    const toggleSaved = async () => {
        if (!currentUser?.authenticated) {
            navigate(
                `/?auth=login&redirect=${encodeURIComponent(`/gigs/${gig.id}`)}`,
            );
            return;
        }

        await toggleSavedService(gig);
    };

    return (
        <article className="detail-strip-card">
            <div className="detail-strip-media">
                <Link to={`/gigs/${gig.id}`} aria-label={`View ${gig.title}`}>
                    <img
                        src={gig.image}
                        alt={`${gig.title} preview`}
                        loading="lazy"
                        decoding="async"
                    />
                </Link>
                <button
                    className={gig.saved ? "is-favorite" : undefined}
                    type="button"
                    aria-label={`${gig.saved ? "Remove" : "Save"} ${gig.title}`}
                    aria-pressed={Boolean(gig.saved)}
                    onClick={toggleSaved}
                >
                    <Icon name="heart" />
                </button>
                {gig.consultation ? (
                    <span className="detail-strip-play" aria-hidden="true">
                        <Icon name="play" />
                    </span>
                ) : null}
            </div>
            <div className="detail-strip-seller-row">
                <Link
                    className="gig-seller-profile-link"
                    to={
                        gig.sellerProfilePath ||
                        profilePathForSeller(gig.seller, gig.sellerUsername)
                    }
                >
                    <span className="gig-avatar">
                        <img
                            src={gig.avatar}
                            alt=""
                            loading="lazy"
                            decoding="async"
                        />
                    </span>
                    <strong>{gig.seller}</strong>
                </Link>
                {gig.badge ? (
                    <span className="detail-strip-badge">{gig.badge}</span>
                ) : null}
                <em>{gig.level}</em>
            </div>
            <Link className="detail-strip-title" to={`/gigs/${gig.id}`}>
                {gig.title}
            </Link>
            <span className="detail-strip-rating">
                <Icon name="star" />
                <strong>{gig.rating.toFixed(1)}</strong>
                <span>({gig.reviews})</span>
            </span>
            <strong className="detail-strip-price">
                {t("pages.gigdetailspage.from2")}
                {gig.price}
            </strong>
            {gig.consultation ? (
                <span className="detail-strip-consultation">
                    <Icon name="video" />{" "}
                    {t("pages.gigdetailspage.offersVideoConsultations")}{" "}
                </span>
            ) : null}
        </article>
    );
}
function DetailHistoryStrip({ gigs, onClear, onScroll, rowRef }) {
    const { t } = useTranslation();
    return (
        <section
            className="detail-history-section"
            aria-labelledby="detailBrowsingHistoryTitle"
        >
            <div className="detail-strip-heading">
                <h2 id="detailBrowsingHistoryTitle">
                    {t("pages.gigdetailspage.yourBrowsingHistory")}
                </h2>
                <div className="detail-history-actions">
                    <button type="button" onClick={onClear}>
                        {" "}
                        {t("pages.gigdetailspage.clearAll")}{" "}
                    </button>
                    <span aria-hidden="true"></span>
                    <Link to="/search/gigs?source=browsing-history">
                        {t("pages.gigdetailspage.seeAll")}
                    </Link>
                    <button
                        type="button"
                        aria-label={t(
                            "pages.gigdetailspage.scrollBrowsingHistoryLeft",
                        )}
                        onClick={() => onScroll(-1)}
                    >
                        <Icon name="arrowRight" />
                    </button>
                    <button
                        type="button"
                        aria-label={t(
                            "pages.gigdetailspage.scrollBrowsingHistoryRight",
                        )}
                        onClick={() => onScroll(1)}
                    >
                        <Icon name="arrowRight" />
                    </button>
                </div>
            </div>
            <div className="detail-history-row" ref={rowRef}>
                {gigs.map((gig) => (
                    <Link
                        className="detail-history-card"
                        to={`/gigs/${gig.id}`}
                        key={`history-${gig.id}-${gig.title}`}
                    >
                        <span className="history-gig-media">
                            <img
                                src={gig.image}
                                alt=""
                                loading="lazy"
                                decoding="async"
                            />
                            <span aria-hidden="true">
                                <Icon name="heart" />
                            </span>
                        </span>
                        <strong>{gig.title}</strong>
                    </Link>
                ))}
            </div>
        </section>
    );
}
function DetailTalentWayCard({
    action,
    copy,
    icon,
    meta = "",
    onClick,
    title,
}) {
    return (
        <article className="talent-way-card">
            <Icon name={icon} />
            <h3>{title}</h3>
            <p>{copy}</p>
            <div>
                {meta ? <strong>{meta}</strong> : <span></span>}
                <button type="button" onClick={onClick}>
                    {action}
                </button>
            </div>
        </article>
    );
}
function MessageBubble({ onSend, seller }) {
    const { t } = useTranslation();
    const [isComposerOpen, setIsComposerOpen] = useState(false);
    const [messageDraft, setMessageDraft] = useState(
        `Hi ${seller.name}, I would like to discuss this service.`,
    );
    const [sendStatus, setSendStatus] = useState("");
    const [isSending, setIsSending] = useState(false);
    const promptStarters = [
        `Hi ${seller.name}, can you help with a similar project?`,
        "Could you share timeline and pricing for my scope?",
        "Can we discuss requirements before I place an order?",
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

    useEffect(() => {
        setMessageDraft(
            `Hi ${seller.name}, I would like to discuss this service.`,
        );
    }, [seller.name]);

    const sendMessage = async () => {
        const message = messageDraft.trim();

        if (!message || isSending) {
            return;
        }

        setIsSending(true);
        setSendStatus("Opening conversation...");

        const result = await onSend(message);

        if (result?.error) {
            setSendStatus(result.error);
            setIsSending(false);
        }
    };

    if (isComposerOpen) {
        return (
            <aside
                className="profile-message-composer seller-message-composer"
                aria-label={`Send a message to ${seller.name}`}
            >
                <header>
                    <img src={seller.avatar} alt="" />
                    <div>
                        <strong>
                            {t("pages.gigdetailspage.message")} {seller.name}
                        </strong>
                        <span>
                            {t("pages.gigdetailspage.awayAvgResponseTime")}{" "}
                            <b>{seller.responseTime}</b>
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
                    <label className="sr-only" htmlFor="gigSellerMessageDraft">
                        Message
                    </label>
                    <textarea
                        id="gigSellerMessageDraft"
                        maxLength={2500}
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

                <footer>
                    <div>
                        <button type="button" aria-label="Attach file">
                            <Icon name="paperclip" />
                        </button>
                    </div>
                    <button
                        className="profile-message-send"
                        type="button"
                        disabled={!messageDraft.trim() || isSending}
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
            className="seller-message-bubble"
            aria-label={`Message ${seller.name}`}
            type="button"
            onClick={() => {
                setIsComposerOpen(true);
                setSendStatus("");
            }}
        >
            <img src={seller.avatar} alt="" />
            <div>
                <strong>
                    {t("pages.gigdetailspage.message")} {seller.name}
                </strong>
                <span>
                    {t("pages.gigdetailspage.awayAvgResponseTime")}{" "}
                    {seller.responseTime}
                </span>
            </div>
        </button>
    );
}
export default GigDetailsPage;
