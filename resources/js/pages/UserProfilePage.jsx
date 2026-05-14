import { useEffect, useRef, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { Icon } from "../components/common/Icons.jsx";
import Footer from "../components/layout/Footer.jsx";
import Header from "../components/layout/Header.jsx";
import { getUserProfile } from "../data/userProfileData.js";

const profileTabs = [
  { id: "about", label: "About Me" },
  { id: "services", label: "Services" },
  { id: "portfolio", label: "Portfolio" },
  { id: "reviews", label: "Reviews" },
];

function UserProfilePage({ onNavigate }) {
  const { username } = useParams();
  const profile = getUserProfile(username);
  const [activeSection, setActiveSection] = useState("about");
  const [isStickyNavVisible, setIsStickyNavVisible] = useState(false);
  const summaryRef = useRef(null);

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
          { rootMargin: "-170px 0px -55% 0px", threshold: 0.01 },
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

      const headerOffset = window.matchMedia("(max-width: 980px)").matches ? 58 : 66;
      setIsStickyNavVisible(summary.getBoundingClientRect().bottom <= headerOffset);
    };

    updateStickyNavVisibility();
    window.addEventListener("scroll", updateStickyNavVisibility, { passive: true });
    window.addEventListener("resize", updateStickyNavVisibility);

    return () => {
      window.removeEventListener("scroll", updateStickyNavVisibility);
      window.removeEventListener("resize", updateStickyNavVisibility);
    };
  }, [profile.slug]);

  return (
    <div className={`user-profile-page${isStickyNavVisible ? " has-profile-sticky-nav" : ""}`}>
      <Header enableMarketplaceHeader={false} forceSearch onNavigate={onNavigate} />

      <main>
        <section className="public-profile-shell">
          <div className="container">
            <ProfileHero profile={profile} summaryRef={summaryRef} />
          </div>
        </section>

        <ProfileStickyNav activeSection={activeSection} isVisible={isStickyNavVisible} profile={profile} />

        <div className="container public-profile-content">
          <ServicesSection profile={profile} />
          <PortfolioSection profile={profile} />
          <WorkExperienceSection profile={profile} />
          <ProfileSourcingCTA />
          <ReviewsSection profile={profile} />
        </div>

        <ProfileTalentSection />
      </main>

      <ProfileMessageBubble profile={profile} />
      <Footer />
    </div>
  );
}

function ProfileHero({ profile, summaryRef }) {
  return (
    <header className="public-profile-hero" id="about">
      <div className="public-profile-primary">
        <div className="public-profile-avatar-wrap">
          <img src={profile.avatar} alt={`${profile.name} profile`} />
          <span className="profile-online-dot" aria-label="Online"></span>
        </div>

        <div className="public-profile-summary" ref={summaryRef}>
          <div className="public-profile-name-row">
            <h1>{profile.name}</h1>
            <span>{profile.handle}</span>
          </div>

          <div className="public-profile-rating-row">
            <ProfileRating rating={profile.rating} reviews={profile.reviews} />
            <span>{profile.level}</span>
          </div>

          <strong className="public-profile-title">{profile.title}</strong>

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
          <h2>About me</h2>
          <p>
            {profile.about} <a href="#portfolio">Read more</a>
          </p>
        </section>

        <section className="public-skills-block" aria-labelledby="profileSkillsTitle">
          <h2 id="profileSkillsTitle">Skills</h2>
          <div>
            {profile.skills.map((skill) => (
              <Link to={`/search/gigs?query=${encodeURIComponent(skill)}&source=profile-skill`} key={skill}>
                {skill}
              </Link>
            ))}
          </div>
        </section>
      </div>

      <aside className="public-profile-contact-column" aria-label={`Contact ${profile.name}`}>
        <div className="profile-top-actions">
          <button type="button">More about me</button>
          <button type="button" aria-label={`Save ${profile.name}`}>
            <Icon name="heart" />
          </button>
        </div>
        <article className="profile-contact-card">
          <div>
            <img src={profile.avatar} alt="" />
            <span className="profile-online-dot" aria-hidden="true"></span>
            <div>
              <strong>{profile.name}</strong>
              <p>Online - {profile.localTime} local time</p>
            </div>
          </div>
          <button type="button">
            <Icon name="send" />
            Contact me
          </button>
          <p>Average response time: {profile.responseTime}</p>
        </article>
      </aside>
    </header>
  );
}

function ProfileStickyNav({ activeSection, isVisible, profile }) {
  return (
    <div className={`profile-sticky-nav${isVisible ? " is-visible" : ""}`} aria-hidden={!isVisible} aria-label="Profile sections">
      <div className="container">
        <div className="sticky-profile-person">
          <div className="sticky-profile-avatar">
            <img src={profile.avatar} alt="" />
            <span className="profile-online-dot" aria-hidden="true"></span>
          </div>
          <div>
            <strong>{profile.name}</strong>
            <ProfileRating rating={profile.rating} reviews={profile.reviews} />
            <span>Online - {profile.localTime} local time</span>
          </div>
        </div>

        <nav className="sticky-profile-tabs">
          {profileTabs.map((tab) => (
            <a className={activeSection === tab.id ? "is-active" : ""} href={`#${tab.id}`} key={tab.id}>
              {tab.label}
            </a>
          ))}
        </nav>

        <div className="sticky-profile-action">
          <button type="button">
            <Icon name="send" />
            Contact me
          </button>
          <span>Average response time: {profile.responseTime}</span>
        </div>
      </div>
    </div>
  );
}

function ServicesSection({ profile }) {
  return (
    <section className="profile-section profile-services-section" id="services">
      <h2>See my services</h2>
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
                From
                <strong>${service.price} / project</strong>
              </span>
              <Link to={`/gigs/${service.id}`}>More details</Link>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}

function PortfolioSection({ profile }) {
  const { portfolio } = profile;

  return (
    <section className="profile-section profile-portfolio-section" id="portfolio">
      <h2>Portfolio</h2>
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
                <dt>Project cost</dt>
                <dd>{portfolio.cost}</dd>
              </div>
              <div>
                <dt>Project duration</dt>
                <dd>{portfolio.duration}</dd>
              </div>
            </dl>
          </div>
        </article>

        <div className="profile-portfolio-thumbs" aria-label="Portfolio thumbnails">
          {portfolio.thumbnails.slice(0, 2).map((image, index) => (
            <button className={index === 0 ? "is-active" : ""} type="button" key={`${image}-${index}`}>
              <img src={image} alt="" />
            </button>
          ))}
          <button className="profile-project-count" type="button">
            +3
            <span>Projects</span>
          </button>
        </div>
      </div>
    </section>
  );
}

function WorkExperienceSection({ profile }) {
  return (
    <section className="profile-section profile-work-section">
      <h2>Work experience</h2>
      <div className="profile-work-list">
        {profile.workExperience.map((item) => (
          <article className="profile-work-item" key={`${item.role}-${item.company}`}>
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
  return (
    <section className="profile-sourcing-cta">
      <div>
        <h2>Get the right freelancer, without the search</h2>
        <p>We'll handle the sourcing, interviewing, and vetting so you don't have to.</p>
        <button type="button">
          Source for me
          <Icon name="arrowRight" />
        </button>
      </div>
      <div className="profile-sourcing-stack" aria-hidden="true">
        {["Eugene Cherniak", "Alina Cruz", "P Musilenko"].map((name, index) => (
          <article key={name}>
            <img src={`https://images.pexels.com/photos/${[220453, 774909, 614810][index]}/pexels-photo-${[220453, 774909, 614810][index]}.jpeg?auto=compress&cs=tinysrgb&w=120`} alt="" />
            <strong>{name}</strong>
            <span></span>
          </article>
        ))}
      </div>
    </section>
  );
}

function ReviewsSection({ profile }) {
  const reviews = profile.reviewsData;
  const sample = reviews.sample;

  return (
    <section className="profile-section profile-reviews-section" id="reviews">
      <div className="profile-reviews-summary">
        <div>
          <h2>{reviews.count} Reviews</h2>
          {reviews.breakdown.map((row) => (
            <div className="profile-review-breakdown-row" key={row.label}>
              <span>{row.label}</span>
              <i>
                <b style={{ width: `${row.value}%` }}></b>
              </i>
              <strong>({row.count})</strong>
            </div>
          ))}
        </div>
        <div>
          <ProfileRating rating={reviews.rating} reviews={0} />
          <h3>Rating Breakdown</h3>
          {reviews.ratings.map((row) => (
            <div className="profile-rating-breakdown-row" key={row.label}>
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
        <form className="profile-review-search" role="search" onSubmit={(event) => event.preventDefault()}>
          <label className="sr-only" htmlFor="profileReviewSearch">
            Search reviews
          </label>
          <input id="profileReviewSearch" type="search" placeholder="Search reviews" />
          <button type="submit" aria-label="Search reviews">
            <Icon name="search" />
          </button>
        </form>
        <div>
          <span>1-5 out of {reviews.count} Reviews</span>
          <span>
            Sort By <strong>Most relevant</strong>
            <Icon name="chevronDown" />
          </span>
        </div>
      </div>

      <article className="profile-review-card">
        <header>
          <span className="profile-review-initial">{sample.name.slice(0, 1).toUpperCase()}</span>
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
          <p>{sample.text} <a href="#reviews">See more</a></p>
          <div className="profile-review-order-row">
            <dl>
              <div>
                <dt>{sample.price}</dt>
                <dd>Price</dd>
              </div>
              <div>
                <dt>{sample.duration}</dt>
                <dd>Duration</dd>
              </div>
            </dl>
            <Link to={`/gigs/${profile.services[0]?.id || "ai-website-chatbot"}`}>
              <img src={sample.serviceImage || profile.services[0]?.image} alt="" />
              <span>{sample.serviceTitle || profile.services[0]?.title}</span>
            </Link>
          </div>
        </div>
        <button className="profile-seller-response" type="button">
          <span>{profile.name.slice(0, 1)}</span>
          Seller's Response
          <Icon name="chevronDown" />
        </button>
      </article>

      <div className="profile-review-helpful">
        <span>Helpful?</span>
        <button type="button">
          <Icon name="thumbsUp" />
          Yes
        </button>
        <button type="button">
          <Icon name="thumbsDown" />
          No
        </button>
      </div>

      <button className="profile-show-more-reviews" type="button">
        Show More Reviews
      </button>
    </section>
  );
}

function ProfileTalentSection() {
  return (
    <section className="profile-talent-section">
      <div className="container">
        <h2>Find freelance talent - your way</h2>
        <div className="talent-way-grid">
          <ProfileTalentCard
            action="Post a brief"
            copy="Generate a brief with AI to receive a curated shortlist of freelancer offers."
            icon="document"
            title="Post a project brief"
          />
          <ProfileTalentCard
            action="Get started"
            copy="Save the endless search - we'll source, interview, and vet freelancers for you."
            icon="user"
            meta="Only $89"
            title="Let us find your freelancer"
          />
          <ProfileTalentCard
            action="Book free consultation"
            copy="Big project? No problem. We'll build a freelance team and fully execute your project."
            icon="verifiedUser"
            meta="Custom pricing"
            title="Get a team built for you"
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
        <Link to="/search/gigs?source=profile-talent-way">{action}</Link>
      </div>
    </article>
  );
}

function ProfileRating({ rating, reviews }) {
  return (
    <span className="profile-rating-line">
      {Array.from({ length: 5 }, (_, index) => (
        <Icon name="star" key={index} />
      ))}
      <strong>{rating.toFixed(1)}</strong>
      {reviews ? <a href="#reviews">({reviews})</a> : null}
    </span>
  );
}

function ProfileMessageBubble({ profile }) {
  return (
    <aside className="profile-message-bubble" aria-label={`Message ${profile.name}`}>
      <img src={profile.avatar} alt="" />
      <span className="profile-online-dot" aria-hidden="true"></span>
      <div>
        <strong>Message {profile.name}</strong>
        <span>Online - Avg. response time: {profile.responseTime}</span>
      </div>
    </aside>
  );
}

export default UserProfilePage;
