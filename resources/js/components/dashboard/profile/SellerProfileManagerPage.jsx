import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { FinanceNotice } from "../FinanceControls.jsx";
import { Icon } from "../../common/Icons.jsx";
import { profilePathForSeller } from "../../../data/userProfileData.js";
import { apiRequest } from "../../../api/apiClient.js";

const sellerProfile = {
    name: "",
    handle: "",
    title: "",
    location: "",
    languages: "Add languages",
    avatar: "",
    rating: "0.0",
    reviews: "0",
    about: "",
};

const industries = [
    "Programming & Tech",
    "Digital Marketing",
    "Graphics & Design",
    "Video & Animation",
    "Writing & Translation",
    "Business",
];

const expertiseAreas = [
    "WordPress",
    "Laravel",
    "Mobile App",
    "Payment Gateway",
    "React",
    "Full Stack",
];

const projectDurations = [
    "Less than 1 month",
    "1-3 months",
    "3-6 months",
    "6+ months",
];

const months = [
    ["01", "January"],
    ["02", "February"],
    ["03", "March"],
    ["04", "April"],
    ["05", "May"],
    ["06", "June"],
    ["07", "July"],
    ["08", "August"],
    ["09", "September"],
    ["10", "October"],
    ["11", "November"],
    ["12", "December"],
];

const years = ["2026", "2025", "2024", "2023", "2022", "2021"];

const starterProjects = [];

const defaultSkills = [];

const defaultWorkExperience = null;

const defaultEducation = null;

const defaultCertification = null;

const emptyWorkExperienceDraft = {
    title: "",
    employmentType: "Full-time",
    company: "",
    startDate: "",
    endDate: "",
    duration: "",
    description: "",
};

const emptyEducationDraft = {
    country: "",
    university: "",
    degree: "B.Sc.",
    major: "",
    year: "2026",
};

const emptyCertificationDraft = {
    name: "",
    provider: "",
    year: "2026",
    credentialUrl: "",
};

const defaultSellerLanguages = [];

const sellerLanguageOptions = [
    "English",
    "Bengali",
    "French",
    "Spanish",
    "Hindi",
    "Arabic",
    "Urdu",
    "German",
    "Portuguese",
];

const sellerProficiencyOptions = [
    "Basic",
    "Conversational",
    "Fluent",
    "Native/Bilingual",
];

const storageKeys = {
    title: "bdgigs:seller-profile-title",
    languages: "bdgigs:seller-profile-languages",
    about: "bdgigs:seller-profile-about",
    projects: "bdgigs:seller-profile-projects",
    skills: "bdgigs:seller-profile-skills",
    education: "bdgigs:seller-profile-education",
    work: "bdgigs:seller-profile-work",
    certification: "bdgigs:seller-profile-certification",
};

function readStoredValue(key, fallback) {
    return fallback;
}

function writeStoredValue(key, value) {
    return { key, value };
}

function formatSellerLanguages(languages) {
    if (!languages?.length) {
        return "Add languages";
    }

    return `Speaks ${languages
        .map((item) => item.language)
        .filter(Boolean)
        .join(", ")}`;
}

function createEmptyProjectForm() {
    return {
        id: "",
        name: "",
        industry: "",
        expertise: "",
        duration: "",
        cost: "",
        startedMonth: "",
        startedYear: "",
        madeOnFiverr: true,
        image: "",
        mediaCount: 0,
        linkedCatalog: "",
        description: "",
        attachmentName: "",
    };
}

function projectToForm(project) {
    return {
        ...createEmptyProjectForm(),
        ...project,
        attachmentName: project?.image ? "Current portfolio preview" : "",
    };
}

function formToProject(form) {
    const generatedId = form.name
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/(^-|-$)/g, "");

    return {
        ...form,
        id: form.id || generatedId || `portfolio-project-${Date.now()}`,
        image: form.image || "/assets/img/gig_images/1.png",
        mediaCount: form.mediaCount || 1,
        linkedCatalog: form.linkedCatalog || "Full Stack Web Applications",
    };
}

function SellerProfileManagerPage({ initialMode = "profile" }) {
    const navigate = useNavigate();
    const [notice, setNotice] = useState("");
    const [mode, setMode] = useState(initialMode);
    const [activeEditor, setActiveEditor] = useState("");
    const [openActionMenu, setOpenActionMenu] = useState("");
    const [isSharePopupOpen, setIsSharePopupOpen] = useState(false);
    const [professionalTitle, setProfessionalTitle] = useState(() =>
        readStoredValue(storageKeys.title, sellerProfile.title),
    );
    const [sellerLanguages, setSellerLanguages] = useState(() =>
        readStoredValue(storageKeys.languages, defaultSellerLanguages),
    );
    const [about, setAbout] = useState(() =>
        readStoredValue(storageKeys.about, sellerProfile.about),
    );
    const [projects, setProjects] = useState(() =>
        readStoredValue(storageKeys.projects, starterProjects),
    );
    const [skills, setSkills] = useState(() =>
        readStoredValue(storageKeys.skills, defaultSkills),
    );
    const [education, setEducation] = useState(() =>
        readStoredValue(storageKeys.education, defaultEducation),
    );
    const [workExperience, setWorkExperience] = useState(() =>
        readStoredValue(storageKeys.work, defaultWorkExperience),
    );
    const [certification, setCertification] = useState(() =>
        readStoredValue(storageKeys.certification, null),
    );
    const [industryFilter, setIndustryFilter] = useState("");
    const [expertiseFilter, setExpertiseFilter] = useState("");
    const [madeOnFiverrOnly, setMadeOnFiverrOnly] = useState(false);
    const [projectStep, setProjectStep] = useState(1);
    const [editingProjectId, setEditingProjectId] = useState("");
    const [projectForm, setProjectForm] = useState(createEmptyProjectForm);
    const [identityProfile, setIdentityProfile] = useState(sellerProfile);
    const [profileLoaded, setProfileLoaded] = useState(false);

    const displayProfile = useMemo(
        () => ({
            ...identityProfile,
            title: professionalTitle,
            about,
            languages: formatSellerLanguages(sellerLanguages),
        }),
        [about, identityProfile, professionalTitle, sellerLanguages],
    );
    const sellerPublicProfilePath = useMemo(
        () => profilePathForSeller(displayProfile.handle || displayProfile.name),
        [displayProfile],
    );

    const filteredProjects = useMemo(
        () =>
            projects.filter((project) => {
                const industryMatch =
                    !industryFilter || project.industry === industryFilter;
                const expertiseMatch =
                    !expertiseFilter || project.expertise === expertiseFilter;
                const madeOnFiverrMatch =
                    !madeOnFiverrOnly || project.madeOnFiverr;

                return industryMatch && expertiseMatch && madeOnFiverrMatch;
            }),
        [expertiseFilter, industryFilter, madeOnFiverrOnly, projects],
    );

    useEffect(() => {
        apiRequest("/api/user/profile/seller")
            .then((profile) => {
                setIdentityProfile((current) => ({
                    ...current,
                    name: profile.name,
                    handle: profile.handle,
                    avatar: profile.avatar,
                    location: profile.location,
                    rating: profile.rating,
                    reviews: profile.reviews,
                }));
                setProfessionalTitle(profile.title || "");
                setSellerLanguages(profile.languages || []);
                setAbout(profile.about || "");
                setProjects(profile.projects || []);
                setSkills(profile.skills || []);
                setEducation(profile.education || null);
                setWorkExperience(profile.workExperience || null);
                setCertification(profile.certification || null);
                setProfileLoaded(true);
            })
            .catch(() => setProfileLoaded(true));
    }, []);

    useEffect(() => {
        if (!profileLoaded) return;

        apiRequest("/api/user/profile/seller", {
            method: "PATCH",
            body: {
                title: professionalTitle,
                languages: sellerLanguages,
                about,
                projects,
                skills,
                education,
                workExperience,
                certification,
            },
        }).catch(() => {});
    }, [
        about,
        certification,
        education,
        professionalTitle,
        profileLoaded,
        projects,
        sellerLanguages,
        skills,
        workExperience,
    ]);

    useEffect(() => {
        setMode(initialMode);
    }, [initialMode]);

    useEffect(() => {
        if (!openActionMenu) {
            return undefined;
        }

        const closeMenu = () => setOpenActionMenu("");
        const closeOnEscape = (event) => {
            if (event.key === "Escape") {
                closeMenu();
            }
        };

        window.addEventListener("click", closeMenu);
        window.addEventListener("keydown", closeOnEscape);

        return () => {
            window.removeEventListener("click", closeMenu);
            window.removeEventListener("keydown", closeOnEscape);
        };
    }, [openActionMenu]);

    const openCreateProject = () => {
        setEditingProjectId("");
        setProjectForm(createEmptyProjectForm());
        setProjectStep(1);
        setMode("project-form");
        setNotice("");
    };

    const openEditProject = (project) => {
        setEditingProjectId(project.id);
        setProjectForm(projectToForm(project));
        setProjectStep(1);
        setMode("project-form");
        setNotice("");
    };

    const closeProjectForm = () => {
        setMode("portfolio");
        setProjectStep(1);
        setEditingProjectId("");
        setProjectForm(createEmptyProjectForm());
    };

    const updateProjectForm = (field, value) => {
        setProjectForm((current) => ({ ...current, [field]: value }));
    };

    const saveProject = () => {
        const project = formToProject(projectForm);

        setProjects((current) => {
            if (!editingProjectId) {
                return [project, ...current];
            }

            return current.map((item) =>
                item.id === editingProjectId ? project : item,
            );
        });

        setNotice(
            editingProjectId
                ? "Portfolio project updated."
                : "Portfolio project created.",
        );
        closeProjectForm();
    };

    const handleProjectContinue = () => {
        if (projectStep === 1) {
            setProjectStep(2);
            return;
        }

        saveProject();
    };

    const deleteProject = (projectId) => {
        setProjects((current) =>
            current.filter((project) => project.id !== projectId),
        );
        setNotice("Portfolio project deleted.");
    };

    const openEditor = (editor) => {
        setOpenActionMenu("");
        setActiveEditor(editor);
    };

    const openPortfolioManager = () => {
        setMode("portfolio");
        navigate("/dashboard/seller/profile/portfolio");
    };

    const openProfileEditor = () => {
        setMode("profile");
        navigate("/dashboard/seller/profile");
    };

    const openSellerPreview = () => {
        navigate(sellerPublicProfilePath);
    };

    if (mode === "project-form") {
        return (
            <ProjectFormView
                editing={Boolean(editingProjectId)}
                form={projectForm}
                profile={displayProfile}
                step={projectStep}
                onCancel={closeProjectForm}
                onContinue={handleProjectContinue}
                onStepChange={setProjectStep}
                onUpdate={updateProjectForm}
            />
        );
    }

    if (mode === "portfolio") {
        return (
            <main className="dashboard-content seller-profile-manager">
                <FinanceNotice message={notice} />
                <button
                    className="seller-back-link"
                    type="button"
                    onClick={openProfileEditor}
                >
                    <Icon name="arrowRight" /> Back to profile editor
                </button>
                <SellerProfileSummary profile={displayProfile} />
                <PortfolioManager
                    expertiseFilter={expertiseFilter}
                    filteredProjects={filteredProjects}
                    industryFilter={industryFilter}
                    madeOnFiverrOnly={madeOnFiverrOnly}
                    onCreate={openCreateProject}
                    onDelete={deleteProject}
                    onEdit={openEditProject}
                    onExpertiseFilterChange={setExpertiseFilter}
                    onIndustryFilterChange={setIndustryFilter}
                    onMadeOnFiverrChange={setMadeOnFiverrOnly}
                />
            </main>
        );
    }

    return (
        <main className="dashboard-content seller-editor-page">
            <FinanceNotice message={notice} />
            {isSharePopupOpen ? (
                <ShareExpertisePopup
                    onClose={() => setIsSharePopupOpen(false)}
                    onCopy={() => {
                        navigator.clipboard?.writeText(
                            `${window.location.origin}${sellerPublicProfilePath}`,
                        );
                        setNotice("Profile link copied.");
                        setIsSharePopupOpen(false);
                    }}
                />
            ) : null}
            <div className="seller-editor-layout">
                <div className="seller-editor-main">
                    <SellerEditorHero
                        activeEditor={activeEditor}
                        languages={sellerLanguages}
                        profile={displayProfile}
                        onCancelEditor={() => setActiveEditor("")}
                        onEdit={openEditor}
                        onLanguagesChange={setSellerLanguages}
                        onNotice={setNotice}
                        onPreview={openSellerPreview}
                        onShare={() => setIsSharePopupOpen(true)}
                        onTitleChange={setProfessionalTitle}
                    />

                    <AboutProfileSection
                        about={about}
                        isEditing={activeEditor === "about"}
                        onCancel={() => setActiveEditor("")}
                        onEdit={() => openEditor("about")}
                        onSave={(value) => {
                            setAbout(value);
                            setActiveEditor("");
                            setNotice("About section updated.");
                        }}
                    />

                    <FeaturedClientsSection
                        isEditing={activeEditor === "featured-clients"}
                        onCancel={() => setActiveEditor("")}
                        onEdit={() => openEditor("featured-clients")}
                        onSave={() => {
                            setActiveEditor("");
                            setNotice("Featured client saved.");
                        }}
                    />

                    <PortfolioPreviewSection
                        project={projects[0]}
                        onOpen={openPortfolioManager}
                    />

                    <IntroVideoSection
                        isEditing={activeEditor === "intro-video"}
                        onCancel={() => setActiveEditor("")}
                        onEdit={() => openEditor("intro-video")}
                        onSave={() => {
                            setActiveEditor("");
                            setNotice("Intro video submitted for review.");
                        }}
                    />

                    <WorkExperienceSection
                        openMenu={openActionMenu}
                        isEditing={activeEditor === "work"}
                        work={workExperience}
                        onActionMenuChange={setOpenActionMenu}
                        onCancel={() => setActiveEditor("")}
                        onDelete={() => {
                            setWorkExperience(null);
                            setOpenActionMenu("");
                            setActiveEditor("");
                            setNotice("Work experience removed.");
                        }}
                        onEdit={() => openEditor("work")}
                        onSave={(nextWork) => {
                            setWorkExperience(nextWork);
                            setActiveEditor("");
                            setNotice("Work experience updated.");
                        }}
                    />

                    <SkillsSection
                        editingSkill={activeEditor}
                        openMenu={openActionMenu}
                        skills={skills}
                        onActionMenuChange={setOpenActionMenu}
                        onCancel={() => setActiveEditor("")}
                        onDelete={(skill) => {
                            setSkills((current) =>
                                current.filter((item) => item !== skill),
                            );
                            setOpenActionMenu("");
                            setActiveEditor("");
                            setNotice("Skill removed.");
                        }}
                        onEdit={(skill) => openEditor(`skill:${skill}`)}
                        onNew={() => openEditor("skill:new")}
                        onSave={(oldSkill, nextSkill) => {
                            setSkills((current) => {
                                if (!oldSkill) {
                                    return [nextSkill, ...current];
                                }

                                return current.map((item) =>
                                    item === oldSkill ? nextSkill : item,
                                );
                            });
                            setActiveEditor("");
                            setNotice("Skill updated.");
                        }}
                    />

                    <EducationCertificationSection
                        certification={certification}
                        education={education}
                        openMenu={openActionMenu}
                        editingSection={activeEditor}
                        onActionMenuChange={setOpenActionMenu}
                        onCancel={() => setActiveEditor("")}
                        onCertificationEdit={() => openEditor("certification")}
                        onCertificationSave={(nextCertification) => {
                            setCertification(nextCertification);
                            setActiveEditor("");
                            setNotice("Certification updated.");
                        }}
                        onDelete={() => {
                            setEducation(null);
                            setOpenActionMenu("");
                            setActiveEditor("");
                            setNotice("Education removed.");
                        }}
                        onEdit={() => openEditor("education")}
                        onSave={(nextEducation) => {
                            setEducation(nextEducation);
                            setActiveEditor("");
                            setNotice("Education updated.");
                        }}
                    />
                </div>

                <SellerEditorAside
                    onOpenCertification={() => openEditor("certification")}
                    onOpenPortfolio={openPortfolioManager}
                />
            </div>
        </main>
    );
}

function SellerEditorHero({
    activeEditor,
    languages,
    profile,
    onCancelEditor,
    onEdit,
    onLanguagesChange,
    onNotice,
    onPreview,
    onShare,
    onTitleChange,
}) {
    const isTitleEditing = activeEditor === "title";
    const isLanguagesEditing = activeEditor === "languages";

    const handleTitleKeyDown = (event) => {
        if (event.key === "Enter" || event.key === "Escape") {
            event.preventDefault();
            onCancelEditor();
        }
    };

    return (
        <header className="seller-editor-hero">
            <div className="seller-editor-avatar-wrap">
                <img src={profile.avatar} alt={`${profile.name} profile`} />
                <button
                    type="button"
                    aria-label="Change profile photo"
                    onClick={() => onNotice("Profile photo uploader opened.")}
                >
                    <Icon name="camera" />
                </button>
            </div>
            <div className="seller-editor-hero-copy">
                <div className="seller-editor-name-row">
                    <h1>{profile.name}</h1>
                    <button
                        type="button"
                        aria-label="Edit name"
                        onClick={() => onEdit("name")}
                    >
                        <Icon name="edit" />
                    </button>
                        <span>{profile.handle}</span>
                </div>
                <p className={isTitleEditing ? "seller-title-edit-row" : ""}>
                    {isTitleEditing ? (
                        <>
                            <input
                                className="seller-title-edit-input"
                                aria-label="Professional title"
                                autoFocus
                                maxLength={80}
                                value={profile.title}
                                onChange={(event) =>
                                    onTitleChange(event.target.value)
                                }
                                onKeyDown={handleTitleKeyDown}
                            />
                            <span className="seller-title-help-card">
                                Stand out with a clear headline that explains
                                what you do.
                                <a href="#about">Learn more</a>
                            </span>
                        </>
                    ) : (
                        <>
                            <strong>{profile.title}</strong>
                            <button
                                type="button"
                                aria-label="Edit professional title"
                                onClick={() => onEdit("title")}
                            >
                                <Icon name="edit" />
                            </button>
                        </>
                    )}
                </p>
                <div className="seller-editor-meta">
                    <span>
                        <Icon name="location" />
                        {profile.location}
                    </span>
                    <span
                        className={
                            isLanguagesEditing
                                ? "seller-language-editor-anchor is-open"
                                : "seller-language-editor-anchor"
                        }
                    >
                        <Icon name="message" />
                        <a href="#languages">{profile.languages}</a>
                        <button
                            className="seller-language-edit-toggle"
                            type="button"
                            aria-label={
                                isLanguagesEditing
                                    ? "Close languages editor"
                                    : "Edit languages"
                            }
                            onClick={() =>
                                isLanguagesEditing
                                    ? onCancelEditor()
                                    : onEdit("languages")
                            }
                        >
                            <Icon name={isLanguagesEditing ? "close" : "edit"} />
                        </button>
                        {isLanguagesEditing ? (
                            <LanguageEditorPopover
                                languages={languages}
                                onChange={onLanguagesChange}
                            />
                        ) : null}
                    </span>
                </div>
            </div>
            <div className="seller-editor-hero-actions">
                <button
                    type="button"
                    onClick={onShare}
                >
                    <Icon name="share" /> Share
                </button>
                <button
                    type="button"
                    onClick={onPreview}
                >
                    <Icon name="eye" /> Preview
                </button>
            </div>
        </header>
    );
}

function LanguageEditorPopover({ languages, onChange }) {
    const updateLanguage = (id, field, value) => {
        onChange(
            languages.map((item) =>
                item.id === id ? { ...item, [field]: value } : item,
            ),
        );
    };

    const deleteLanguage = (id) => {
        if (languages.length <= 1) {
            return;
        }

        onChange(languages.filter((item) => item.id !== id));
    };

    const addLanguage = () => {
        onChange([
            ...languages,
            {
                id: `language-${Date.now()}`,
                language: "English",
                proficiency: "Fluent",
            },
        ]);
    };

    return (
        <div className="seller-language-popover" role="dialog">
            <div className="seller-language-help">
                <span aria-hidden="true">i</span>
                <p>
                    Add the languages you work in and your proficiency level to
                    align expectations with potential clients.
                </p>
            </div>

            <div className="seller-language-list">
                {languages.map((item) => (
                    <div className="seller-language-row" key={item.id}>
                        <select
                            aria-label="Language"
                            value={item.language}
                            onChange={(event) =>
                                updateLanguage(
                                    item.id,
                                    "language",
                                    event.target.value,
                                )
                            }
                        >
                            {sellerLanguageOptions.map((language) => (
                                <option value={language} key={language}>
                                    {language}
                                </option>
                            ))}
                        </select>
                        <select
                            aria-label={`${item.language} proficiency`}
                            value={item.proficiency}
                            onChange={(event) =>
                                updateLanguage(
                                    item.id,
                                    "proficiency",
                                    event.target.value,
                                )
                            }
                        >
                            {sellerProficiencyOptions.map((proficiency) => (
                                <option value={proficiency} key={proficiency}>
                                    {proficiency}
                                </option>
                            ))}
                        </select>
                        {languages.length > 1 ? (
                            <button
                                type="button"
                                onClick={() => deleteLanguage(item.id)}
                            >
                                Delete
                            </button>
                        ) : null}
                    </div>
                ))}
            </div>

            <button
                className="seller-add-language-button"
                type="button"
                onClick={addLanguage}
            >
                <span aria-hidden="true">+</span> Add languages
            </button>
        </div>
    );
}

function SellerEditorAside({ onOpenCertification, onOpenPortfolio }) {
    return (
        <aside className="seller-editor-aside">
            <section className="seller-editor-side-card">
                <div className="seller-profile-strength-head">
                    <h2>Profile Strength</h2>
                    <strong>
                        10<span>/12</span>
                    </strong>
                </div>
                <p>
                    A strong profile helps you stand out and attract better
                    opportunities.
                </p>
                <div className="seller-strength-track">
                    <span></span>
                </div>
                <button type="button">
                    <Icon name="video" /> Create an intro video
                </button>
                <button type="button" onClick={onOpenCertification}>
                    <Icon name="document" /> List certifications
                </button>
            </section>
            <section className="seller-editor-side-card">
                <h2>Quick Links</h2>
                <button
                    className="seller-quick-gigs-card"
                    type="button"
                    onClick={onOpenPortfolio}
                >
                    <span>
                        <Icon name="orders" />
                    </span>
                    <strong>Gigs</strong>
                    <Icon name="arrowRight" />
                </button>
            </section>
        </aside>
    );
}

function ShareExpertisePopup({ onClose, onCopy }) {
    const shareItems = [
        { label: "Facebook", mark: "f", type: "facebook" },
        { label: "LinkedIn", mark: "in", type: "linkedin" },
        { label: "Twitter", mark: "t", type: "twitter" },
        { label: "WhatsApp", mark: "w", type: "whatsapp" },
    ];

    return (
        <div className="seller-share-layer" role="presentation">
            <button
                className="seller-share-backdrop"
                type="button"
                aria-label="Close share popup"
                onClick={onClose}
            ></button>
            <section
                className="seller-share-popup"
                role="dialog"
                aria-modal="true"
                aria-labelledby="sellerShareTitle"
            >
                <button
                    className="seller-share-close"
                    type="button"
                    aria-label="Close"
                    onClick={onClose}
                >
                    <Icon name="close" />
                </button>
                <h2 id="sellerShareTitle">Share the expertise</h2>
                <p>Let people know about this freelancer.</p>
                <div className="seller-share-options">
                    {shareItems.map((item) => (
                        <button
                            className={`seller-share-option ${item.type}`}
                            key={item.label}
                            type="button"
                        >
                            <span>{item.mark}</span>
                            {item.label}
                        </button>
                    ))}
                    <button
                        className="seller-share-option copy"
                        type="button"
                        onClick={onCopy}
                    >
                        <span>
                            <Icon name="share" />
                        </span>
                        Copy Link
                    </button>
                </div>
            </section>
        </div>
    );
}

function SellerSectionCard({
    children,
    className = "",
    onClick,
    title,
}) {
    return (
        <section
            className={`seller-editor-card ${className}`}
            onClick={onClick}
        >
            {title ? <h2>{title}</h2> : null}
            {children}
        </section>
    );
}

function SellerActionDropdown({
    id,
    label,
    openMenu,
    onDelete,
    onEdit,
    onOpenChange,
}) {
    const isOpen = openMenu === id;

    return (
        <div
            className="seller-action-menu-wrap"
            onClick={(event) => event.stopPropagation()}
        >
            <button
                className="seller-three-dot-button"
                type="button"
                aria-label={`Open actions for ${label}`}
                aria-expanded={isOpen}
                onClick={() => onOpenChange(isOpen ? "" : id)}
            >
                <Icon name="moreHorizontal" />
            </button>
            {isOpen ? (
                <div className="seller-action-dropdown" role="menu">
                    <button
                        type="button"
                        role="menuitem"
                        onClick={() => {
                            onOpenChange("");
                            onEdit();
                        }}
                    >
                        <Icon name="edit" /> Edit
                    </button>
                    <button
                        type="button"
                        role="menuitem"
                        onClick={() => {
                            onOpenChange("");
                            onDelete();
                        }}
                    >
                        <Icon name="trash" /> Delete
                    </button>
                </div>
            ) : null}
        </div>
    );
}

function AboutProfileSection({
    about,
    isEditing,
    onCancel,
    onEdit,
    onSave,
}) {
    const [draft, setDraft] = useState(about);

    useEffect(() => {
        setDraft(about);
    }, [about, isEditing]);

    if (isEditing) {
        return (
            <section className="seller-profile-card seller-about-card seller-about-editing-card">
                <div className="seller-editor-card-head">
                    <h2>About</h2>
                    <button type="button" onClick={onCancel}>
                        Done
                    </button>
                </div>
                <div className="seller-about-helper">
                    <Icon name="document" />
                    <span>
                        Add details about your expertise and the services you
                        offer to help clients get to know you.
                    </span>
                </div>
                <div className="seller-about-editor">
                    <textarea
                        maxLength={600}
                        value={draft}
                        onChange={(event) => setDraft(event.target.value)}
                    />
                    <button
                        className="seller-ai-action"
                        type="button"
                        aria-label="Improve about text"
                    >
                        <Icon name="spark" />
                    </button>
                    <div className="seller-about-editor-footer">
                        <button
                            type="button"
                            aria-label="Copy about text"
                            onClick={() =>
                                navigator.clipboard?.writeText(draft)
                            }
                        >
                            <Icon name="document" />
                        </button>
                        <span>{draft.length}/600 characters</span>
                    </div>
                </div>
                <div className="seller-inline-actions">
                    <button type="button" onClick={onCancel}>
                        Cancel
                    </button>
                    <button type="button" onClick={() => onSave(draft)}>
                        Update
                    </button>
                </div>
            </section>
        );
    }

    return (
        <SellerSectionCard title="About" onClick={onEdit}>
            <p className="seller-about-display">{about}</p>
        </SellerSectionCard>
    );
}

function FeaturedClientsSection({ isEditing, onCancel, onEdit, onSave }) {
    const [confirmed, setConfirmed] = useState(false);

    return (
        <SellerSectionCard className="seller-featured-card">
            <div>
                <h2>Featured clients</h2>
                <p>
                    Build credibility by featuring up to 5 clients or brands
                    your agency has worked with.
                </p>
                <button
                    className="seller-light-button"
                    type="button"
                    onClick={onEdit}
                >
                    <Icon name="plus" /> Add client
                </button>
            </div>
            <div className="seller-featured-illustration" aria-hidden="true">
                <Icon name="document" />
                <span></span>
            </div>
            {isEditing ? (
                <form
                    className="seller-inline-form seller-client-form"
                    onSubmit={(event) => {
                        event.preventDefault();
                        onSave();
                    }}
                >
                    <div className="seller-form-head">
                        <strong>
                            <Icon name="plus" /> Add client
                        </strong>
                        <button type="button" onClick={onCancel}>
                            <Icon name="close" />
                        </button>
                    </div>
                    <label>
                        <span>Client name</span>
                        <select defaultValue="">
                            <option value="" disabled>
                                Client name
                            </option>
                            <option>BDGigs</option>
                            <option>BrightCart</option>
                            <option>CloudPeak</option>
                        </select>
                    </label>
                    <label>
                        <span>Describe the work</span>
                        <textarea maxLength={400} />
                    </label>
                    <label className="seller-check-row">
                        <input
                            type="checkbox"
                            checked={confirmed}
                            onChange={(event) =>
                                setConfirmed(event.target.checked)
                            }
                        />
                        <span>
                            I confirm I have worked with this client and can
                            verify the work.
                        </span>
                    </label>
                    <div className="seller-inline-actions">
                        <button type="button" onClick={onCancel}>
                            Cancel
                        </button>
                        <button type="submit" disabled={!confirmed}>
                            Submit
                        </button>
                    </div>
                </form>
            ) : null}
        </SellerSectionCard>
    );
}

function PortfolioPreviewSection({ project, onOpen }) {
    return (
        <SellerSectionCard title="Portfolio">
            <div className="seller-portfolio-preview">
                <img
                    src={project?.image || "/assets/img/gig_images/1.png"}
                    alt="Portfolio preview"
                />
                <button type="button" onClick={onOpen}>
                    <Icon name="share" /> Start portfolio
                </button>
            </div>
        </SellerSectionCard>
    );
}

function IntroVideoSection({ isEditing, onCancel, onEdit, onSave }) {
    if (isEditing) {
        return <IntroVideoUploadForm onCancel={onCancel} onSave={onSave} />;
    }

    return (
        <SellerSectionCard className="seller-intro-video-card">
            <div>
                <h2>Intro video</h2>
                <p>Introduce yourself and make a connection with potential clients.</p>
                <button
                    className="seller-light-button"
                    type="button"
                    onClick={onEdit}
                >
                    <Icon name="plus" /> Add intro video
                </button>
            </div>
            <div className="seller-video-illustration" aria-hidden="true">
                <span>
                    <Icon name="user" />
                </span>
                <i>
                    <Icon name="play" />
                </i>
            </div>
        </SellerSectionCard>
    );
}

function IntroVideoUploadForm({ onCancel, onSave }) {
    const [videoName, setVideoName] = useState("");

    return (
        <section className="seller-intro-upload-card">
            <div className="seller-video-requirements">
                <h2>Upload your intro video</h2>
                <p>
                    Connecting over video is a great way to build credibility and
                    increase your conversion rate.
                </p>
                <strong>Video requirements:</strong>
                <ul>
                    <li>Length: 20-60 seconds</li>
                    <li>Minimum resolution: 1280x720</li>
                    <li>Aspect ratio: 16:9 (landscape)</li>
                    <li>File size: Up to 5 GB</li>
                </ul>
            </div>
            <label className="seller-video-dropzone">
                <input
                    type="file"
                    accept=".mp4,.mov,.avi"
                    onChange={(event) =>
                        setVideoName(event.target.files?.[0]?.name || "")
                    }
                />
                <Icon name="document" />
                <strong>Upload your video</strong>
                <span>
                    <u>Choose</u> a file or drop it here
                </span>
                <small>
                    You can upload the following formats: .mp4, .mov, .avi
                </small>
                {videoName ? <em>{videoName}</em> : null}
            </label>
            <div className="seller-video-review-note">
                <Icon name="document" />
                <p>
                    <strong>Your video will be manually reviewed for approval</strong>
                    To expedite the approval process, make sure your video
                    features you or a team member and follows all of our
                    guidelines before uploading.
                </p>
            </div>
            <div className="seller-upload-actions">
                <button type="button">Guidelines</button>
                <span>
                    <button type="button" onClick={onCancel}>
                        Cancel
                    </button>
                    <button
                        type="button"
                        disabled={!videoName}
                        onClick={onSave}
                    >
                        Submit video
                    </button>
                </span>
            </div>
        </section>
    );
}

function WorkExperienceSection({
    isEditing,
    openMenu,
    work,
    onActionMenuChange,
    onCancel,
    onDelete,
    onEdit,
    onSave,
}) {
    if (isEditing) {
        return (
            <SellerSectionCard title="Work experience">
                <WorkExperienceForm
                    work={work || emptyWorkExperienceDraft}
                    onCancel={onCancel}
                    onSave={onSave}
                />
            </SellerSectionCard>
        );
    }

    return (
        <SellerSectionCard title="Work experience">
            <button
                className="seller-light-button"
                type="button"
                onClick={onEdit}
            >
                <Icon name="plus" /> Add new
            </button>
            {work ? (
                <article className="seller-work-record">
                    <span>
                        <Icon name="building" />
                    </span>
                    <div>
                        <strong>{work.title}</strong>
                        <small>
                            {work.company} - {work.employmentType}
                        </small>
                        <small>
                            {work.startDate} - {work.endDate} - {work.duration}
                        </small>
                        <p>{work.description}</p>
                    </div>
                    <SellerActionDropdown
                        id="work-experience"
                        label="work experience"
                        openMenu={openMenu}
                        onDelete={onDelete}
                        onEdit={onEdit}
                        onOpenChange={onActionMenuChange}
                    />
                </article>
            ) : (
                <p className="seller-empty-note">
                    Add your work history to give clients insight into your
                    expertise.
                </p>
            )}
        </SellerSectionCard>
    );
}

function WorkExperienceForm({ work, onCancel, onSave }) {
    const [draft, setDraft] = useState(work);

    const updateDraft = (field, value) => {
        setDraft((current) => ({ ...current, [field]: value }));
    };

    return (
        <form
            className="seller-inline-form seller-work-form"
            onSubmit={(event) => {
                event.preventDefault();
                onSave(draft);
            }}
        >
            <div className="seller-form-head">
                <strong>
                    <Icon name="plus" /> Add new
                </strong>
                <button type="button" onClick={onCancel}>
                    <Icon name="close" />
                </button>
            </div>
            <input
                type="text"
                placeholder="Title"
                value={draft.title}
                onChange={(event) => updateDraft("title", event.target.value)}
            />
            <select
                value={draft.employmentType}
                onChange={(event) =>
                    updateDraft("employmentType", event.target.value)
                }
            >
                <option>Full-time</option>
                <option>Part-time</option>
                <option>Contract</option>
                <option>Freelance</option>
            </select>
            <input
                type="text"
                placeholder="Company name"
                value={draft.company}
                onChange={(event) => updateDraft("company", event.target.value)}
            />
            <label className="seller-check-row compact">
                <input type="checkbox" defaultChecked />
                <span>I currently work here</span>
            </label>
            <div className="seller-two-fields">
                <input
                    type="text"
                    placeholder="Start date"
                    value={draft.startDate}
                    onChange={(event) =>
                        updateDraft("startDate", event.target.value)
                    }
                />
                <input
                    type="text"
                    placeholder="End date"
                    value={draft.endDate}
                    onChange={(event) =>
                        updateDraft("endDate", event.target.value)
                    }
                />
            </div>
            <textarea
                maxLength={2000}
                placeholder="Add your job history and achievements to give clients insight into your expertise."
                value={draft.description}
                onChange={(event) =>
                    updateDraft("description", event.target.value)
                }
            />
            <small>{draft.description.length}/2000 characters</small>
            <select defaultValue="">
                <option value="">Skills (Optional)</option>
                {defaultSkills.map((skill) => (
                    <option key={skill}>{skill}</option>
                ))}
            </select>
            <input type="text" placeholder="Industry (Optional)" />
            <div className="seller-inline-actions">
                <button type="button" onClick={onCancel}>
                    Cancel
                </button>
                <button type="submit">Add</button>
            </div>
        </form>
    );
}

function SkillsSection({
    editingSkill,
    openMenu,
    skills,
    onActionMenuChange,
    onCancel,
    onDelete,
    onEdit,
    onNew,
    onSave,
}) {
    const activeSkill = editingSkill?.startsWith("skill:")
        ? editingSkill.replace("skill:", "")
        : "";
    const isAdding = activeSkill === "new";

    return (
        <SellerSectionCard title="Skills and expertise">
            <button
                className="seller-light-button"
                type="button"
                onClick={onNew}
            >
                <Icon name="plus" /> Add new
            </button>
            <div className="seller-skills-grid">
                {(isAdding ? [""] : []).map((item) => (
                    <SkillEditCard
                        key="new-skill"
                        onCancel={onCancel}
                        onSave={(value) => onSave("", value)}
                        skill={item}
                    />
                ))}
                {skills.map((skill) =>
                    activeSkill === skill ? (
                        <SkillEditCard
                            key={skill}
                            onCancel={onCancel}
                            onSave={(value) => onSave(skill, value)}
                            skill={skill}
                        />
                    ) : (
                        <article className="seller-skill-card" key={skill}>
                            <SellerActionDropdown
                                id={`skill-${skill}`}
                                label={skill}
                                openMenu={openMenu}
                                onDelete={() => onDelete(skill)}
                                onEdit={() => onEdit(skill)}
                                onOpenChange={onActionMenuChange}
                            />
                            <strong>{skill}</strong>
                            <span>Pro</span>
                        </article>
                    ),
                )}
            </div>
        </SellerSectionCard>
    );
}

function SkillEditCard({ skill, onCancel, onSave }) {
    const [draft, setDraft] = useState(skill || "Website development");

    return (
        <article className="seller-skill-edit-card">
            <select
                value={draft}
                onChange={(event) => setDraft(event.target.value)}
            >
                {defaultSkills.map((item) => (
                    <option key={item} value={item}>
                        {item}
                    </option>
                ))}
            </select>
            <select defaultValue="Pro">
                <option>Pro</option>
                <option>Intermediate</option>
                <option>Beginner</option>
            </select>
            <div>
                <button type="button" onClick={onCancel}>
                    Cancel
                </button>
                <button
                    type="button"
                    disabled={!draft}
                    onClick={() => onSave(draft)}
                >
                    Update
                </button>
            </div>
        </article>
    );
}

function EducationCertificationSection({
    certification,
    education,
    editingSection,
    openMenu,
    onActionMenuChange,
    onCertificationEdit,
    onCertificationSave,
    onCancel,
    onDelete,
    onEdit,
    onSave,
}) {
    const isEducationEditing = editingSection === "education";
    const isCertificationEditing = editingSection === "certification";

    return (
        <div className="seller-editor-two-col">
            <SellerSectionCard title="Education">
                <button
                    className="seller-light-button"
                    type="button"
                    onClick={onEdit}
                >
                    <Icon name="plus" /> Add new
                </button>
                {isEducationEditing ? (
                    <EducationForm
                        education={education || emptyEducationDraft}
                        onCancel={onCancel}
                        onSave={onSave}
                    />
                ) : education ? (
                    <article className="seller-education-record">
                        <Icon name="graduation" />
                        <div>
                            <strong>{education.university}</strong>
                            <p>
                                {education.degree} Degree. {education.major}
                            </p>
                            <p>
                                {education.country}, Graduated {education.year}
                            </p>
                        </div>
                        <SellerActionDropdown
                            id="education"
                            label="education"
                            openMenu={openMenu}
                            onDelete={onDelete}
                            onEdit={onEdit}
                            onOpenChange={onActionMenuChange}
                        />
                    </article>
                ) : (
                    <p className="seller-empty-note">
                        Add your education details to help clients understand
                        your background.
                    </p>
                )}
            </SellerSectionCard>

            <SellerSectionCard title="Certifications">
                {isCertificationEditing ? (
                    <CertificationForm
                        certification={certification || emptyCertificationDraft}
                        onCancel={onCancel}
                        onSave={onCertificationSave}
                    />
                ) : (
                    <>
                        <p>
                            Showcase your mastery with certifications earned in
                            your field.
                        </p>
                        {certification ? (
                            <article className="seller-certification-record">
                                <Icon name="document" />
                                <div>
                                    <strong>{certification.name}</strong>
                                    <p>
                                        {certification.provider} -{" "}
                                        {certification.year}
                                    </p>
                                </div>
                                <SellerActionDropdown
                                    id="certification"
                                    label="certification"
                                    openMenu={openMenu}
                                    onDelete={() => onCertificationSave(null)}
                                    onEdit={onCertificationEdit}
                                    onOpenChange={onActionMenuChange}
                                />
                            </article>
                        ) : null}
                        <button
                            className="seller-light-button"
                            type="button"
                            onClick={onCertificationEdit}
                        >
                            <Icon name="plus" /> Add certifications
                        </button>
                    </>
                )}
            </SellerSectionCard>
        </div>
    );
}

function CertificationForm({ certification, onCancel, onSave }) {
    const [draft, setDraft] = useState(certification);

    const updateDraft = (field, value) => {
        setDraft((current) => ({ ...current, [field]: value }));
    };

    return (
        <form
            className="seller-inline-form seller-certification-form"
            onSubmit={(event) => {
                event.preventDefault();
                onSave(draft);
            }}
        >
            <input
                type="text"
                placeholder="Certification or award"
                value={draft.name}
                onChange={(event) => updateDraft("name", event.target.value)}
            />
            <input
                type="text"
                placeholder="Certified from"
                value={draft.provider}
                onChange={(event) =>
                    updateDraft("provider", event.target.value)
                }
            />
            <select
                value={draft.year}
                onChange={(event) => updateDraft("year", event.target.value)}
            >
                {["2026", "2025", "2024", "2023", "2022", "2021", "2020"].map(
                    (year) => (
                        <option key={year}>{year}</option>
                    ),
                )}
            </select>
            <input
                type="url"
                placeholder="Credential URL (optional)"
                value={draft.credentialUrl}
                onChange={(event) =>
                    updateDraft("credentialUrl", event.target.value)
                }
            />
            <div className="seller-certification-actions">
                <button type="button" onClick={onCancel}>
                    Cancel
                </button>
                <button type="submit" disabled={!draft.name || !draft.provider}>
                    Add certification
                </button>
            </div>
        </form>
    );
}

function EducationForm({ education, onCancel, onSave }) {
    const [draft, setDraft] = useState(education);

    const updateDraft = (field, value) => {
        setDraft((current) => ({ ...current, [field]: value }));
    };

    return (
        <form
            className="seller-inline-form seller-education-form"
            onSubmit={(event) => {
                event.preventDefault();
                onSave(draft);
            }}
        >
            <select
                value={draft.country}
                onChange={(event) => updateDraft("country", event.target.value)}
            >
                <option>Bangladesh</option>
                <option>Pakistan</option>
                <option>United States</option>
                <option>United Kingdom</option>
            </select>
            <input
                type="text"
                value={draft.university}
                onChange={(event) =>
                    updateDraft("university", event.target.value)
                }
            />
            <div className="seller-two-fields">
                <select
                    value={draft.degree}
                    onChange={(event) =>
                        updateDraft("degree", event.target.value)
                    }
                >
                    <option>B.Sc.</option>
                    <option>M.Sc.</option>
                    <option>B.A.</option>
                </select>
                <input
                    type="text"
                    value={draft.major}
                    onChange={(event) =>
                        updateDraft("major", event.target.value)
                    }
                />
            </div>
            <select
                value={draft.year}
                onChange={(event) => updateDraft("year", event.target.value)}
            >
                {["2026", "2025", "2024", "2023", "2022", "2021", "2020", "2019", "2018"].map(
                    (year) => (
                        <option key={year}>{year}</option>
                    ),
                )}
            </select>
            <div className="seller-inline-actions">
                <button type="button" onClick={onCancel}>
                    Delete
                </button>
                <button type="button" onClick={onCancel}>
                    Cancel
                </button>
                <button type="submit">Update</button>
            </div>
        </form>
    );
}

function SellerProfileSummary({ profile }) {
    return (
        <section className="seller-profile-summary-card">
            <div className="seller-profile-person">
                <span className="seller-profile-avatar">
                    <img src={profile.avatar} alt={`${profile.name} profile`} />
                    <i aria-hidden="true"></i>
                </span>
                <div>
                    <h1>{profile.name}</h1>
                    <p className="seller-rating-row">
                        <Icon name="star" />
                        <strong>{profile.rating}</strong>
                        <span>({profile.reviews})</span>
                    </p>
                    <p>{profile.title}</p>
                </div>
            </div>
            <button className="seller-contact-disabled" type="button" disabled>
                Contact
            </button>
        </section>
    );
}

function PortfolioManager({
    expertiseFilter,
    filteredProjects,
    industryFilter,
    madeOnFiverrOnly,
    onCreate,
    onDelete,
    onEdit,
    onExpertiseFilterChange,
    onIndustryFilterChange,
    onMadeOnFiverrChange,
}) {
    return (
        <section className="seller-portfolio-manager">
            <div className="seller-portfolio-heading">
                <div>
                    <h2>Portfolio</h2>
                    <p>
                        Showcase your skills and experience with past projects
                        and work samples from delivered orders.
                        <span aria-label="More information">?</span>
                    </p>
                </div>
                <div className="seller-portfolio-actions">
                    <button
                        className="seller-muted-button"
                        type="button"
                        disabled
                    >
                        Reorganize
                    </button>
                    <button
                        className="seller-primary-button"
                        type="button"
                        onClick={onCreate}
                    >
                        <Icon name="plus" /> Create new project
                    </button>
                </div>
            </div>

            <div className="seller-portfolio-tools">
                <label>
                    <span className="sr-only">Industry</span>
                    <select
                        value={industryFilter}
                        onChange={(event) =>
                            onIndustryFilterChange(event.target.value)
                        }
                    >
                        <option value="">Industry</option>
                        {industries.map((industry) => (
                            <option key={industry} value={industry}>
                                {industry}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="seller-expertise-select">
                    <span className="sr-only">Search areas of expertise</span>
                    <select
                        value={expertiseFilter}
                        onChange={(event) =>
                            onExpertiseFilterChange(event.target.value)
                        }
                    >
                        <option value="">Search areas of expertise</option>
                        {expertiseAreas.map((area) => (
                            <option key={area} value={area}>
                                {area}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="seller-switch-row">
                    <input
                        type="checkbox"
                        checked={madeOnFiverrOnly}
                        onChange={(event) =>
                            onMadeOnFiverrChange(event.target.checked)
                        }
                    />
                    <span></span>
                    Made on Fiverr
                </label>
            </div>

            <p className="seller-project-count">
                Showing {filteredProjects.length}{" "}
                {filteredProjects.length === 1 ? "project" : "projects"}
            </p>

            {filteredProjects.length ? (
                <div className="seller-project-grid">
                    {filteredProjects.map((project) => (
                        <PortfolioProjectCard
                            key={project.id}
                            project={project}
                            onDelete={() => onDelete(project.id)}
                            onEdit={() => onEdit(project)}
                        />
                    ))}
                </div>
            ) : (
                <div className="seller-empty-projects">
                    <strong>No matching projects yet.</strong>
                    <p>
                        Clear the filters or add a new project to show your best
                        work here.
                    </p>
                    <button type="button" onClick={onCreate}>
                        <Icon name="plus" /> Create new project
                    </button>
                </div>
            )}
        </section>
    );
}

function PortfolioProjectCard({ project, onDelete, onEdit }) {
    return (
        <article className="seller-project-card">
            <div className="seller-project-media">
                <img src={project.image} alt="" />
                <div className="seller-project-card-actions">
                    <button type="button" onClick={onEdit}>
                        Edit
                    </button>
                    <button
                        type="button"
                        aria-label={`Delete ${project.name}`}
                        onClick={onDelete}
                    >
                        <Icon name="trash" />
                    </button>
                </div>
                <span>
                    <Icon name="camera" />
                    {project.mediaCount}
                </span>
            </div>
            <h3>{project.name}</h3>
            <p>{project.description}</p>
        </article>
    );
}

function ProjectFormView({
    editing,
    form,
    profile,
    step,
    onCancel,
    onContinue,
    onStepChange,
    onUpdate,
}) {
    const title = editing
        ? "Edit your portfolio project"
        : "Add a new project to your portfolio";
    const canContinue =
        step === 2 ||
        Boolean(
            form.name.trim() &&
                form.industry &&
                form.duration &&
                form.cost.trim() &&
                form.description.trim(),
        );

    return (
        <main className="dashboard-content portfolio-project-builder">
            <header className="project-builder-topbar">
                <nav aria-label="Project creation steps">
                    <button
                        className={step === 1 ? "is-active" : ""}
                        type="button"
                        onClick={() => onStepChange(1)}
                    >
                        <span>1</span>
                        Create project
                    </button>
                    <i aria-hidden="true">&gt;</i>
                    <button
                        className={step === 2 ? "is-active" : ""}
                        type="button"
                        onClick={() => canContinue && onStepChange(2)}
                    >
                        <span>2</span>
                        Link to catalog
                    </button>
                </nav>
            </header>

            {step === 1 ? (
                <ProjectDetailsForm
                    form={form}
                    profile={profile}
                    title={title}
                    onUpdate={onUpdate}
                />
            ) : (
                <CatalogLinkStep form={form} onUpdate={onUpdate} />
            )}

            <footer className="project-builder-footer">
                <button type="button" onClick={onCancel}>
                    Cancel
                </button>
                <button
                    className="seller-primary-button"
                    type="button"
                    disabled={!canContinue}
                    onClick={onContinue}
                >
                    {step === 1 ? "Continue" : "Save project"}
                </button>
            </footer>
        </main>
    );
}

function ProjectDetailsForm({ form, profile, title, onUpdate }) {
    return (
        <section className="project-details-form">
            <h1>{title}</h1>
            <label className="project-form-field compact">
                <span>Project name</span>
                <small>Create a clear, descriptive name for your project.</small>
                <input
                    maxLength={50}
                    type="text"
                    placeholder="Nike Women's Division - Fall Marketing Campaign."
                    value={form.name}
                    onChange={(event) => onUpdate("name", event.target.value)}
                />
                <em>{form.name.length}/50 characters</em>
            </label>

            <label className="project-form-field">
                <span>Industry</span>
                <small>Select at most 6.</small>
                <select
                    value={form.industry}
                    onChange={(event) =>
                        onUpdate("industry", event.target.value)
                    }
                >
                    <option value="">Select an industry from the list.</option>
                    {industries.map((industry) => (
                        <option key={industry} value={industry}>
                            {industry}
                        </option>
                    ))}
                </select>
            </label>

            <div className="project-form-grid">
                <label className="project-form-field">
                    <span>Project duration</span>
                    <select
                        value={form.duration}
                        onChange={(event) =>
                            onUpdate("duration", event.target.value)
                        }
                    >
                        <option value="">Select duration.</option>
                        {projectDurations.map((duration) => (
                            <option key={duration} value={duration}>
                                {duration}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="project-form-field">
                    <span>Project cost</span>
                    <input
                        type="text"
                        placeholder="$ Ex. 1000"
                        value={form.cost ? `$ ${form.cost}` : ""}
                        onChange={(event) =>
                            onUpdate(
                                "cost",
                                event.target.value.replace(/[^0-9]/g, ""),
                            )
                        }
                    />
                </label>
            </div>

            <fieldset className="project-started-field">
                <legend>Project started on</legend>
                <label>
                    <span className="sr-only">Month</span>
                    <select
                        value={form.startedMonth}
                        onChange={(event) =>
                            onUpdate("startedMonth", event.target.value)
                        }
                    >
                        <option value="">MM</option>
                        {months.map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                </label>
                <label>
                    <span className="sr-only">Year</span>
                    <select
                        value={form.startedYear}
                        onChange={(event) =>
                            onUpdate("startedYear", event.target.value)
                        }
                    >
                        <option value="">YY</option>
                        {years.map((year) => (
                            <option key={year} value={year}>
                                {year}
                            </option>
                        ))}
                    </select>
                </label>
            </fieldset>

            <label className="project-form-field">
                <span>Area of expertise</span>
                <select
                    value={form.expertise}
                    onChange={(event) =>
                        onUpdate("expertise", event.target.value)
                    }
                >
                    <option value="">Select the strongest expertise area.</option>
                    {expertiseAreas.map((area) => (
                        <option key={area} value={area}>
                            {area}
                        </option>
                    ))}
                </select>
            </label>

            <label className="project-form-field">
                <span>Project description</span>
                <small>
                    Use this space to share about your client, their goals, any
                    challenges that came up, and how you dealt with them.
                </small>
                <span className="project-textarea-wrap">
                    <textarea
                        maxLength={1400}
                        value={form.description}
                        onChange={(event) =>
                            onUpdate("description", event.target.value)
                        }
                    />
                    <button type="button" aria-label="Improve description">
                        <Icon name="spark" />
                    </button>
                </span>
                <em>{form.description.length}/1400 characters</em>
            </label>

            <div className="project-attachments">
                <strong>Attachments</strong>
                <p>
                    Keep in mind that the first file you upload will appear in
                    the thumbnail preview. We recommend the size 1024x768 with
                    aspect ratio 4:3. Not sure what to upload?
                    <a href="#attachment-suggestions"> Check out our suggestions</a>
                </p>
                <label className="project-upload-box">
                    <input
                        type="file"
                        accept=".jpg,.jpeg,.png,.gif,.mp4,.avi"
                        multiple
                        onChange={(event) => {
                            const file = event.target.files?.[0];
                            if (!file) {
                                return;
                            }

                            onUpdate("attachmentName", file.name);
                            onUpdate("mediaCount", event.target.files.length);

                            if (file.type.startsWith("image/")) {
                                const reader = new FileReader();
                                reader.onload = () => {
                                    onUpdate("image", reader.result || "");
                                };
                                reader.readAsDataURL(file);
                            }
                        }}
                    />
                    <span>Drag and drop files or</span>
                    <b>
                        <Icon name="document" /> Choose files
                    </b>
                    <small>
                        You can upload the following formats:
                        <br />
                        .jpg, .jpeg, .png, .gif, .mp4, .avi; max size - 50 MB;
                        max files - 5
                    </small>
                    {form.attachmentName ? <em>{form.attachmentName}</em> : null}
                </label>
            </div>

            <p className="project-form-owner">
                This project will appear on {profile.name}'s seller profile.
            </p>
        </section>
    );
}

function CatalogLinkStep({ form, onUpdate }) {
    return (
        <section className="catalog-link-step">
            <div>
                <h1>Link this project to a service</h1>
                <p>
                    Connect the portfolio project to a catalog service so buyers
                    can move from your work sample to the right offer.
                </p>
            </div>

            <label className="catalog-service-option">
                <input
                    type="radio"
                    name="linkedCatalog"
                    checked={form.linkedCatalog === "Full Stack Web Applications"}
                    onChange={() =>
                        onUpdate(
                            "linkedCatalog",
                            "Full Stack Web Applications",
                        )
                    }
                />
                <img src="/assets/img/gig_images/4.png" alt="" />
                <span>
                    <strong>Full Stack Web Applications</strong>
                    <small>
                        I will web application, software development, full stack
                        website development
                    </small>
                </span>
            </label>

            <label className="catalog-service-option">
                <input
                    type="radio"
                    name="linkedCatalog"
                    checked={form.linkedCatalog === "WordPress Customization"}
                    onChange={() =>
                        onUpdate("linkedCatalog", "WordPress Customization")
                    }
                />
                <img src="/assets/img/gig_images/8.png" alt="" />
                <span>
                    <strong>WordPress Customization</strong>
                    <small>
                        I will create, redesign, or customize a WordPress
                        website for your business
                    </small>
                </span>
            </label>
        </section>
    );
}

export default SellerProfileManagerPage;
