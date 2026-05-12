import { useState } from "react";
import { FinanceNotice } from "../components/dashboard/FinanceControls.jsx";
import { Icon } from "../components/common/Icons.jsx";

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
    about:
      "I work with specialist freelancers to ship marketplace, SaaS, and ecommerce projects with clear briefs, practical timelines, and fast review cycles. I value thoughtful communication, polished delivery, and long-term creative partnerships.",
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
    about:
      "Hi, I'm your dedicated PHP and full-stack developer with a passion for delivering exceptional digital solutions. Whether you need a quick fix, a complete overhaul, or a bespoke website build from scratch, I'm here to bring your vision to life. I offer free consultations, personalized attention, and a commitment to your success. With over five years of experience in web development, I ensure each project is crafted to perfection.",
    quickLinkLabel: "Gigs",
    quickLinkPage: "seller-services",
  },
};

function ProfileActionMenu({ id, label, openMenu, setOpenMenu, onAction }) {
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
          <button type="button" role="menuitem" onClick={() => onAction(`Editing ${label}.`)}>
            <Icon name="edit" />
            Edit
          </button>
          <button type="button" role="menuitem" onClick={() => onAction(`${label} removed from this preview.`)}>
            <Icon name="trash" />
            Delete
          </button>
        </div>
      ) : null}
    </div>
  );
}

function AddClientForm({ onCancel, onSubmit }) {
  const [description, setDescription] = useState("");
  const [confirmed, setConfirmed] = useState(false);

  return (
    <form className="featured-client-form" onSubmit={onSubmit}>
      <div className="featured-client-form-head">
        <strong>
          <Icon name="plus" />
          Add client
        </strong>
        <button aria-label="Close add client form" type="button" onClick={onCancel}>
          <Icon name="close" />
        </button>
      </div>
      <p>
        Build credibility with potential clients by featuring well-known brands or big companies you've worked with.
        Add up to 5 clients, 1 at a time. <a href="#guidelines">See guidelines</a>
      </p>
      <div className="profile-form-grid">
        <label className="profile-form-field full">
          <span>Client name</span>
          <select defaultValue="">
            <option value="" disabled>
              Client name
            </option>
            <option>BDGigs</option>
            <option>CloudPeak</option>
            <option>BrightCart</option>
          </select>
        </label>
        <label className="profile-form-field">
          <span>Project start date</span>
          <input type="text" placeholder="Project start date" />
        </label>
        <label className="profile-form-field">
          <span>Project end date</span>
          <input type="text" placeholder="Project end date (optional)" />
        </label>
        <label className="profile-form-field full">
          <span>Describe the work you did for this client</span>
          <textarea
            maxLength="400"
            placeholder="Describe the work you did for this client"
            value={description}
            onChange={(event) => setDescription(event.target.value)}
          />
          <small>{description.length}/400 characters</small>
        </label>
      </div>
      <div className="profile-verification-block">
        <strong>
          Verify your work for this client <span aria-label="More information">?</span>
        </strong>
        <p>
          Confirm your work with a link to an invoice, contract, or the actual work. If the client hired you through BDGigs,
          you can link to the BDGigs order.
        </p>
        <div className="profile-warning-note">
          <Icon name="bell" />
          Submitting falsified documents or work samples that are not your own can result in loss of access to this feature.
        </div>
        <input className="profile-url-input" type="url" placeholder="http://" />
        <button className="profile-link-inline" type="button">
          <Icon name="plus" />
          Add another link for verification
        </button>
        <label className="profile-check-row">
          <input checked={confirmed} type="checkbox" onChange={(event) => setConfirmed(event.target.checked)} />
          <span>I confirm I've worked with this client and have permission to publish their name and logo on BDGigs.</span>
        </label>
      </div>
      <div className="profile-form-actions">
        <button type="button" onClick={onCancel}>
          Cancel
        </button>
        <button disabled={!confirmed} type="submit">
          Submit
        </button>
      </div>
    </form>
  );
}

function DashboardProfilePage({ onNavigate, variant = "buyer" }) {
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
          <header className="profile-edit-hero" aria-labelledby="profileEditName">
            <div className="profile-edit-avatar-wrap">
              <span className="profile-edit-avatar">{profile.initials}</span>
              <button aria-label="Change profile photo" className="profile-avatar-camera" type="button" onClick={() => handleNotice("Profile photo upload opened.")}>
                <Icon name="camera" />
              </button>
            </div>

            <div className="profile-edit-heading">
              <div className="profile-name-row">
                <h1 id="profileEditName">{profile.name}</h1>
                <button aria-label="Edit name" className="profile-inline-icon" type="button" onClick={() => handleNotice("Name editor opened.")}>
                  <Icon name="edit" />
                </button>
                <span>{profile.handle}</span>
              </div>
              <div className="profile-title-row">
                <strong>{profile.title}</strong>
                <button aria-label="Edit professional title" className="profile-inline-icon" type="button" onClick={() => handleNotice("Professional title editor opened.")}>
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
                  <button aria-label="Edit languages" className="profile-inline-icon" type="button" onClick={() => handleNotice("Language editor opened.")}>
                    <Icon name="edit" />
                  </button>
                </span>
              </div>
            </div>

            <div className="profile-hero-actions">
              <button type="button" onClick={() => handleNotice("Share link copied to clipboard.")}>
                <Icon name="share" />
                Share
              </button>
              <button type="button" onClick={() => handleNotice("Public profile preview opened.")}>
                <Icon name="eye" />
                Preview
              </button>
            </div>
          </header>

          <article className="profile-edit-card">
            <h2>About</h2>
            <p className="profile-about-copy">{profile.about}</p>
          </article>

          <article className="profile-edit-card featured-clients-card">
            <div className="profile-card-split">
              <div>
                <h2>Featured clients</h2>
                <p>Build credibility by featuring up to 5 clients or brands your agency has worked with.</p>
                <button className="profile-light-button" type="button" onClick={() => setIsClientFormOpen(true)}>
                  <Icon name="plus" />
                  Add client
                </button>
              </div>
              <div className="profile-card-illustration" aria-hidden="true">
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
                  handleNotice("Featured client saved to your profile preview.");
                }}
              />
            ) : null}
          </article>

          <article className="profile-edit-card portfolio-card">
            <h2>Portfolio</h2>
            <div className="portfolio-preview-thumb">
              <img alt="Portfolio preview" src="/assets/img/gig_images/1.png" />
            </div>
            <button className="profile-light-button" type="button" onClick={() => handleNotice("Portfolio builder opened.")}>
              <Icon name="share" />
              Start portfolio
            </button>
          </article>

          <article className="profile-edit-card intro-video-card">
            <div>
              <h2>Intro video</h2>
              <p>Introduce yourself and make a connection with potential clients.</p>
              <button className="profile-light-button" type="button" onClick={() => handleNotice("Intro video uploader opened.")}>
                <Icon name="plus" />
                Add intro video
              </button>
            </div>
            <div className="profile-video-illustration" aria-hidden="true">
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
              <h2>Work experience</h2>
              <button className="profile-light-button" type="button" onClick={() => handleNotice("Work experience form opened.")}>
                <Icon name="plus" />
                Add new
              </button>
            </div>
            <div className="profile-record-card">
              <span className="profile-record-icon">
                <Icon name="building" />
              </span>
              <div>
                <strong>Software Engineer</strong>
                <small>The SoftKing Limited - Full-time</small>
                <small>Jun 2023 - Present - 2 yrs 11 mos</small>
                <p>
                  I design, develop, and maintain web applications using PHP, Laravel, and Node.js. I work on improving existing projects by adding new features, supporting bugs in Laravel applications, and collaborating with teams to plan and deliver new features.
                </p>
              </div>
              <ProfileActionMenu id="work-experience" label="work experience" openMenu={openMenu} setOpenMenu={setOpenMenu} onAction={handleNotice} />
            </div>
          </article>

          <article className="profile-edit-card">
            <div className="profile-section-title-row">
              <h2>Skills and expertise</h2>
              <button className="profile-light-button" type="button" onClick={() => handleNotice("Skill editor opened.")}>
                <Icon name="plus" />
                Add new
              </button>
            </div>
            <div className="profile-skill-grid">
              {sellerSkills.map((skill) => (
                <div className="profile-skill-card" key={skill}>
                  <div>
                    <strong>{skill}</strong>
                    <span>{isSeller ? "Pro" : "Verified"}</span>
                  </div>
                  <ProfileActionMenu id={`skill-${skill}`} label={skill} openMenu={openMenu} setOpenMenu={setOpenMenu} onAction={handleNotice} />
                </div>
              ))}
            </div>
          </article>

          <div className="profile-edit-two-col">
            <article className="profile-edit-card">
              <div className="profile-section-title-row">
                <h2>Education</h2>
                <button className="profile-light-button" type="button" onClick={() => handleNotice("Education form opened.")}>
                  <Icon name="plus" />
                  Add new
                </button>
              </div>
              <div className="profile-record-card compact">
                <span className="profile-record-icon">
                  <Icon name="graduation" />
                </span>
                <div>
                  <strong>University of Dhaka</strong>
                  <small>B.Sc. Degree, computerscience engineering</small>
                  <small>Bangladesh, Graduated 2018</small>
                </div>
                <ProfileActionMenu id="education" label="education" openMenu={openMenu} setOpenMenu={setOpenMenu} onAction={handleNotice} />
              </div>
            </article>

            <article className="profile-edit-card certification-card">
              <h2>Certifications</h2>
              <p>Showcase your mastery with certifications earned in your field.</p>
              <button className="profile-light-button" type="button" onClick={() => handleNotice("Certification form opened.")}>
                <Icon name="plus" />
                Add certifications
              </button>
            </article>
          </div>
        </div>

        <aside className="profile-edit-aside" aria-label="Profile completion">
          <article className="profile-side-card">
            <div className="profile-strength-head">
              <h2>Profile Strength</h2>
              <strong>
                {profile.strength}<span>/{profile.maxStrength}</span>
              </strong>
            </div>
            <p>A strong profile helps you stand out and attract better opportunities.</p>
            <div className="profile-strength-track">
              <span style={{ "--strength": `${profile.progress}%` }}></span>
            </div>
            <button type="button" onClick={() => handleNotice("Intro video uploader opened.")}>
              <Icon name="video" />
              Create an intro video
            </button>
            <button type="button" onClick={() => handleNotice("Certification form opened.")}>
              <Icon name="document" />
              List certifications
            </button>
          </article>

          <article className="profile-side-card">
            <h2>Quick Links</h2>
            <button type="button" onClick={() => onNavigate?.(profile.quickLinkPage)}>
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
