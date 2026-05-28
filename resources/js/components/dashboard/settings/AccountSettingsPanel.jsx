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
import { useToast } from "../../common/ToastProvider.jsx";
import { useTranslation } from "react-i18next";
import { apiRequest } from "../../../api/apiClient.js";
import { useSessionStore } from "../../../stores/useSessionStore.js";

const notificationSoundPath = "/assets/audio/notification.wav";

function AccountSettingsPanel({ onNavigate, variant = "buyer" }) {
    const { t } = useTranslation();
    const notify = useToast();
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
    const [sellerApplication, setSellerApplication] = useState(null);
    const [sellerApplicationReason, setSellerApplicationReason] = useState("");
    const [isSellerApplicationSubmitting, setIsSellerApplicationSubmitting] =
        useState(false);
    const [isIdentitySubmitting, setIsIdentitySubmitting] = useState(false);
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
            .catch((error) => {
                const message =
                    error.message || "Unable to load account settings.";
                setNotice(message);
                notify.error(message);
            });

        if (variant === "seller") {
            apiRequest("/api/seller/application")
                .then((application) => {
                    setSellerApplication(application);
                    setSellerApplicationReason(application.reason || "");
                })
                .catch(() => {});
        }
    }, [notify, variant]);

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
            notify.success("Notification preferences updated.");
        } catch (error) {
            notify.error(
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
            notify.success("Password updated.");
        } catch (error) {
            notify.error(error.message || "Password could not be updated.");
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
            notify.info("Scan the QR code and confirm with an authenticator code.");
        } catch (error) {
            notify.error(error.message || "Two factor enrollment could not start.");
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
            notify.success("Two factor authentication is enabled.");
        } catch (error) {
            notify.error(error.message || "Authenticator code was not accepted.");
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
            notify.success("Two factor authentication is disabled.");
        } catch (error) {
            notify.error(error.message || "Two factor authentication stayed enabled.");
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
            notify.success("Recovery codes regenerated.");
        } catch (error) {
            notify.error(error.message || "Recovery codes could not be regenerated.");
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
            notify.success("Session signed out.");
        } catch (error) {
            notify.error(error.message || "Session could not be revoked.");
        }
    };
    const submitIdentity = async (payload) => {
        const formData = new FormData();
        formData.append("legalName", payload.legalName);
        formData.append("documentType", payload.documentType);
        formData.append("documentReference", payload.documentReference);
        formData.append("country", payload.country || "");
        formData.append("document", payload.documentFile);

        setIsIdentitySubmitting(true);

        try {
            const submission = await apiRequest(
                "/api/user/settings/identity-verification",
                { body: formData },
            );
            setIdentity(submission);
            setAccount((current) => ({
                ...current,
                verificationStatus: submission.status,
            }));
            notify.success("Identity verification submitted for review.");
            return submission;
        } catch (error) {
            notify.error(
                error.message ||
                    "Identity verification could not be submitted.",
            );
            throw error;
        } finally {
            setIsIdentitySubmitting(false);
        }
    };
    const deactivateAccount = async (password, reason) => {
        try {
            await apiRequest("/api/user/settings/deactivate", {
                body: { password, reason },
            });
            setCurrentUser(null);
            onNavigate("home");
        } catch (error) {
            notify.error(error.message || "Account deactivation failed.");
        }
    };
    const submitSellerApplication = async () => {
        setIsSellerApplicationSubmitting(true);

        try {
            const application = await apiRequest("/api/seller/application", {
                body: { reason: sellerApplicationReason },
            });
            setSellerApplication(application);
            notify.success("Seller application submitted for review.");
        } catch (error) {
            notify.error(error.message || "Seller application could not be submitted.");
        } finally {
            setIsSellerApplicationSubmitting(false);
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
                <>
                    {variant === "seller" ? (
                        <SellerApplicationStatus
                            application={sellerApplication}
                            isSubmitting={isSellerApplicationSubmitting}
                            reason={sellerApplicationReason}
                            onReasonChange={setSellerApplicationReason}
                            onSubmit={submitSellerApplication}
                        />
                    ) : null}
                    <SettingsHubCards basePath={basePath} />
                </>
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
                    onTry={() => {
                        notify.info("Real-time notification preview sent.");
                        playNotificationPreview();
                    }}
                />
            ) : null}
            {activePage === "identity-verification" ? (
                <IdentityVerificationSection
                    basePath={basePath}
                    identity={identity}
                    isSubmitting={isIdentitySubmitting}
                    profile={profile}
                    onBack={() =>
                        notify.info("Returned to account settings.")
                    }
                    onContinue={submitIdentity}
                />
            ) : null}
        </main>
    );
}

function SellerApplicationStatus({
    application,
    isSubmitting,
    onReasonChange,
    onSubmit,
    reason,
}) {
    const status = application?.status || "not_applied";
    const canSubmit = application?.canSubmit ?? status === "not_applied";

    return (
        <section className="account-settings-section seller-application-section">
            <div className="settings-section-heading">
                <div>
                    <span>Seller onboarding</span>
                    <h2>Seller application</h2>
                    <p>
                        Your seller status controls whether gigs can be
                        submitted for marketplace review.
                    </p>
                </div>
                <span className="status-badge status-progress">
                    {formatStatusLabel(status)}
                </span>
            </div>
            {application?.reason ? (
                <p className="account-settings-notice">{application.reason}</p>
            ) : null}
            {canSubmit ? (
                <div className="security-password-form">
                    <label>
                        <span>Application note</span>
                        <textarea
                            value={reason}
                            onChange={(event) =>
                                onReasonChange(event.target.value)
                            }
                            rows={3}
                            placeholder="Tell the marketplace team what services you plan to sell."
                        />
                    </label>
                    <button
                        className="settings-dark-button"
                        type="button"
                        onClick={onSubmit}
                        disabled={isSubmitting}
                    >
                        {isSubmitting ? "Submitting..." : "Submit application"}
                    </button>
                </div>
            ) : null}
            {application?.history?.length ? (
                <div className="settings-session-list">
                    {application.history.slice(0, 3).map((item, index) => (
                        <article key={`${item.to}-${index}`}>
                            <div>
                                <strong>{formatStatusLabel(item.to)}</strong>
                                <p>{item.reason || "No reason recorded."}</p>
                            </div>
                            <span>{item.createdAt}</span>
                        </article>
                    ))}
                </div>
            ) : null}
        </section>
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
    const [reason, setReason] = useState("");
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
                    onDeactivate(password, reason);
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
                <label>
                    <span>Reason</span>
                    <textarea
                        rows="3"
                        value={reason}
                        onChange={(event) => setReason(event.target.value)}
                        placeholder="Optional reason for support records"
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
    isSubmitting,
    onBack,
    onContinue,
    profile,
}) {
    const { t } = useTranslation();
    const [step, setStep] = useState(
        identity?.documentPath ? "review" : "details",
    );
    const [error, setError] = useState("");
    const [draft, setDraft] = useState({
        legalName: profile.name || "",
        documentType: "Government ID",
        documentReference: "",
        country: profile.country || "",
        documentFile: null,
    });
    const [previewUrl, setPreviewUrl] = useState("");

    useEffect(() => {
        if (
            !draft.documentFile ||
            !draft.documentFile.type.startsWith("image/")
        ) {
            setPreviewUrl("");
            return undefined;
        }

        const nextPreviewUrl = URL.createObjectURL(draft.documentFile);
        setPreviewUrl(nextPreviewUrl);

        return () => URL.revokeObjectURL(nextPreviewUrl);
    }, [draft.documentFile]);

    const updateDraft = (field, value) => {
        setDraft((current) => ({ ...current, [field]: value }));
    };
    const goToUpload = () => {
        if (!draft.legalName.trim() || !draft.documentReference.trim()) {
            setError("Legal name and document reference are required.");
            return;
        }

        setError("");
        setStep("upload");
    };
    const submitVerification = async (event) => {
        event.preventDefault();

        if (!draft.documentFile) {
            setError("Upload a passport, government ID, or license file.");
            return;
        }

        setError("");

        try {
            await onContinue(draft);
            setStep("review");
        } catch {
            setStep("upload");
        }
    };
    const status = identity?.status || (step === "review" ? "review" : "");
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
                <ol className="identity-progress-list">
                    {["details", "upload", "review"].map((stepName, index) => (
                        <li
                            className={step === stepName ? "is-active" : ""}
                            key={stepName}
                        >
                            <span>{index + 1}</span>
                            {stepName}
                        </li>
                    ))}
                </ol>
                {status ? (
                    <div className="identity-status-card">
                        <strong>Verification status: {status}</strong>
                        {identity?.submittedAt ? (
                            <span>
                                Submitted{" "}
                                {formatIdentityDate(identity.submittedAt)}
                            </span>
                        ) : null}
                        {identity?.documentPath ? (
                            <a
                                href={identity.documentPath}
                                target="_blank"
                                rel="noreferrer"
                            >
                                View uploaded document
                            </a>
                        ) : null}
                    </div>
                ) : null}
                {error ? (
                    <p className="identity-validation" role="alert">
                        {error}
                    </p>
                ) : null}
                <form
                    className="security-password-form identity-submit-form"
                    onSubmit={submitVerification}
                >
                    {step === "details" ? (
                        <>
                            <h3>Confirm your details</h3>
                            <label>
                                <span>Legal name</span>
                                <input
                                    value={draft.legalName}
                                    onChange={(event) =>
                                        updateDraft(
                                            "legalName",
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                            </label>
                            <label>
                                <span>Document type</span>
                                <select
                                    value={draft.documentType}
                                    onChange={(event) =>
                                        updateDraft(
                                            "documentType",
                                            event.target.value,
                                        )
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
                                        updateDraft(
                                            "country",
                                            event.target.value,
                                        )
                                    }
                                />
                            </label>
                            <div className="identity-step-actions">
                                <button
                                    className="settings-dark-button"
                                    type="button"
                                    onClick={goToUpload}
                                >
                                    Next: upload ID
                                </button>
                            </div>
                        </>
                    ) : null}
                    {step === "upload" ? (
                        <>
                            <h3>Upload your ID</h3>
                            <label className="identity-upload-card">
                                <input
                                    type="file"
                                    accept="image/*,application/pdf"
                                    onChange={(event) =>
                                        updateDraft(
                                            "documentFile",
                                            event.target.files?.[0] || null,
                                        )
                                    }
                                />
                                <Icon name="upload" />
                                <strong>Choose a file or drop it here</strong>
                                <span>JPG, PNG, WEBP, or PDF up to 10 MB</span>
                            </label>
                            {draft.documentFile ? (
                                <div className="identity-file-preview">
                                    {previewUrl ? (
                                        <img src={previewUrl} alt="" />
                                    ) : (
                                        <Icon name="document" />
                                    )}
                                    <div>
                                        <strong>
                                            {draft.documentFile.name}
                                        </strong>
                                        <span>
                                            {formatFileSize(
                                                draft.documentFile.size,
                                            )}
                                        </span>
                                    </div>
                                </div>
                            ) : null}
                            <div className="identity-step-actions">
                                <button
                                    className="settings-light-button"
                                    type="button"
                                    onClick={() => setStep("details")}
                                >
                                    Back
                                </button>
                                <button
                                    className="settings-dark-button"
                                    type="submit"
                                    disabled={
                                        isSubmitting || !draft.documentFile
                                    }
                                >
                                    {isSubmitting
                                        ? "Submitting..."
                                        : "Submit for review"}
                                </button>
                            </div>
                        </>
                    ) : null}
                    {step === "review" ? (
                        <div className="identity-complete-panel">
                            <Icon name="verifiedUser" />
                            <h3>Verification submitted</h3>
                            <p>
                                Your document is queued for manual review. You
                                can keep using your dashboard while we review it.
                            </p>
                            <button
                                className="settings-light-button"
                                type="button"
                                onClick={() => setStep("details")}
                            >
                                Submit another document
                            </button>
                        </div>
                    ) : null}
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

function playNotificationPreview() {
    const audio = new Audio(notificationSoundPath);
    audio.play().catch(() => {});
}

function formatStatusLabel(status = "") {
    return String(status || "not_applied")
        .replace(/[_-]/g, " ")
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function formatFileSize(size = 0) {
    if (!size) {
        return "0 KB";
    }

    if (size < 1024 * 1024) {
        return `${Math.ceil(size / 1024)} KB`;
    }

    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
}

function formatIdentityDate(value) {
    try {
        return new Intl.DateTimeFormat("en", {
            dateStyle: "medium",
            timeStyle: "short",
        }).format(new Date(value));
    } catch {
        return value;
    }
}

export default AccountSettingsPanel;
