import { useEffect, useMemo, useState } from "react";
import { Icon } from "../../common/Icons.jsx";
import { apiRequest } from "../../../api/apiClient.js";

const initialClientProfile = {
    name: "",
    handle: "",
    username: "",
    avatar: "",
    location: "",
    joined: "",
    overview: "",
    workingDays: {
        start: "",
        end: "",
    },
    workingHours: {
        start: "",
        end: "",
    },
    timezone: "UTC",
    languages: [],
};

const timeOptions = [
    "8:00 AM",
    "9:00 AM",
    "10:00 AM",
    "11:00 AM",
    "12:00 PM",
    "1:00 PM",
    "2:00 PM",
    "3:00 PM",
    "4:00 PM",
    "5:00 PM",
    "6:00 PM",
    "7:00 PM",
    "8:00 PM",
];

const languageOptions = [
    "English",
    "Bengali",
    "French",
    "Spanish",
    "German",
    "Hindi",
    "Arabic",
];

const proficiencyOptions = [
    "Basic",
    "Conversational",
    "Fluent",
    "Native/Bilingual",
];

const communicationPreferenceOptions = [
    "Inbox messages",
    "Video calls",
    "Project brief only",
];

function BuyerClientProfilePage() {
    const [profile, setProfile] = useState(initialClientProfile);
    const [activeDrawer, setActiveDrawer] = useState("");
    const [notice, setNotice] = useState("");

    useEffect(() => {
        apiRequest("/api/user/profile/buyer")
            .then(setProfile)
            .catch((error) =>
                setNotice(error.message || "Profile could not be loaded."),
            );
    }, []);

    useEffect(() => {
        if (!activeDrawer) {
            return undefined;
        }

        const handleKeyDown = (event) => {
            if (event.key === "Escape") {
                setActiveDrawer("");
            }
        };

        document.body.classList.add("has-client-profile-drawer");
        window.addEventListener("keydown", handleKeyDown);

        return () => {
            document.body.classList.remove("has-client-profile-drawer");
            window.removeEventListener("keydown", handleKeyDown);
        };
    }, [activeDrawer]);

    const drawerTitle = useMemo(() => {
        if (activeDrawer === "identity") {
            return "Edit your name and photo";
        }

        if (activeDrawer === "communication") {
            return "Edit your communication preferences";
        }

        if (activeDrawer === "overview") {
            return "Edit your overview";
        }

        return "";
    }, [activeDrawer]);

    const updateProfile = (updates, shouldClose = true) => {
        setProfile((current) => ({ ...current, ...updates }));
        apiRequest("/api/user/profile/buyer", {
            method: "PATCH",
            body: updates,
        })
            .then(setProfile)
            .catch((error) =>
                setNotice(error.message || "Profile could not be saved."),
            );
        if (shouldClose) {
            setActiveDrawer("");
        }
    };

    return (
        <main className="dashboard-content buyer-profile-page">
            <nav className="buyer-profile-breadcrumb" aria-label="Breadcrumb">
                <a href="/">Home</a>
                <span>/</span>
                <span>Client profile</span>
            </nav>

            <header className="buyer-profile-top">
                <div className="buyer-profile-intro">
                    <p>
                        Build your <strong>client profile</strong> on bdgigs.
                    </p>
                    <p>
                        Are you a freelancer? Visit your{" "}
                        <a href="/dashboard/seller/profile">
                            freelancer profile
                        </a>{" "}
                        to view and update it.
                    </p>
                </div>
                <a className="buyer-public-view" href="/dashboard/profile">
                    <Icon name="eye" /> Public view
                </a>
                <BuyerProfileProgress />
            </header>

            <div className="buyer-profile-layout">
                <div className="buyer-profile-main">
                    <BuyerIdentityCard
                        profile={profile}
                        onEdit={() => setActiveDrawer("identity")}
                    />

                    <BuyerOverviewCard
                        overview={profile.overview}
                        onEdit={() => setActiveDrawer("overview")}
                    />

                    <BuyerCommunicationCard
                        profile={profile}
                        onEdit={() => setActiveDrawer("communication")}
                    />

                    <section className="buyer-profile-card buyer-reviews-card">
                        <h2>Reviews from freelancers</h2>
                        <div className="buyer-review-empty" aria-hidden="true">
                            <span>*****</span>
                        </div>
                    </section>
                </div>

                <aside className="buyer-profile-sidebar">
                    <section className="buyer-profile-card buyer-progress-card-mobile">
                        <BuyerProfileProgress />
                    </section>
                    <section className="buyer-profile-card buyer-quick-links">
                        <h2>Quick links</h2>
                        <a href="/dashboard/briefs">
                            <Icon name="document" />
                            Briefs
                        </a>
                        <a href="/dashboard/orders">
                            <Icon name="orders" />
                            Orders
                        </a>
                        <a href="/dashboard/saved-services">
                            <Icon name="heart" />
                            Lists
                        </a>
                    </section>
                </aside>
            </div>

            {notice ? <p className="account-settings-notice">{notice}</p> : null}

            {activeDrawer ? (
                <ClientProfileDrawer
                    title={drawerTitle}
                    onClose={() => setActiveDrawer("")}
                >
                    {activeDrawer === "identity" ? (
                        <IdentityEditForm
                            profile={profile}
                            onCancel={() => setActiveDrawer("")}
                            onSave={updateProfile}
                        />
                    ) : null}
                    {activeDrawer === "overview" ? (
                        <OverviewEditForm
                            overview={profile.overview}
                            onCancel={() => setActiveDrawer("")}
                            onSave={(overview) => updateProfile({ overview })}
                        />
                    ) : null}
                    {activeDrawer === "communication" ? (
                        <CommunicationEditForm
                            profile={profile}
                            onSave={(updates) => updateProfile(updates, false)}
                        />
                    ) : null}
                </ClientProfileDrawer>
            ) : null}
        </main>
    );
}

function BuyerProfileProgress() {
    return (
        <section className="buyer-progress-card">
            <div className="buyer-progress-bar-head">
                <span>0%</span>
                <strong>35%</strong>
                <span>100%</span>
            </div>
            <div className="buyer-progress-track">
                <span></span>
            </div>
            <p>
                You're nearly there, add a few more details to complete your
                profile.
            </p>
        </section>
    );
}

function BuyerIdentityCard({ profile, onEdit }) {
    return (
        <section className="buyer-profile-card buyer-identity-card">
            <button type="button" onClick={onEdit}>
                Edit
            </button>
            <img src={profile.avatar} alt={`${profile.name} profile`} />
            <div>
                <h1>{profile.name}</h1>
                <span>{profile.handle}</span>
                <p>
                    <span>
                        <Icon name="location" />
                        {profile.location}
                    </span>
                    <span>
                        <Icon name="user" />
                        {profile.joined}
                    </span>
                </p>
            </div>
        </section>
    );
}

function BuyerOverviewCard({ overview, onEdit }) {
    return (
        <section className="buyer-profile-card buyer-overview-card">
            <div>
                <h2>Overview</h2>
                <p>
                    {overview ||
                        "Share details about yourself, your business, and what services you're looking to order."}
                </p>
                <button type="button" onClick={onEdit}>
                    {overview ? "Edit" : "Add"}
                </button>
            </div>
            <BuyerOverviewIllustration />
        </section>
    );
}

function BuyerOverviewIllustration() {
    return (
        <div className="buyer-overview-illustration" aria-hidden="true">
            <span className="buyer-illustration-card">
                <i></i>
                <b></b>
            </span>
            <span className="buyer-illustration-paper one"></span>
            <span className="buyer-illustration-paper two"></span>
        </div>
    );
}

function BuyerCommunicationCard({ profile, onEdit }) {
    const preferredHours =
        profile.workingDays.start &&
        profile.workingDays.end &&
        profile.workingHours.start &&
        profile.workingHours.end
            ? `${profile.workingDays.start}-${profile.workingDays.end}, ${profile.workingHours.start}-${profile.workingHours.end}`
            : "";

    return (
        <section className="buyer-profile-card buyer-communication-card">
            <button type="button" onClick={onEdit}>
                Edit
            </button>
            <h2>Communication preferences</h2>
            <div className="buyer-communication-grid">
                <div>
                    <h3>Speaks</h3>
                    {profile.languages.map((language) => (
                        <p key={language.id}>
                            <strong>{language.language}</strong>{" "}
                            <span>({language.level})</span>
                        </p>
                    ))}
                </div>
                <div>
                    <h3>Preferred hours</h3>
                    <button
                        className="buyer-link-button"
                        type="button"
                        onClick={onEdit}
                    >
                        {preferredHours || "Add preferred hours"}
                    </button>
                </div>
            </div>
        </section>
    );
}

function ClientProfileDrawer({ title, children, onClose }) {
    return (
        <div className="client-profile-drawer-layer" role="presentation">
            <button
                className="client-profile-drawer-backdrop"
                type="button"
                aria-label="Close profile editor"
                onClick={onClose}
            ></button>
            <aside
                className="client-profile-drawer"
                role="dialog"
                aria-modal="true"
                aria-labelledby="clientProfileDrawerTitle"
            >
                <header>
                    <h2 id="clientProfileDrawerTitle">{title}</h2>
                    <button
                        type="button"
                        aria-label="Close"
                        onClick={onClose}
                    >
                        <Icon name="close" />
                    </button>
                </header>
                {children}
            </aside>
        </div>
    );
}

function IdentityEditForm({ profile, onCancel, onSave }) {
    const [draft, setDraft] = useState({
        name: profile.name,
        avatar: profile.avatar,
    });

    const handleFileChange = (event) => {
        const file = event.target.files?.[0];
        if (!file || !file.type.startsWith("image/")) {
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            setDraft((current) => ({
                ...current,
                avatar: reader.result || current.avatar,
            }));
        };
        reader.readAsDataURL(file);
    };

    return (
        <form
            className="client-drawer-form"
            onSubmit={(event) => {
                event.preventDefault();
                onSave(draft);
            }}
        >
            <div className="client-drawer-body">
                <p className="client-drawer-intro">
                    Sharing these details makes it easy to introduce yourself to
                    others and helps create authentic connections.
                </p>

                <label className="client-photo-upload">
                    <input
                        type="file"
                        accept="image/*"
                        onChange={handleFileChange}
                    />
                    <img src={draft.avatar} alt="" />
                    <span>
                        <Icon name="camera" />
                    </span>
                </label>

                <label className="client-drawer-field">
                    <span>Choose your display name</span>
                    <input
                        type="text"
                        value={draft.name}
                        onChange={(event) =>
                            setDraft((current) => ({
                                ...current,
                                name: event.target.value,
                            }))
                        }
                    />
                    <small>
                        This is the name people will see on Fiverr, including in
                        inbox messages and orders.
                    </small>
                </label>

                <label className="client-drawer-field">
                    <span>Username</span>
                    <span className="client-locked-input">
                        <input type="text" value={profile.username} disabled />
                        <Icon name="settings" />
                    </span>
                </label>
            </div>

            <DrawerActions onCancel={onCancel} />
        </form>
    );
}

function OverviewEditForm({ overview, onCancel, onSave }) {
    const [draft, setDraft] = useState(overview);

    return (
        <form
            className="client-drawer-form"
            onSubmit={(event) => {
                event.preventDefault();
                onSave(draft);
            }}
        >
            <div className="client-drawer-body">
                <p className="client-drawer-intro">
                    Tell freelancers what kind of work you order, your business
                    goals, and how you like to collaborate.
                </p>
                <label className="client-drawer-field">
                    <span>Overview</span>
                    <textarea
                        maxLength={600}
                        value={draft}
                        placeholder="Example: I run a growing ecommerce business and often hire freelancers for website, marketing, and design projects."
                        onChange={(event) => setDraft(event.target.value)}
                    />
                    <small>{draft.length}/600 characters</small>
                </label>
            </div>

            <DrawerActions onCancel={onCancel} />
        </form>
    );
}

function CommunicationEditForm({ profile, onSave }) {
    const [draft, setDraft] = useState({
        workingHours: profile.workingHours,
        languages: profile.languages,
    });
    const [activeLanguageMenu, setActiveLanguageMenu] = useState("");
    const [editingLanguageId, setEditingLanguageId] = useState("");
    const [languageFormOpen, setLanguageFormOpen] = useState(true);
    const [preferenceTouched, setPreferenceTouched] = useState(true);
    const [languageDraft, setLanguageDraft] = useState({
        language: "Bengali",
        level: "Native/Bilingual",
        communicationPreference: "",
    });

    const commitDraft = (nextDraft) => {
        setDraft(nextDraft);
        onSave(nextDraft);
    };

    const updateWorkingHours = (field, value) => {
        const nextDraft = {
            ...draft,
            workingHours: {
                ...draft.workingHours,
                [field]: value,
            },
        };
        commitDraft(nextDraft);
    };

    const openNewLanguageForm = () => {
        setEditingLanguageId("");
        setLanguageDraft({
            language: "Bengali",
            level: "Native/Bilingual",
            communicationPreference: "",
        });
        setPreferenceTouched(true);
        setLanguageFormOpen(true);
        setActiveLanguageMenu("");
    };

    const openEditLanguageForm = (language) => {
        setEditingLanguageId(language.id);
        setLanguageDraft({
            language: language.language,
            level: language.level,
            communicationPreference: language.communicationPreference || "",
        });
        setPreferenceTouched(false);
        setLanguageFormOpen(true);
        setActiveLanguageMenu("");
    };

    const deleteLanguage = (languageId) => {
        const nextDraft = {
            ...draft,
            languages: draft.languages.filter(
                (language) => language.id !== languageId,
            ),
        };
        commitDraft(nextDraft);
        setActiveLanguageMenu("");
    };

    const saveLanguage = () => {
        if (!languageDraft.communicationPreference) {
            setPreferenceTouched(true);
            return;
        }

        const languagePayload = {
            id: editingLanguageId || `language-${Date.now()}`,
            ...languageDraft,
        };
        const nextDraft = editingLanguageId
            ? {
                  ...draft,
                  languages: draft.languages.map((language) =>
                      language.id === editingLanguageId
                          ? languagePayload
                          : language,
                  ),
              }
            : {
                  ...draft,
                  languages: [...draft.languages, languagePayload],
              };

        commitDraft(nextDraft);
        setLanguageFormOpen(false);
        setEditingLanguageId("");
    };

    const cancelLanguageForm = () => {
        setLanguageFormOpen(false);
        setEditingLanguageId("");
        setPreferenceTouched(false);
    };

    const updateLanguageDraft = (field, value) => {
        setLanguageDraft((current) => ({
            ...current,
            [field]: value,
        }));

        if (field === "communicationPreference") {
            setPreferenceTouched(true);
        }
    };

    return (
        <div className="client-drawer-form client-communication-editor">
            <div className="client-drawer-body">
                <div className="client-drawer-field">
                    <span>Working hours</span>
                    <div className="client-drawer-inline-fields">
                        <select
                            value={draft.workingHours.start}
                            onChange={(event) =>
                                updateWorkingHours(
                                    "start",
                                    event.target.value,
                                )
                            }
                        >
                            <option value="">Start time</option>
                            {timeOptions.map((time) => (
                                <option key={time} value={time}>
                                    {time}
                                </option>
                            ))}
                        </select>
                        <i aria-hidden="true">-</i>
                        <select
                            value={draft.workingHours.end}
                            onChange={(event) =>
                                updateWorkingHours(
                                    "end",
                                    event.target.value,
                                )
                            }
                        >
                            <option value="">End time</option>
                            {timeOptions.map((time) => (
                                <option key={time} value={time}>
                                    {time}
                                </option>
                            ))}
                        </select>
                    </div>
                    <small className="client-timezone-row">
                        <span></span>
                        {profile.timezone}
                    </small>
                </div>

                <div className="client-language-list">
                    {draft.languages.map((language) => (
                        <article key={language.id}>
                            <span>
                                <strong>{language.language}</strong>
                                <small>{language.level}</small>
                            </span>
                            <button
                                className="client-language-menu-trigger"
                                type="button"
                                aria-expanded={
                                    activeLanguageMenu === language.id
                                }
                                aria-label={`More actions for ${language.language}`}
                                onClick={() =>
                                    setActiveLanguageMenu((current) =>
                                        current === language.id
                                            ? ""
                                            : language.id,
                                    )
                                }
                            >
                                <Icon name="moreHorizontal" />
                            </button>
                            {activeLanguageMenu === language.id ? (
                                <div
                                    className="client-language-menu"
                                    role="menu"
                                >
                                    <button
                                        type="button"
                                        role="menuitem"
                                        onClick={() =>
                                            openEditLanguageForm(language)
                                        }
                                    >
                                        <Icon name="edit" /> Edit
                                    </button>
                                    <button
                                        type="button"
                                        role="menuitem"
                                        onClick={() =>
                                            deleteLanguage(language.id)
                                        }
                                    >
                                        <Icon name="trash" /> Delete
                                    </button>
                                </div>
                            ) : null}
                        </article>
                    ))}
                </div>

                {languageFormOpen ? (
                    <LanguageInlineForm
                        draft={languageDraft}
                        editing={Boolean(editingLanguageId)}
                        preferenceTouched={preferenceTouched}
                        onCancel={cancelLanguageForm}
                        onChange={updateLanguageDraft}
                        onSubmit={saveLanguage}
                    />
                ) : (
                    <button
                        className="client-add-language"
                        type="button"
                        onClick={openNewLanguageForm}
                    >
                        <Icon name="plus" /> Add language
                    </button>
                )}
            </div>
        </div>
    );
}

function LanguageInlineForm({
    draft,
    editing,
    preferenceTouched,
    onCancel,
    onChange,
    onSubmit,
}) {
    const hasPreferenceError =
        preferenceTouched && !draft.communicationPreference;

    return (
        <section className="client-language-editor-card">
            <label className="client-drawer-field">
                <span>Language</span>
                <select
                    value={draft.language}
                    onChange={(event) =>
                        onChange("language", event.target.value)
                    }
                >
                    {languageOptions.map((language) => (
                        <option key={language} value={language}>
                            {language}
                        </option>
                    ))}
                </select>
            </label>
            <label className="client-drawer-field">
                <span>Proficiency</span>
                <select
                    value={draft.level}
                    onChange={(event) => onChange("level", event.target.value)}
                >
                    {proficiencyOptions.map((level) => (
                        <option key={level} value={level}>
                            {level}
                        </option>
                    ))}
                </select>
            </label>
            <label
                className={`client-drawer-field${
                    hasPreferenceError ? " has-error" : ""
                }`}
            >
                <span>Communication method preference</span>
                <select
                    value={draft.communicationPreference}
                    onChange={(event) =>
                        onChange(
                            "communicationPreference",
                            event.target.value,
                        )
                    }
                >
                    <option value="">Communication preference</option>
                    {communicationPreferenceOptions.map((preference) => (
                        <option key={preference} value={preference}>
                            {preference}
                        </option>
                    ))}
                </select>
                {hasPreferenceError ? (
                    <small className="client-field-error">
                        This is a required field.
                    </small>
                ) : null}
            </label>
            <div className="client-language-editor-actions">
                <button type="button" onClick={onCancel}>
                    Cancel
                </button>
                <button type="button" onClick={onSubmit}>
                    {editing ? "Save" : "Add"}
                </button>
            </div>
        </section>
    );
}

function DrawerActions({ onCancel }) {
    return (
        <footer className="client-drawer-actions">
            <button type="button" onClick={onCancel}>
                Cancel
            </button>
            <button type="submit">Save changes</button>
        </footer>
    );
}

export default BuyerClientProfilePage;
