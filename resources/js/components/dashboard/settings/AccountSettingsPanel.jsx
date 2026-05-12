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

function AccountSettingsPanel({ onNavigate, variant = "buyer" }) {
  const { settingsPage } = useParams();
  const profile = settingsProfiles[variant] || settingsProfiles.buyer;
  const basePath = variant === "seller" ? "/dashboard/seller/settings" : "/dashboard/settings";
  const activePage = settingsPage || "overview";
  const [notice, setNotice] = useState("");
  const [twoFactorEnabled, setTwoFactorEnabled] = useState(false);
  const [realTimeEnabled, setRealTimeEnabled] = useState(true);
  const [soundEnabled, setSoundEnabled] = useState(true);
  const [notificationPrefs, setNotificationPrefs] = useState(() => buildNotificationState(notificationRows[variant]));

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
            <h1>Account settings</h1>
            <p>
              {profile.name} ({profile.email})
            </p>
          </div>
          <a href={profile.profilePath} onClick={openProfile}>
            {profile.profileLabel}
          </a>
        </header>
      ) : null}

      {notice ? <p className="account-settings-notice">{notice}</p> : null}

      {activePage === "overview" ? <SettingsHubCards basePath={basePath} /> : null}
      {activePage === "personal-information" ? (
        <PersonalInformationSection basePath={basePath} rows={personalRows} onAction={(label) => setNotice(`${label} settings are ready to edit.`)} />
      ) : null}
      {activePage === "account-security" ? (
        <AccountSecuritySection
          basePath={basePath}
          twoFactorEnabled={twoFactorEnabled}
          onSave={() => setNotice("Security changes saved.")}
          onToggleTwoFactor={() => setTwoFactorEnabled((enabled) => !enabled)}
          onAction={(label) => setNotice(`${label} settings are ready to edit.`)}
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
          onToggleRealtime={() => setRealTimeEnabled((enabled) => !enabled)}
          onToggleSound={() => setSoundEnabled((enabled) => !enabled)}
          onSave={() => setNotice("Notification preferences updated.")}
          onTry={() => setNotice("Real-time notification preview sent.")}
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
  return (
    <section className="settings-hub-grid" aria-label="Account settings categories">
      {settingsHubCards.map((card) => (
        <Link className="settings-hub-card" to={`${basePath}/${card.id}`} key={card.id}>
          <Icon name={card.icon} />
          <strong>{card.title}</strong>
          <span>{card.description}</span>
        </Link>
      ))}
    </section>
  );
}

function PersonalInformationSection({ basePath, rows, onAction }) {
  return (
    <section className="account-settings-section personal-info-section" aria-labelledby="personalInfoTitle">
      <SectionBacklink basePath={basePath} />
      <h2 id="personalInfoTitle">Personal information</h2>
      <div className="personal-info-list">
        {rows.map((row) => (
          <article className="personal-info-row" key={row.label}>
            <div>
              <strong>{row.label}</strong>
              {row.value ? <span>{row.value}</span> : null}
            </div>
            <button className={row.danger ? "danger-link" : ""} type="button" onClick={() => onAction(row.label)}>
              {row.action}
            </button>
          </article>
        ))}
      </div>
    </section>
  );
}

function AccountSecuritySection({ basePath, twoFactorEnabled, onAction, onSave, onToggleTwoFactor }) {
  return (
    <section className="account-settings-section security-settings-section" aria-labelledby="securityTitle">
      <SectionBacklink basePath={basePath} />
      <h2 id="securityTitle">Account security</h2>
      <form
        className="security-password-form"
        onSubmit={(event) => {
          event.preventDefault();
          onSave();
        }}
      >
        <h3>Change password</h3>
        <label>
          <span>Current Password</span>
          <input type="password" autoComplete="current-password" />
        </label>
        <label>
          <span>New Password</span>
          <input type="password" autoComplete="new-password" />
        </label>
        <label>
          <span>Confirm Password</span>
          <div>
            <input type="password" autoComplete="new-password" />
            <small>8 characters or longer. Combine upper and lowercase letters and numbers.</small>
          </div>
        </label>
        <button className="settings-primary-button" type="submit">
          Save Changes
        </button>
      </form>

      <div className="security-option-list">
        {securityRows.map((row) => (
          <article className="security-option-row" key={row.title}>
            <strong>{row.title}</strong>
            <p>{row.description}</p>
            <button type="button" onClick={() => onAction(row.title)}>
              {row.action}
            </button>
          </article>
        ))}
        <article className="security-option-row two-factor-row">
          <strong>
            Two factor authentication
            <span>Recommended</span>
          </strong>
          <div>
            <SettingsSwitch enabled={twoFactorEnabled} label="Toggle two factor authentication" onToggle={onToggleTwoFactor} />
            <p>
              To help keep your account secure, we'll ask you to submit a code when using a new device to log in.
            </p>
          </div>
        </article>
      </div>

      <section className="connected-device-panel" aria-labelledby="connectedDeviceTitle">
        <h3 id="connectedDeviceTitle">Connected devices</h3>
        <article>
          <Icon name="dashboard" />
          <div>
            <strong>
              {connectedDevice.title} <span>{connectedDevice.status}</span>
            </strong>
            <p>{connectedDevice.detail}</p>
          </div>
          <button type="button">Sign Out</button>
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
  return (
    <section className="account-settings-section notification-settings-section" aria-labelledby="notificationsTitle">
      <SectionBacklink basePath={basePath} />
      <h2 id="notificationsTitle">Notifications</h2>
      <p>{intro}</p>

      <div className="notification-table" role="table" aria-label="Notification preferences">
        <div className="notification-table-head" role="row">
          <span></span>
          <strong>Email</strong>
          <strong>Push</strong>
        </div>
        {rows.map((row) => (
          <div className="notification-table-row" role="row" key={row.id}>
            <strong>
              {row.label}
              {row.id === "other" ? <span aria-label="More information">?</span> : null}
            </strong>
            <label>
              <span className="sr-only">{`${row.label} email notifications`}</span>
              <input
                checked={notificationPrefs[row.id]?.email || false}
                className="settings-check"
                type="checkbox"
                onChange={() => onNotificationChange(row.id, "email")}
              />
            </label>
            <label>
              <span className="sr-only">{`${row.label} push notifications`}</span>
              <input
                checked={notificationPrefs[row.id]?.push || false}
                className="settings-check"
                type="checkbox"
                onChange={() => onNotificationChange(row.id, "push")}
              />
            </label>
          </div>
        ))}
      </div>

      <section className="real-time-settings" aria-labelledby="realTimeTitle">
        <h3 id="realTimeTitle">Real-time notifications</h3>
        <p>Receive on-screen updates, announcements, and more while online.</p>
        <article>
          <strong>Real-time notifications</strong>
          <div>
            <button className="settings-text-link" type="button" onClick={onTry}>
              Try me
            </button>
            <SettingsSwitch enabled={realTimeEnabled} label="Toggle real-time notifications" onToggle={onToggleRealtime} />
          </div>
        </article>
        <article>
          <strong>Sound effects</strong>
          <SettingsSwitch enabled={soundEnabled} label="Toggle sound effects" onToggle={onToggleSound} />
        </article>
      </section>

      <button className="settings-dark-button" type="button" onClick={onSave}>
        Update preferences
      </button>
    </section>
  );
}

function IdentityVerificationSection({ basePath, onBack, onContinue, profile }) {
  return (
    <section className="account-settings-section identity-settings-section" aria-labelledby="identityTitle">
      <div className="identity-copy">
        <SectionBacklink basePath={basePath} />
        <h2 id="identityTitle">{profile.identityTitle}</h2>
        <h3>Next steps</h3>
        <p>
          {profile.identityDescription} <a href={`${basePath}/identity-verification`}>Learn more</a>
        </p>
        <ol className="identity-step-list">
          <li>Verify your phone number</li>
          <li>Upload your ID</li>
        </ol>
        <div className="identity-actions">
          <button className="settings-light-button" type="button" onClick={onBack}>
            Back
          </button>
          <button className="settings-dark-button" type="button" onClick={onContinue}>
            Continue
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
  return (
    <Link className="settings-backlink" to={basePath}>
      &larr; Account settings
    </Link>
  );
}

function SettingsSwitch({ enabled, label, onToggle }) {
  return (
    <button className={`settings-switch${enabled ? " is-on" : ""}`} type="button" aria-label={label} aria-pressed={enabled} onClick={onToggle}>
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
