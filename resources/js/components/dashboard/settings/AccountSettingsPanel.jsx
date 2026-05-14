import { useMemo, useState } from "react";
import { Link, Navigate, useParams } from "react-router-dom";
import {
    connectedDevice,
    notificationRows,
    personalInfoRows,
    securityRows,
    settingsHubCards,
    settingsPageTitles,
    settingsProfiles,
} from "../../../data/settingsPageData.js";
import { Icon } from "../../common/Icons.jsx";
import { useTranslation } from "react-i18next";
function AccountSettingsPanel({ onNavigate, variant = "buyer" }) {
    const { t } = useTranslation();
    const { settingsPage } = useParams();
    const profile = settingsProfiles[variant] || settingsProfiles.buyer;
    const basePath =
        variant === "seller"
            ? "/dashboard/seller/settings"
            : "/dashboard/settings";
    const activePage = settingsPage || "overview";
    const [notice, setNotice] = useState("");
    const [twoFactorEnabled, setTwoFactorEnabled] = useState(false);
    const [realTimeEnabled, setRealTimeEnabled] = useState(true);
    const [soundEnabled, setSoundEnabled] = useState(true);
    const [notificationPrefs, setNotificationPrefs] = useState(() =>
        buildNotificationState(notificationRows[variant]),
    );
    const personalRows = useMemo(
        () =>
            personalInfoRows.map((row) => ({
                ...row,
                value: row.field ? profile[row.field] : row.value,
            })),
        [profile],
    );
    const handleNotificationChange = (rowId, channel) => {
        setNotificationPrefs((current) => ({
            ...current,
            [rowId]: {
                ...current[rowId],
                [channel]: !current[rowId][channel],
            },
        }));
    };
    const openProfile = (event) => {
        event.preventDefault();
        onNavigate(profile.profilePage);
    };
    if (settingsPage && !settingsPageTitles[settingsPage]) {
        return <Navigate to={basePath} replace />;
    }
    return (
        <main className="dashboard-content account-settings-page" id="top">
            {activePage === "overview" ? (
                <header className="account-settings-header">
                    <div>
                        <h1>
                            {t(
                                "components.dashboard.settings.accountsettingspanel.accountSettings",
                            )}
                        </h1>
                        <p>
                            {profile.name} ({profile.email})
                        </p>
                    </div>
                    <a href={profile.profilePath} onClick={openProfile}>
                        {profile.profileLabel}
                    </a>
                </header>
            ) : null}

            {notice ? (
                <p className="account-settings-notice">{notice}</p>
            ) : null}

            {activePage === "overview" ? (
                <SettingsHubCards basePath={basePath} />
            ) : null}
            {activePage === "personal-information" ? (
                <PersonalInformationSection
                    basePath={basePath}
                    rows={personalRows}
                    onAction={(label) =>
                        setNotice(`${label} settings are ready to edit.`)
                    }
                />
            ) : null}
            {activePage === "account-security" ? (
                <AccountSecuritySection
                    basePath={basePath}
                    twoFactorEnabled={twoFactorEnabled}
                    onSave={() => setNotice("Security changes saved.")}
                    onToggleTwoFactor={() =>
                        setTwoFactorEnabled((enabled) => !enabled)
                    }
                    onAction={(label) =>
                        setNotice(`${label} settings are ready to edit.`)
                    }
                />
            ) : null}
            {activePage === "notifications" ? (
                <NotificationPreferencesSection
                    basePath={basePath}
                    intro={profile.notificationIntro}
                    notificationPrefs={notificationPrefs}
                    rows={notificationRows[variant]}
                    onNotificationChange={handleNotificationChange}
                    realTimeEnabled={realTimeEnabled}
                    soundEnabled={soundEnabled}
                    onToggleRealtime={() =>
                        setRealTimeEnabled((enabled) => !enabled)
                    }
                    onToggleSound={() => setSoundEnabled((enabled) => !enabled)}
                    onSave={() =>
                        setNotice("Notification preferences updated.")
                    }
                    onTry={() =>
                        setNotice("Real-time notification preview sent.")
                    }
                />
            ) : null}
            {activePage === "identity-verification" ? (
                <IdentityVerificationSection
                    basePath={basePath}
                    profile={profile}
                    onBack={() => setNotice("Returned to account settings.")}
                    onContinue={() => setNotice("Verification flow opened.")}
                />
            ) : null}
        </main>
    );
}
function SettingsHubCards({ basePath }) {
    const { t } = useTranslation();
    return (
        <section
            className="settings-hub-grid"
            aria-label={t(
                "components.dashboard.settings.accountsettingspanel.accountSettingsCategories",
            )}
        >
            {settingsHubCards.map((card) => (
                <Link
                    className="settings-hub-card"
                    to={`${basePath}/${card.id}`}
                    key={card.id}
                >
                    <Icon name={card.icon} />
                    <strong>{card.title}</strong>
                    <span>{card.description}</span>
                </Link>
            ))}
        </section>
    );
}
function PersonalInformationSection({ basePath, rows, onAction }) {
    const { t } = useTranslation();
    return (
        <section
            className="account-settings-section personal-info-section"
            aria-labelledby="personalInfoTitle"
        >
            <SectionBacklink basePath={basePath} />
            <h2 id="personalInfoTitle">
                {t(
                    "components.dashboard.settings.accountsettingspanel.personalInformation",
                )}
            </h2>
            <div className="personal-info-list">
                {rows.map((row) => (
                    <article className="personal-info-row" key={row.label}>
                        <div>
                            <strong>{row.label}</strong>
                            {row.value ? <span>{row.value}</span> : null}
                        </div>
                        <button
                            className={row.danger ? "danger-link" : ""}
                            type="button"
                            onClick={() => onAction(row.label)}
                        >
                            {row.action}
                        </button>
                    </article>
                ))}
            </div>
        </section>
    );
}
function AccountSecuritySection({
    basePath,
    twoFactorEnabled,
    onAction,
    onSave,
    onToggleTwoFactor,
}) {
    const { t } = useTranslation();
    return (
        <section
            className="account-settings-section security-settings-section"
            aria-labelledby="securityTitle"
        >
            <SectionBacklink basePath={basePath} />
            <h2 id="securityTitle">
                {t(
                    "components.dashboard.settings.accountsettingspanel.accountSecurity",
                )}
            </h2>
            <form
                className="security-password-form"
                onSubmit={(event) => {
                    event.preventDefault();
                    onSave();
                }}
            >
                <h3>
                    {t(
                        "components.dashboard.settings.accountsettingspanel.changePassword",
                    )}
                </h3>
                <label>
                    <span>
                        {t(
                            "components.dashboard.settings.accountsettingspanel.currentPassword",
                        )}
                    </span>
                    <input type="password" autoComplete="current-password" />
                </label>
                <label>
                    <span>
                        {t(
                            "components.dashboard.settings.accountsettingspanel.newPassword",
                        )}
                    </span>
                    <input type="password" autoComplete="new-password" />
                </label>
                <label>
                    <span>
                        {t(
                            "components.dashboard.settings.accountsettingspanel.confirmPassword",
                        )}
                    </span>
                    <div>
                        <input type="password" autoComplete="new-password" />
                        <small>
                            {t(
                                "components.dashboard.settings.accountsettingspanel.8CharactersOrLongerCombineUpperAndLowercase",
                            )}
                        </small>
                    </div>
                </label>
                <button className="settings-primary-button" type="submit">
                    {" "}
                    {t(
                        "components.dashboard.settings.accountsettingspanel.saveChanges",
                    )}{" "}
                </button>
            </form>

            <div className="security-option-list">
                {securityRows.map((row) => (
                    <article className="security-option-row" key={row.title}>
                        <strong>{row.title}</strong>
                        <p>{row.description}</p>
                        <button
                            type="button"
                            onClick={() => onAction(row.title)}
                        >
                            {row.action}
                        </button>
                    </article>
                ))}
                <article className="security-option-row two-factor-row">
                    <strong>
                        {" "}
                        {t(
                            "components.dashboard.settings.accountsettingspanel.twoFactorAuthentication",
                        )}{" "}
                        <span>
                            {t(
                                "components.dashboard.settings.accountsettingspanel.recommended",
                            )}
                        </span>
                    </strong>
                    <div>
                        <SettingsSwitch
                            enabled={twoFactorEnabled}
                            label="Toggle two factor authentication"
                            onToggle={onToggleTwoFactor}
                        />
                        <p>
                            {" "}
                            {t(
                                "components.dashboard.settings.accountsettingspanel.toHelpKeepYourAccountSecureWellAsk",
                            )}{" "}
                        </p>
                    </div>
                </article>
            </div>

            <section
                className="connected-device-panel"
                aria-labelledby="connectedDeviceTitle"
            >
                <h3 id="connectedDeviceTitle">
                    {t(
                        "components.dashboard.settings.accountsettingspanel.connectedDevices",
                    )}
                </h3>
                <article>
                    <Icon name="dashboard" />
                    <div>
                        <strong>
                            {connectedDevice.title}{" "}
                            <span>{connectedDevice.status}</span>
                        </strong>
                        <p>{connectedDevice.detail}</p>
                    </div>
                    <button type="button">
                        {t(
                            "components.dashboard.settings.accountsettingspanel.signOut",
                        )}
                    </button>
                </article>
            </section>
        </section>
    );
}
function NotificationPreferencesSection({
    intro,
    basePath,
    notificationPrefs,
    onNotificationChange,
    onSave,
    onToggleRealtime,
    onToggleSound,
    onTry,
    realTimeEnabled,
    rows,
    soundEnabled,
}) {
    const { t } = useTranslation();
    return (
        <section
            className="account-settings-section notification-settings-section"
            aria-labelledby="notificationsTitle"
        >
            <SectionBacklink basePath={basePath} />
            <h2 id="notificationsTitle">
                {t(
                    "components.dashboard.settings.accountsettingspanel.notifications",
                )}
            </h2>
            <p>{intro}</p>

            <div
                className="notification-table"
                role="table"
                aria-label={t(
                    "components.dashboard.settings.accountsettingspanel.notificationPreferences",
                )}
            >
                <div className="notification-table-head" role="row">
                    <span></span>
                    <strong>
                        {t(
                            "components.dashboard.settings.accountsettingspanel.email",
                        )}
                    </strong>
                    <strong>
                        {t(
                            "components.dashboard.settings.accountsettingspanel.push",
                        )}
                    </strong>
                </div>
                {rows.map((row) => (
                    <div
                        className="notification-table-row"
                        role="row"
                        key={row.id}
                    >
                        <strong>
                            {row.label}
                            {row.id === "other" ? (
                                <span
                                    aria-label={t(
                                        "components.dashboard.settings.accountsettingspanel.moreInformation",
                                    )}
                                >
                                    ?
                                </span>
                            ) : null}
                        </strong>
                        <label>
                            <span className="sr-only">{`${row.label} email notifications`}</span>
                            <input
                                checked={
                                    notificationPrefs[row.id]?.email || false
                                }
                                className="settings-check"
                                type="checkbox"
                                onChange={() =>
                                    onNotificationChange(row.id, "email")
                                }
                            />
                        </label>
                        <label>
                            <span className="sr-only">{`${row.label} push notifications`}</span>
                            <input
                                checked={
                                    notificationPrefs[row.id]?.push || false
                                }
                                className="settings-check"
                                type="checkbox"
                                onChange={() =>
                                    onNotificationChange(row.id, "push")
                                }
                            />
                        </label>
                    </div>
                ))}
            </div>

            <section
                className="real-time-settings"
                aria-labelledby="realTimeTitle"
            >
                <h3 id="realTimeTitle">
                    {t(
                        "components.dashboard.settings.accountsettingspanel.realTimeNotifications",
                    )}
                </h3>
                <p>
                    {t(
                        "components.dashboard.settings.accountsettingspanel.receiveOnScreenUpdatesAnnouncementsAndMoreWhile",
                    )}
                </p>
                <article>
                    <strong>
                        {t(
                            "components.dashboard.settings.accountsettingspanel.realTimeNotifications",
                        )}
                    </strong>
                    <div>
                        <button
                            className="settings-text-link"
                            type="button"
                            onClick={onTry}
                        >
                            {" "}
                            {t(
                                "components.dashboard.settings.accountsettingspanel.tryMe",
                            )}{" "}
                        </button>
                        <SettingsSwitch
                            enabled={realTimeEnabled}
                            label="Toggle real-time notifications"
                            onToggle={onToggleRealtime}
                        />
                    </div>
                </article>
                <article>
                    <strong>
                        {t(
                            "components.dashboard.settings.accountsettingspanel.soundEffects",
                        )}
                    </strong>
                    <SettingsSwitch
                        enabled={soundEnabled}
                        label="Toggle sound effects"
                        onToggle={onToggleSound}
                    />
                </article>
            </section>

            <button
                className="settings-dark-button"
                type="button"
                onClick={onSave}
            >
                {" "}
                {t(
                    "components.dashboard.settings.accountsettingspanel.updatePreferences",
                )}{" "}
            </button>
        </section>
    );
}
function IdentityVerificationSection({
    basePath,
    onBack,
    onContinue,
    profile,
}) {
    const { t } = useTranslation();
    return (
        <section
            className="account-settings-section identity-settings-section"
            aria-labelledby="identityTitle"
        >
            <div className="identity-copy">
                <SectionBacklink basePath={basePath} />
                <h2 id="identityTitle">{profile.identityTitle}</h2>
                <h3>
                    {t(
                        "components.dashboard.settings.accountsettingspanel.nextSteps",
                    )}
                </h3>
                <p>
                    {profile.identityDescription}{" "}
                    <a href={`${basePath}/identity-verification`}>
                        {t(
                            "components.dashboard.settings.accountsettingspanel.learnMore",
                        )}
                    </a>
                </p>
                <ol className="identity-step-list">
                    <li>
                        {t(
                            "components.dashboard.settings.accountsettingspanel.verifyYourPhoneNumber",
                        )}
                    </li>
                    <li>
                        {t(
                            "components.dashboard.settings.accountsettingspanel.uploadYourId",
                        )}
                    </li>
                </ol>
                <div className="identity-actions">
                    <button
                        className="settings-light-button"
                        type="button"
                        onClick={onBack}
                    >
                        {" "}
                        {t(
                            "components.dashboard.settings.accountsettingspanel.back",
                        )}{" "}
                    </button>
                    <button
                        className="settings-dark-button"
                        type="button"
                        onClick={onContinue}
                    >
                        {" "}
                        {t(
                            "components.dashboard.settings.accountsettingspanel.continue",
                        )}{" "}
                    </button>
                </div>
            </div>
            <aside className="identity-info-card">
                <Icon name="document" />
                <strong>{profile.identityCardTitle}</strong>
                <p>{profile.identityCardText}</p>
            </aside>
        </section>
    );
}
function SectionBacklink({ basePath }) {
    const { t } = useTranslation();
    return (
        <Link className="settings-backlink" to={basePath}>
            {" "}
            {t(
                "components.dashboard.settings.accountsettingspanel.accountSettings2",
            )}{" "}
        </Link>
    );
}
function SettingsSwitch({ enabled, label, onToggle }) {
    return (
        <button
            className={`settings-switch${enabled ? " is-on" : ""}`}
            type="button"
            aria-label={label}
            aria-pressed={enabled}
            onClick={onToggle}
        >
            <span></span>
        </button>
    );
}
function buildNotificationState(rows = []) {
    return rows.reduce((preferences, row) => {
        preferences[row.id] = {
            email: row.email,
            push: row.push,
        };
        return preferences;
    }, {});
}
export default AccountSettingsPanel;
