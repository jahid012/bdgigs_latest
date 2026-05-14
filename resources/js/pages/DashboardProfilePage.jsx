import { useState } from "react";
import { FinanceNotice } from "../components/dashboard/FinanceControls.jsx";
import { Icon } from "../components/common/Icons.jsx";
import { useTranslation } from "react-i18next";
const sellerSkills = [
    "Website development",
    "Website customization",
    "Laravel development",
    "Laravel",
    "PHP Laravel",
    "Laravel framework",
];
const profileContent = {
    buyer: {
        name: "Jahid",
        handle: "@jahid_01",
        initials: "JA",
        title: "Product Founder || Marketplace Buyer || Growth Operator",
        location: "Bangladesh",
        languages: "Speaks English, Bengali, French, Spanish",
        strength: "9",
        maxStrength: "12",
        progress: 75,
        about: "I work with specialist freelancers to ship marketplace, SaaS, and ecommerce projects with clear briefs, practical timelines, and fast review cycles. I value thoughtful communication, polished delivery, and long-term creative partnerships.",
        quickLinkLabel: "Saved services",
        quickLinkPage: "saved-services",
    },
    seller: {
        name: "Hasan",
        handle: "@jahid_01",
        initials: "HA",
        title: "Web Developer || Mobile App Developer || Full Stack Developer",
        location: "Bangladesh",
        languages: "Speaks English, Bengali, French, Spanish",
        strength: "10",
        maxStrength: "12",
        progress: 84,
        about: "Hi, I'm your dedicated PHP and full-stack developer with a passion for delivering exceptional digital solutions. Whether you need a quick fix, a complete overhaul, or a bespoke website build from scratch, I'm here to bring your vision to life. I offer free consultations, personalized attention, and a commitment to your success. With over five years of experience in web development, I ensure each project is crafted to perfection.",
        quickLinkLabel: "Gigs",
        quickLinkPage: "seller-services",
    },
};
function ProfileActionMenu({ id, label, openMenu, setOpenMenu, onAction }) {
    const { t } = useTranslation();
    const isOpen = openMenu === id;
    return (
        <div className="profile-item-actions">
            <button
                aria-expanded={isOpen}
                aria-label={`Open actions for ${label}`}
                className="profile-menu-button"
                type="button"
                onClick={() => setOpenMenu(isOpen ? "" : id)}
            >
                <Icon name="moreHorizontal" />
            </button>
            {isOpen ? (
                <div className="profile-action-menu" role="menu">
                    <button
                        type="button"
                        role="menuitem"
                        onClick={() => onAction(`Editing ${label}.`)}
                    >
                        <Icon name="edit" />{" "}
                        {t("pages.dashboardprofilepage.edit")}{" "}
                    </button>
                    <button
                        type="button"
                        role="menuitem"
                        onClick={() =>
                            onAction(`${label} removed from this preview.`)
                        }
                    >
                        <Icon name="trash" />{" "}
                        {t("pages.dashboardprofilepage.delete")}{" "}
                    </button>
                </div>
            ) : null}
        </div>
    );
}
function AddClientForm({ onCancel, onSubmit }) {
    const { t } = useTranslation();
    const [description, setDescription] = useState("");
    const [confirmed, setConfirmed] = useState(false);
    return (
        <form className="featured-client-form" onSubmit={onSubmit}>
            <div className="featured-client-form-head">
                <strong>
                    <Icon name="plus" />{" "}
                    {t("pages.dashboardprofilepage.addClient")}{" "}
                </strong>
                <button
                    aria-label={t(
                        "pages.dashboardprofilepage.closeAddClientForm",
                    )}
                    type="button"
                    onClick={onCancel}
                >
                    <Icon name="close" />
                </button>
            </div>
            <p>
                {" "}
                {t(
                    "pages.dashboardprofilepage.buildCredibilityWithPotentialClientsByFeaturingWell",
                )}{" "}
                <a href="#guidelines">
                    {t("pages.dashboardprofilepage.seeGuidelines")}
                </a>
            </p>
            <div className="profile-form-grid">
                <label className="profile-form-field full">
                    <span>{t("pages.dashboardprofilepage.clientName")}</span>
                    <select defaultValue="">
                        <option value="" disabled>
                            {" "}
                            {t("pages.dashboardprofilepage.clientName")}{" "}
                        </option>
                        <option>BDGigs</option>
                        <option>
                            {t("pages.dashboardprofilepage.cloudpeak")}
                        </option>
                        <option>
                            {t("pages.dashboardprofilepage.brightcart")}
                        </option>
                    </select>
                </label>
                <label className="profile-form-field">
                    <span>
                        {t("pages.dashboardprofilepage.projectStartDate")}
                    </span>
                    <input
                        type="text"
                        placeholder={t(
                            "pages.dashboardprofilepage.projectStartDate",
                        )}
                    />
                </label>
                <label className="profile-form-field">
                    <span>
                        {t("pages.dashboardprofilepage.projectEndDate")}
                    </span>
                    <input
                        type="text"
                        placeholder={t(
                            "pages.dashboardprofilepage.projectEndDateOptional",
                        )}
                    />
                </label>
                <label className="profile-form-field full">
                    <span>
                        {t(
                            "pages.dashboardprofilepage.describeTheWorkYouDidForThisClient",
                        )}
                    </span>
                    <textarea
                        maxLength="400"
                        placeholder={t(
                            "pages.dashboardprofilepage.describeTheWorkYouDidForThisClient",
                        )}
                        value={description}
                        onChange={(event) => setDescription(event.target.value)}
                    />
                    <small>
                        {description.length}
                        {t("pages.dashboardprofilepage.400Characters")}
                    </small>
                </label>
            </div>
            <div className="profile-verification-block">
                <strong>
                    {" "}
                    {t(
                        "pages.dashboardprofilepage.verifyYourWorkForThisClient",
                    )}{" "}
                    <span
                        aria-label={t(
                            "pages.dashboardprofilepage.moreInformation",
                        )}
                    >
                        ?
                    </span>
                </strong>
                <p>
                    {" "}
                    {t(
                        "pages.dashboardprofilepage.confirmYourWorkWithALinkToAn",
                    )}{" "}
                </p>
                <div className="profile-warning-note">
                    <Icon name="bell" />{" "}
                    {t(
                        "pages.dashboardprofilepage.submittingFalsifiedDocumentsOrWorkSamplesThatAre",
                    )}{" "}
                </div>
                <input
                    className="profile-url-input"
                    type="url"
                    placeholder="http://"
                />
                <button className="profile-link-inline" type="button">
                    <Icon name="plus" />{" "}
                    {t(
                        "pages.dashboardprofilepage.addAnotherLinkForVerification",
                    )}{" "}
                </button>
                <label className="profile-check-row">
                    <input
                        checked={confirmed}
                        type="checkbox"
                        onChange={(event) => setConfirmed(event.target.checked)}
                    />
                    <span>
                        {t(
                            "pages.dashboardprofilepage.iConfirmIveWorkedWithThisClientAnd",
                        )}
                    </span>
                </label>
            </div>
            <div className="profile-form-actions">
                <button type="button" onClick={onCancel}>
                    {" "}
                    {t("pages.dashboardprofilepage.cancel")}{" "}
                </button>
                <button disabled={!confirmed} type="submit">
                    {" "}
                    {t("pages.dashboardprofilepage.submit")}{" "}
                </button>
            </div>
        </form>
    );
}
function DashboardProfilePage({ onNavigate, variant = "buyer" }) {
    const { t } = useTranslation();
    const profile = profileContent[variant] || profileContent.buyer;
    const isSeller = variant === "seller";
    const [notice, setNotice] = useState("");
    const [isClientFormOpen, setIsClientFormOpen] = useState(false);
    const [openMenu, setOpenMenu] = useState("");
    const handleNotice = (message) => {
        setOpenMenu("");
        setNotice(message);
    };
    return (
        <main className="dashboard-content profile-edit-page">
            <FinanceNotice message={notice} />

            <div className="profile-edit-layout">
                <div className="profile-edit-main">
                    <header
                        className="profile-edit-hero"
                        aria-labelledby="profileEditName"
                    >
                        <div className="profile-edit-avatar-wrap">
                            <span className="profile-edit-avatar">
                                {profile.initials}
                            </span>
                            <button
                                aria-label={t(
                                    "pages.dashboardprofilepage.changeProfilePhoto",
                                )}
                                className="profile-avatar-camera"
                                type="button"
                                onClick={() =>
                                    handleNotice("Profile photo upload opened.")
                                }
                            >
                                <Icon name="camera" />
                            </button>
                        </div>

                        <div className="profile-edit-heading">
                            <div className="profile-name-row">
                                <h1 id="profileEditName">{profile.name}</h1>
                                <button
                                    aria-label={t(
                                        "pages.dashboardprofilepage.editName",
                                    )}
                                    className="profile-inline-icon"
                                    type="button"
                                    onClick={() =>
                                        handleNotice("Name editor opened.")
                                    }
                                >
                                    <Icon name="edit" />
                                </button>
                                <span>{profile.handle}</span>
                            </div>
                            <div className="profile-title-row">
                                <strong>{profile.title}</strong>
                                <button
                                    aria-label={t(
                                        "pages.dashboardprofilepage.editProfessionalTitle",
                                    )}
                                    className="profile-inline-icon"
                                    type="button"
                                    onClick={() =>
                                        handleNotice(
                                            "Professional title editor opened.",
                                        )
                                    }
                                >
                                    <Icon name="edit" />
                                </button>
                            </div>
                            <div className="profile-meta-row">
                                <span>
                                    <Icon name="location" />
                                    {profile.location}
                                </span>
                                <span>
                                    <Icon name="message" />
                                    <a href="#languages">{profile.languages}</a>
                                    <button
                                        aria-label={t(
                                            "pages.dashboardprofilepage.editLanguages",
                                        )}
                                        className="profile-inline-icon"
                                        type="button"
                                        onClick={() =>
                                            handleNotice(
                                                "Language editor opened.",
                                            )
                                        }
                                    >
                                        <Icon name="edit" />
                                    </button>
                                </span>
                            </div>
                        </div>

                        <div className="profile-hero-actions">
                            <button
                                type="button"
                                onClick={() =>
                                    handleNotice(
                                        "Share link copied to clipboard.",
                                    )
                                }
                            >
                                <Icon name="share" />{" "}
                                {t("pages.dashboardprofilepage.share")}{" "}
                            </button>
                            <button
                                type="button"
                                onClick={() =>
                                    handleNotice(
                                        "Public profile preview opened.",
                                    )
                                }
                            >
                                <Icon name="eye" />{" "}
                                {t("pages.dashboardprofilepage.preview")}{" "}
                            </button>
                        </div>
                    </header>

                    <article className="profile-edit-card">
                        <h2>{t("pages.dashboardprofilepage.about")}</h2>
                        <p className="profile-about-copy">{profile.about}</p>
                    </article>

                    <article className="profile-edit-card featured-clients-card">
                        <div className="profile-card-split">
                            <div>
                                <h2>
                                    {t(
                                        "pages.dashboardprofilepage.featuredClients",
                                    )}
                                </h2>
                                <p>
                                    {t(
                                        "pages.dashboardprofilepage.buildCredibilityByFeaturingUpTo5Clients",
                                    )}
                                </p>
                                <button
                                    className="profile-light-button"
                                    type="button"
                                    onClick={() => setIsClientFormOpen(true)}
                                >
                                    <Icon name="plus" />{" "}
                                    {t(
                                        "pages.dashboardprofilepage.addClient",
                                    )}{" "}
                                </button>
                            </div>
                            <div
                                className="profile-card-illustration"
                                aria-hidden="true"
                            >
                                <Icon name="document" />
                                <span></span>
                            </div>
                        </div>
                        {isClientFormOpen ? (
                            <AddClientForm
                                onCancel={() => setIsClientFormOpen(false)}
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    setIsClientFormOpen(false);
                                    handleNotice(
                                        "Featured client saved to your profile preview.",
                                    );
                                }}
                            />
                        ) : null}
                    </article>

                    <article className="profile-edit-card portfolio-card">
                        <h2>{t("pages.dashboardprofilepage.portfolio")}</h2>
                        <div className="portfolio-preview-thumb">
                            <img
                                alt={t(
                                    "pages.dashboardprofilepage.portfolioPreview",
                                )}
                                src="/assets/img/gig_images/1.png"
                            />
                        </div>
                        <button
                            className="profile-light-button"
                            type="button"
                            onClick={() =>
                                handleNotice("Portfolio builder opened.")
                            }
                        >
                            <Icon name="share" />{" "}
                            {t(
                                "pages.dashboardprofilepage.startPortfolio",
                            )}{" "}
                        </button>
                    </article>

                    <article className="profile-edit-card intro-video-card">
                        <div>
                            <h2>
                                {t("pages.dashboardprofilepage.introVideo")}
                            </h2>
                            <p>
                                {t(
                                    "pages.dashboardprofilepage.introduceYourselfAndMakeAConnectionWithPotential",
                                )}
                            </p>
                            <button
                                className="profile-light-button"
                                type="button"
                                onClick={() =>
                                    handleNotice("Intro video uploader opened.")
                                }
                            >
                                <Icon name="plus" />{" "}
                                {t(
                                    "pages.dashboardprofilepage.addIntroVideo",
                                )}{" "}
                            </button>
                        </div>
                        <div
                            className="profile-video-illustration"
                            aria-hidden="true"
                        >
                            <span>
                                <Icon name="user" />
                            </span>
                            <i>
                                <Icon name="play" />
                            </i>
                        </div>
                    </article>

                    <article className="profile-edit-card">
                        <div className="profile-section-title-row">
                            <h2>
                                {t("pages.dashboardprofilepage.workExperience")}
                            </h2>
                            <button
                                className="profile-light-button"
                                type="button"
                                onClick={() =>
                                    handleNotice("Work experience form opened.")
                                }
                            >
                                <Icon name="plus" />{" "}
                                {t("pages.dashboardprofilepage.addNew")}{" "}
                            </button>
                        </div>
                        <div className="profile-record-card">
                            <span className="profile-record-icon">
                                <Icon name="building" />
                            </span>
                            <div>
                                <strong>
                                    {t(
                                        "pages.dashboardprofilepage.softwareEngineer",
                                    )}
                                </strong>
                                <small>
                                    {t(
                                        "pages.dashboardprofilepage.theSoftkingLimitedFullTime",
                                    )}
                                </small>
                                <small>
                                    {t(
                                        "pages.dashboardprofilepage.jun2023Present2Yrs11Mos",
                                    )}
                                </small>
                                <p>
                                    {" "}
                                    {t(
                                        "pages.dashboardprofilepage.iDesignDevelopAndMaintainWebApplicationsUsing",
                                    )}{" "}
                                </p>
                            </div>
                            <ProfileActionMenu
                                id="work-experience"
                                label="work experience"
                                openMenu={openMenu}
                                setOpenMenu={setOpenMenu}
                                onAction={handleNotice}
                            />
                        </div>
                    </article>

                    <article className="profile-edit-card">
                        <div className="profile-section-title-row">
                            <h2>
                                {t(
                                    "pages.dashboardprofilepage.skillsAndExpertise",
                                )}
                            </h2>
                            <button
                                className="profile-light-button"
                                type="button"
                                onClick={() =>
                                    handleNotice("Skill editor opened.")
                                }
                            >
                                <Icon name="plus" />{" "}
                                {t("pages.dashboardprofilepage.addNew")}{" "}
                            </button>
                        </div>
                        <div className="profile-skill-grid">
                            {sellerSkills.map((skill) => (
                                <div className="profile-skill-card" key={skill}>
                                    <div>
                                        <strong>{skill}</strong>
                                        <span>
                                            {isSeller ? "Pro" : "Verified"}
                                        </span>
                                    </div>
                                    <ProfileActionMenu
                                        id={`skill-${skill}`}
                                        label={skill}
                                        openMenu={openMenu}
                                        setOpenMenu={setOpenMenu}
                                        onAction={handleNotice}
                                    />
                                </div>
                            ))}
                        </div>
                    </article>

                    <div className="profile-edit-two-col">
                        <article className="profile-edit-card">
                            <div className="profile-section-title-row">
                                <h2>
                                    {t("pages.dashboardprofilepage.education")}
                                </h2>
                                <button
                                    className="profile-light-button"
                                    type="button"
                                    onClick={() =>
                                        handleNotice("Education form opened.")
                                    }
                                >
                                    <Icon name="plus" />{" "}
                                    {t(
                                        "pages.dashboardprofilepage.addNew",
                                    )}{" "}
                                </button>
                            </div>
                            <div className="profile-record-card compact">
                                <span className="profile-record-icon">
                                    <Icon name="graduation" />
                                </span>
                                <div>
                                    <strong>
                                        {t(
                                            "pages.dashboardprofilepage.universityOfDhaka",
                                        )}
                                    </strong>
                                    <small>
                                        {t(
                                            "pages.dashboardprofilepage.bScDegreeComputerscienceEngineering",
                                        )}
                                    </small>
                                    <small>
                                        {t(
                                            "pages.dashboardprofilepage.bangladeshGraduated2018",
                                        )}
                                    </small>
                                </div>
                                <ProfileActionMenu
                                    id="education"
                                    label="education"
                                    openMenu={openMenu}
                                    setOpenMenu={setOpenMenu}
                                    onAction={handleNotice}
                                />
                            </div>
                        </article>

                        <article className="profile-edit-card certification-card">
                            <h2>
                                {t("pages.dashboardprofilepage.certifications")}
                            </h2>
                            <p>
                                {t(
                                    "pages.dashboardprofilepage.showcaseYourMasteryWithCertificationsEarnedInYour",
                                )}
                            </p>
                            <button
                                className="profile-light-button"
                                type="button"
                                onClick={() =>
                                    handleNotice("Certification form opened.")
                                }
                            >
                                <Icon name="plus" />{" "}
                                {t(
                                    "pages.dashboardprofilepage.addCertifications",
                                )}{" "}
                            </button>
                        </article>
                    </div>
                </div>

                <aside
                    className="profile-edit-aside"
                    aria-label={t(
                        "pages.dashboardprofilepage.profileCompletion",
                    )}
                >
                    <article className="profile-side-card">
                        <div className="profile-strength-head">
                            <h2>
                                {t(
                                    "pages.dashboardprofilepage.profileStrength",
                                )}
                            </h2>
                            <strong>
                                {profile.strength}
                                <span>/{profile.maxStrength}</span>
                            </strong>
                        </div>
                        <p>
                            {t(
                                "pages.dashboardprofilepage.aStrongProfileHelpsYouStandOutAnd",
                            )}
                        </p>
                        <div className="profile-strength-track">
                            <span
                                style={{
                                    "--strength": `${profile.progress}%`,
                                }}
                            ></span>
                        </div>
                        <button
                            type="button"
                            onClick={() =>
                                handleNotice("Intro video uploader opened.")
                            }
                        >
                            <Icon name="video" />{" "}
                            {t(
                                "pages.dashboardprofilepage.createAnIntroVideo",
                            )}{" "}
                        </button>
                        <button
                            type="button"
                            onClick={() =>
                                handleNotice("Certification form opened.")
                            }
                        >
                            <Icon name="document" />{" "}
                            {t(
                                "pages.dashboardprofilepage.listCertifications",
                            )}{" "}
                        </button>
                    </article>

                    <article className="profile-side-card">
                        <h2>{t("pages.dashboardprofilepage.quickLinks")}</h2>
                        <button
                            type="button"
                            onClick={() => onNavigate?.(profile.quickLinkPage)}
                        >
                            <Icon name="orders" />
                            {profile.quickLinkLabel}
                        </button>
                    </article>
                </aside>
            </div>
        </main>
    );
}
export default DashboardProfilePage;
