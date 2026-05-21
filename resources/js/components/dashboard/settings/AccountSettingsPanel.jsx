import { useEffect, useMemo, useState } from "react";
import { Link, Navigate, useParams } from "react-router-dom";
import {
    notificationRows,
    personalInfoRows,
    settingsHubCards,
    settingsPageTitles,
    settingsProfiles,
} from "../../../data/settingsPageData.js";
import { Icon } from "../../common/Icons.jsx";
import { useTranslation } from "react-i18next";
import { apiRequest } from "../../../api/apiClient.js";
import { useSessionStore } from "../../../stores/useSessionStore.js";

function AccountSettingsPanel({ onNavigate, variant = "buyer" }) {
    const { t } = useTranslation();
    const { settingsPage } = useParams();
    const profileCopy = settingsProfiles[variant] || settingsProfiles.buyer;
    const setCurrentUser = useSessionStore((state) => state.setCurrentUser);
    const basePath =
        variant === "seller"
            ? "/dashboard/seller/settings"
            : "/dashboard/settings";
    const activePage = settingsPage || "overview";
    const [notice, setNotice] = useState("");
    const [twoFactorEnabled, setTwoFactorEnabled] = useState(false);
    const [realTimeEnabled, setRealTimeEnabled] = useState(true);
    const [soundEnabled, setSoundEnabled] = useState(true);
    const [account, setAccount] = useState({
        name: "",
        email: "",
        username: "",
        country: "",
        visibility: "",
        verificationStatus: "",
    });
    const [sessions, setSessions] = useState([]);
    const [identity, setIdentity] = useState(null);
    const [twoFactorSetup, setTwoFactorSetup] = useState({
        qrSvg: "",
        recoveryCodes: [],
        confirmationCode: "",
    });
    const [notificationPrefs, setNotificationPrefs] = useState(() =>
        buildNotificationState(notificationRows[variant]),
    );
    const profile = useMemo(
        () => ({
            ...profileCopy,
            ...account,
            email: account.email || profileCopy.email,
            name: account.name || profileCopy.name,
        }),
        [account, profileCopy],
    );
    const personalRows = useMemo(
        () =>
            personalInfoRows.map((row) => ({
                ...row,
                value: row.field ? profile[row.field] : row.value,
            })),
        [profile],
    );

    useEffect(() => {
        apiRequest("/api/user/settings")
            .then((settings) => {
                setAccount(settings.account || {});
                setSessions(settings.sessions || []);
                setIdentity(settings.identity || null);
                setTwoFactorEnabled(Boolean(settings.account?.twoFactorEnabled));
                setNotificationPrefs({
                    ...buildNotificationState(notificationRows[variant]),
                    ...(settings.notifications?.preferences || {}),
                });
                setRealTimeEnabled(
                    settings.notifications?.realtimeEnabled ?? true,
                );
                setSoundEnabled(settings.notifications?.soundEnabled ?? true);
            })
            .catch((error) =>
                setNotice(error.message || "Unable to load account settings."),
            );
    }, [variant]);

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
    const saveNotifications = async () => {
        try {
            const preferences = await apiRequest(
                "/api/user/settings/notifications",
                {
                    method: "PATCH",
                    body: {
                        preferences: notificationPrefs,
                        realtimeEnabled: realTimeEnabled,
                        soundEnabled,
                    },
                },
            );
            setNotificationPrefs(preferences.preferences || {});
            setRealTimeEnabled(preferences.realtimeEnabled);
            setSoundEnabled(preferences.soundEnabled);
            setNotice("Notification preferences updated.");
        } catch (error) {
            setNotice(
                error.message || "Notification preferences could not be saved.",
            );
        }
    };
    const updatePassword = async (passwords) => {
        try {
            await apiRequest("/api/user/settings/password", {
                method: "PATCH",
                body: passwords,
            });
            setNotice("Password updated.");
        } catch (error) {
            setNotice(error.message || "Password could not be updated.");
        }
    };
    const startTwoFactor = async () => {
        try {
            await apiRequest("/user/two-factor-authentication", {
                method: "POST",
                body: {},
            });
            const [qrCode, recoveryCodes] = await Promise.all([
                apiRequest("/user/two-factor-qr-code"),
                apiRequest("/user/two-factor-recovery-codes"),
            ]);
            setTwoFactorSetup((current) => ({
                ...current,
                qrSvg: qrCode.svg || "",
                recoveryCodes: recoveryCodes || [],
            }));
            setNotice("Scan the QR code and confirm with an authenticator code.");
        } catch (error) {
            setNotice(error.message || "Two factor enrollment could not start.");
        }
    };
    const confirmTwoFactor = async () => {
        try {
            await apiRequest("/user/confirmed-two-factor-authentication", {
                method: "POST",
                body: { code: twoFactorSetup.confirmationCode },
            });
            setTwoFactorEnabled(true);
            setTwoFactorSetup((current) => ({
                ...current,
                qrSvg: "",
                confirmationCode: "",
            }));
            setNotice("Two factor authentication is enabled.");
        } catch (error) {
            setNotice(error.message || "Authenticator code was not accepted.");
        }
    };
    const disableTwoFactor = async () => {
        try {
            await apiRequest("/user/two-factor-authentication", {
                method: "DELETE",
            });
            setTwoFactorEnabled(false);
            setTwoFactorSetup({
                qrSvg: "",
                recoveryCodes: [],
                confirmationCode: "",
            });
            setNotice("Two factor authentication is disabled.");
        } catch (error) {
            setNotice(error.message || "Two factor authentication stayed enabled.");
        }
    };
    const regenerateRecoveryCodes = async () => {
        try {
            await apiRequest("/user/two-factor-recovery-codes", {
                method: "POST",
                body: {},
            });
            const recoveryCodes = await apiRequest(
                "/user/two-factor-recovery-codes",
            );
            setTwoFactorSetup((current) => ({
                ...current,
                recoveryCodes: recoveryCodes || [],
            }));
            setNotice("Recovery codes regenerated.");
        } catch (error) {
            setNotice(error.message || "Recovery codes could not be regenerated.");
        }
    };
    const revokeSession = async (sessionId) => {
        try {
            await apiRequest(`/api/user/settings/sessions/${sessionId}`, {
                method: "DELETE",
            });
            setSessions((current) =>
                current.filter((session) => session.id !== sessionId),
            );
            setNotice("Session signed out.");
        } catch (error) {
            setNotice(error.message || "Session could not be revoked.");
        }
    };
    const submitIdentity = async (payload) => {
        try {
            const submission = await apiRequest(
                "/api/user/settings/identity-verification",
                { body: payload },
            );
            setIdentity(submission);
            setAccount((current) => ({
                ...current,
                verificationStatus: submission.status,
            }));
            setNotice("Identity verification submitted for review.");
        } catch (error) {
            setNotice(error.message || "Identity verification could not be submitted.");
        }
    };
    const deactivateAccount = async (password) => {
        try {
            await apiRequest("/api/user/settings/deactivate", {
                body: { password },
            });
            setCurrentUser(null);
            onNavigate("home");
        } catch (error) {
            setNotice(error.message || "Account deactivation failed.");
        }
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
                    onAction={() => onNavigate(profile.profilePage)}
                    onDeactivate={deactivateAccount}
                />
            ) : null}
            {activePage === "account-security" ? (
                <AccountSecuritySection
                    basePath={basePath}
                    sessions={sessions}
                    twoFactorSetup={twoFactorSetup}
                    twoFactorEnabled={twoFactorEnabled}
                    onConfirmTwoFactor={confirmTwoFactor}
                    onDisableTwoFactor={disableTwoFactor}
                    onPasswordUpdate={updatePassword}
                    onRecoveryCodes={regenerateRecoveryCodes}
                    onRevokeSession={revokeSession}
                    onStartTwoFactor={startTwoFactor}
                    onTwoFactorCode={(confirmationCode) =>
                        setTwoFactorSetup((current) => ({
                            ...current,
                            confirmationCode,
                        }))
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
                    onSave={saveNotifications}
                    onTry={() =>
                        setNotice("Real-time notification preview sent.")
                    }
                />
            ) : null}
            {activePage === "identity-verification" ? (
                <IdentityVerificationSection
                    basePath={basePath}
                    identity={identity}
                    profile={profile}
                    onBack={() => setNotice("Returned to account settings.")}
                    onContinue={submitIdentity}
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
function PersonalInformationSection({
    basePath,
    rows,
    onAction,
    onDeactivate,
}) {
    const { t } = useTranslation();
    const [password, setPassword] = useState("");
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
                {rows.filter((row) => !row.danger).map((row) => (
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
            <form
                className="security-password-form account-deactivate-form"
                onSubmit={(event) => {
                    event.preventDefault();
                    onDeactivate(password);
                }}
            >
                <h3>Deactivate account</h3>
                <p>
                    Marketplace records stay in place, but this account will be
                    signed out and blocked from further use.
                </p>
                <label>
                    <span>Confirm password</span>
                    <input
                        type="password"
                        autoComplete="current-password"
                        value={password}
                        onChange={(event) => setPassword(event.target.value)}
                        required
                    />
                </label>
                <button
                    className="settings-dark-button danger-link"
                    type="submit"
                    disabled={!password}
                >
                    Deactivate account
                </button>
            </form>
        </section>
    );
}
function AccountSecuritySection({
    basePath,
    sessions,
    twoFactorSetup,
    twoFactorEnabled,
    onConfirmTwoFactor,
    onDisableTwoFactor,
    onPasswordUpdate,
    onRecoveryCodes,
    onRevokeSession,
    onStartTwoFactor,
    onTwoFactorCode,
}) {
    const { t } = useTranslation();
    const [passwordForm, setPasswordForm] = useState({
        currentPassword: "",
        password: "",
        password_confirmation: "",
    });
    const updatePasswordField = (field, value) => {
        setPasswordForm((current) => ({ ...current, [field]: value }));
    };
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
                    onPasswordUpdate(passwordForm);
                    setPasswordForm({
                        currentPassword: "",
                        password: "",
                        password_confirmation: "",
                    });
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
                    <input
                        type="password"
                        autoComplete="current-password"
                        value={passwordForm.currentPassword}
                        onChange={(event) =>
                            updatePasswordField(
                                "currentPassword",
                                event.target.value,
                            )
                        }
                        required
                    />
                </label>
                <label>
                    <span>
                        {t(
                            "components.dashboard.settings.accountsettingspanel.newPassword",
                        )}
                    </span>
                    <input
                        type="password"
                        autoComplete="new-password"
                        value={passwordForm.password}
                        onChange={(event) =>
                            updatePasswordField("password", event.target.value)
                        }
                        required
                    />
                </label>
                <label>
                    <span>
                        {t(
                            "components.dashboard.settings.accountsettingspanel.confirmPassword",
                        )}
                    </span>
                    <div>
                        <input
                            type="password"
                            autoComplete="new-password"
                            value={passwordForm.password_confirmation}
                            onChange={(event) =>
                                updatePasswordField(
                                    "password_confirmation",
                                    event.target.value,
                                )
                            }
                            required
                        />
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
                            onToggle={
                                twoFactorEnabled
                                    ? onDisableTwoFactor
                                    : onStartTwoFactor
                            }
                        />
                        <p>
                            {" "}
                            {t(
                                "components.dashboard.settings.accountsettingspanel.toHelpKeepYourAccountSecureWellAsk",
                            )}{" "}
                        </p>
                    </div>
                </article>
                {twoFactorSetup.qrSvg ? (
                    <article className="security-option-row two-factor-setup-row">
                        <div>
                            <strong>Authenticator setup</strong>
                            <p>
                                Scan this QR code, then enter the current code
                                from your authenticator app.
                            </p>
                        </div>
                        <div className="two-factor-qr-panel">
                            <div
                                className="two-factor-qr"
                                dangerouslySetInnerHTML={{
                                    __html: twoFactorSetup.qrSvg,
                                }}
                            />
                            <label>
                                <span className="sr-only">
                                    Authenticator code
                                </span>
                                <input
                                    inputMode="numeric"
                                    value={twoFactorSetup.confirmationCode}
                                    onChange={(event) =>
                                        onTwoFactorCode(event.target.value)
                                    }
                                    placeholder="123456"
                                />
                            </label>
                            <button
                                className="settings-primary-button"
                                type="button"
                                disabled={!twoFactorSetup.confirmationCode}
                                onClick={onConfirmTwoFactor}
                            >
                                Confirm two factor
                            </button>
                        </div>
                    </article>
                ) : null}
                {twoFactorEnabled || twoFactorSetup.recoveryCodes.length ? (
                    <article className="security-option-row recovery-code-row">
                        <div>
                            <strong>Recovery codes</strong>
                            <p>
                                Store recovery codes somewhere private before
                                you need them.
                            </p>
                        </div>
                        {twoFactorSetup.recoveryCodes.length ? (
                            <code className="two-factor-codes">
                                {twoFactorSetup.recoveryCodes.join("\n")}
                            </code>
                        ) : null}
                        <button type="button" onClick={onRecoveryCodes}>
                            Regenerate codes
                        </button>
                    </article>
                ) : null}
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
                {sessions.length ? sessions.map((session) => (
                    <article key={session.id}>
                        <Icon name="dashboard" />
                        <div>
                            <strong>
                                {session.userAgent || "Browser session"}{" "}
                                {session.current ? <span>This device</span> : null}
                            </strong>
                            <p>
                                {session.ipAddress || "Unknown IP"} -{" "}
                                {session.lastActivity}
                            </p>
                        </div>
                        <button
                            type="button"
                            disabled={session.current}
                            onClick={() => onRevokeSession(session.id)}
                        >
                            {session.current
                                ? "Active"
                                : t(
                                      "components.dashboard.settings.accountsettingspanel.signOut",
                                  )}
                        </button>
                    </article>
                )) : (
                    <p>No connected sessions found.</p>
                )}
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
    identity,
    onBack,
    onContinue,
    profile,
}) {
    const { t } = useTranslation();
    const [draft, setDraft] = useState({
        legalName: profile.name || "",
        documentType: "Government ID",
        documentReference: "",
        country: profile.country || "",
    });
    const updateDraft = (field, value) => {
        setDraft((current) => ({ ...current, [field]: value }));
    };
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
                </div>
                <form
                    className="security-password-form identity-submit-form"
                    onSubmit={(event) => {
                        event.preventDefault();
                        onContinue(draft);
                    }}
                >
                    <h3>Submit verification details</h3>
                    {identity ? (
                        <p>
                            Current status:{" "}
                            <strong>{identity.status || "submitted"}</strong>
                        </p>
                    ) : null}
                    <label>
                        <span>Legal name</span>
                        <input
                            value={draft.legalName}
                            onChange={(event) =>
                                updateDraft("legalName", event.target.value)
                            }
                            required
                        />
                    </label>
                    <label>
                        <span>Document type</span>
                        <select
                            value={draft.documentType}
                            onChange={(event) =>
                                updateDraft("documentType", event.target.value)
                            }
                        >
                            <option>Government ID</option>
                            <option>Passport</option>
                            <option>Driving license</option>
                        </select>
                    </label>
                    <label>
                        <span>Document reference</span>
                        <input
                            value={draft.documentReference}
                            onChange={(event) =>
                                updateDraft(
                                    "documentReference",
                                    event.target.value,
                                )
                            }
                            required
                        />
                    </label>
                    <label>
                        <span>Country</span>
                        <input
                            value={draft.country}
                            onChange={(event) =>
                                updateDraft("country", event.target.value)
                            }
                        />
                    </label>
                    <button className="settings-dark-button" type="submit">
                        {t(
                            "components.dashboard.settings.accountsettingspanel.continue",
                        )}
                    </button>
                </form>
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
