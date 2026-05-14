import { useRef, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { aiGigDetailId, getGigDetail, getRecommendedGigs } from "../data/gigDetailsData.js";
import { listingGigs } from "../data/gigListingData.js";
import { profilePathForSeller } from "../data/userProfileData.js";
import { useDismissOnInteractOutside } from "../hooks/useDismissOnInteractOutside.js";
import { Icon } from "../components/common/Icons.jsx";
import Footer from "../components/layout/Footer.jsx";
import Header from "../components/layout/Header.jsx";

const packageFeatureRows = [
  "Functional Web App",
  "Desktop Application",
  "Integration of an AI model to existing app",
  "AI Model Fine-tuning",
  "Chatbot integration",
  "Source Code",
];

function GigDetailsPage({ onNavigate }) {
  const { gigId } = useParams();
  const detail = getGigDetail(gigId);
  const [activeImage, setActiveImage] = useState(0);
  const [activePackage, setActivePackage] = useState(detail.packages[0].id);
  const [openFaq, setOpenFaq] = useState(null);
  const [isReportOpen, setIsReportOpen] = useState(false);
  const reportRef = useRef(null);
  const selectedPackage = detail.packages.find((pkg) => pkg.id === activePackage) || detail.packages[0];

  useDismissOnInteractOutside(reportRef, isReportOpen, () => setIsReportOpen(false));

  const changeImage = (direction) => {
    setActiveImage((current) => (current + direction + detail.gallery.length) % detail.gallery.length);
  };

  return (
    <div className="gig-detail-page">
      <Header enableMarketplaceHeader={false} forceSearch onNavigate={onNavigate} />

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
              <SellerProfile seller={detail.seller} />
              <PortfolioSection portfolio={detail.portfolio} />
              <ComparePackages packages={detail.packages} onSelect={setActivePackage} />
              <RecommendedSection currentId={detail.id} />
              <FAQSection detail={detail} onToggle={setOpenFaq} openFaq={openFaq} />
              <SourcingCTA />
              <ReviewsSection detail={detail} />
            </article>

            <aside className="gig-detail-sidebar" aria-label="Gig packages">
              <TopActions
                isReportOpen={isReportOpen}
                onToggleReport={() => setIsReportOpen((open) => !open)}
                reportRef={reportRef}
                reviewCount={detail.reviews.count}
              />
              <PackageCard
                activePackage={activePackage}
                onPackageChange={setActivePackage}
                packageData={selectedPackage}
                packages={detail.packages}
              />
            </aside>
          </div>
        </div>

        <GigDetailBottomSections detail={detail} onNavigate={onNavigate} />
      </main>

      <MessageBubble seller={detail.seller} />
      <Footer />
    </div>
  );
}

function GigHero({ activeImage, detail, onChangeImage, onSelectImage }) {
  return (
    <section className="gig-detail-hero" aria-labelledby="gigTitle">
      <nav className="gig-detail-breadcrumb" aria-label="Breadcrumb">
        <Link to="/" aria-label="Home">
          <Icon name="home" />
        </Link>
        {detail.breadcrumbs.map((crumb) => (
          <span key={crumb}>
            <span aria-hidden="true">/</span>
            <Link to={`/search/gigs?query=${encodeURIComponent(crumb)}&source=detail-breadcrumb`}>{crumb}</Link>
          </span>
        ))}
      </nav>

      <h1 id="gigTitle">{detail.title}</h1>
      <SellerMini seller={detail.seller} />

      <div className="gig-gallery">
        <button type="button" aria-label="Previous image" onClick={() => onChangeImage(-1)}>
          <Icon name="arrowRight" />
        </button>
        <img src={detail.gallery[activeImage]} alt={`${detail.title} preview ${activeImage + 1}`} />
        <button type="button" aria-label="Next image" onClick={() => onChangeImage(1)}>
          <Icon name="arrowRight" />
        </button>
      </div>

      <div className="gig-gallery-thumbs" aria-label="Gig preview thumbnails">
        {detail.gallery.map((image, index) => (
          <button className={activeImage === index ? "is-active" : ""} type="button" key={image} onClick={() => onSelectImage(index)}>
            <img src={image} alt="" />
          </button>
        ))}
      </div>
    </section>
  );
}

function SellerMini({ seller }) {
  return (
    <div className="gig-seller-mini">
      <Link className="gig-seller-mini-avatar" to={profilePathForSeller(seller.name)} aria-label={`View ${seller.name} profile`}>
        <img src={seller.avatar} alt="" />
      </Link>
      <div>
        <Link className="gig-seller-mini-name" to={profilePathForSeller(seller.name)}>
          <strong>{seller.name}</strong>
        </Link>
        <span>{seller.level}</span>
        <RatingLine rating={seller.rating} reviews={seller.reviews} />
      </div>
    </div>
  );
}

function RatingLine({ rating, reviews }) {
  return (
    <span className="detail-rating-line">
      {Array.from({ length: 5 }, (_, index) => (
        <Icon name="star" key={index} />
      ))}
      <strong>{rating.toFixed(1)}</strong>
      {reviews ? <Link to="#reviews">({reviews} reviews)</Link> : null}
    </span>
  );
}

function TopActions({ isReportOpen, onToggleReport, reportRef, reviewCount }) {
  return (
    <div className="gig-detail-actions" ref={reportRef}>
      <button type="button" aria-label="Open menu">
        <Icon name="menu" />
      </button>
      <button type="button" aria-label="Save gig">
        <Icon name="heart" />
      </button>
      <span>{reviewCount}</span>
      <button type="button" aria-label="Share gig">
        <Icon name="share" />
      </button>
      <button type="button" aria-label="More options" aria-expanded={isReportOpen} onClick={onToggleReport}>
        <Icon name="moreHorizontal" />
      </button>
      {isReportOpen ? (
        <div className="report-popover">
          <button type="button">
            <Icon name="flag" />
            Report an issue
          </button>
        </div>
      ) : null}
    </div>
  );
}

function PackageCard({ activePackage, onPackageChange, packageData, packages }) {
  return (
    <section className="package-card">
      <div className="package-tabs" role="tablist" aria-label="Package tiers">
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
        <strong className="package-price">${packageData.price.toLocaleString()}</strong>
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
            <li className={packageData.features[feature] ? "" : "is-muted"} key={feature}>
              <span aria-hidden="true"></span>
              {feature}
            </li>
          ))}
        </ul>

        <button className="package-continue-button" type="button">
          Continue
          <Icon name="arrowRight" />
        </button>
        <button className="package-contact-button" type="button">
          Contact me
          <Icon name="chevronDown" />
        </button>
      </div>

      <div className="hourly-offer-card">
        <div>
          <img src="/assets/img/gig_images/18.png" alt="" />
          <strong>Need flexibility when hiring?</strong>
        </div>
        <p>Hire by the hour, ideal for long-term projects with flexible hours and weekly payments.</p>
        <div>
          <strong>$15/hour</strong>
          <button type="button">Request hourly offer</button>
        </div>
      </div>
    </section>
  );
}

function AboutGig({ detail }) {
  return (
    <section className="detail-section about-gig-section">
      <h2>About this gig</h2>
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
        <strong>Why Work With us?</strong>
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

function SellerProfile({ seller }) {
  return (
    <section className="detail-section seller-profile-section">
      <h2>
        Get to know <Link to={profilePathForSeller(seller.name)}>{seller.name}</Link>
      </h2>
      <div className="seller-profile-header">
        <Link to={profilePathForSeller(seller.name)} aria-label={`View ${seller.name} profile`}>
          <img src={seller.avatar} alt="" />
        </Link>
        <div>
          <Link to={profilePathForSeller(seller.name)}>
            <strong>{seller.name}</strong>
          </Link>
          <p>{seller.tagline}</p>
          <RatingLine rating={seller.rating} reviews={seller.reviews} />
        </div>
      </div>
      <div className="seller-profile-actions">
        <button type="button">Contact me</button>
        <button type="button">
          <Icon name="video" />
          Book a consultation
        </button>
      </div>
      <div className="seller-profile-card">
        <dl>
          <div>
            <dt>From</dt>
            <dd>{seller.from}</dd>
          </div>
          <div>
            <dt>Member since</dt>
            <dd>{seller.memberSince}</dd>
          </div>
          <div>
            <dt>Avg. response time</dt>
            <dd>{seller.responseTime}</dd>
          </div>
          <div>
            <dt>Last delivery</dt>
            <dd>{seller.lastDelivery}</dd>
          </div>
          <div>
            <dt>Languages</dt>
            <dd>{seller.languages}</dd>
          </div>
        </dl>
        <p>{seller.bio}</p>
      </div>
    </section>
  );
}

function PortfolioSection({ portfolio }) {
  return (
    <section className="detail-section portfolio-section">
      <h2>My Portfolio</h2>
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
      <div className="portfolio-thumbs">
        {portfolio.thumbnails.map((image, index) => (
          <button className={index === 0 ? "is-active" : ""} type="button" key={`${image}-${index}`}>
            <img src={image} alt="" />
          </button>
        ))}
        <button type="button">+2 Projects</button>
      </div>
    </section>
  );
}

function ComparePackages({ packages, onSelect }) {
  return (
    <section className="detail-section compare-section">
      <h2>Compare packages</h2>
      <div className="compare-table-wrap">
        <table className="compare-table">
          <thead>
            <tr>
              <th>Package</th>
              {packages.map((pkg) => (
                <th key={pkg.id}>
                  <strong>${pkg.price.toLocaleString()}</strong>
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
                    <span className={`compare-check${pkg.features[feature] ? "" : " is-muted"}`} aria-label={pkg.features[feature] ? "Included" : "Not included"}></span>
                  </td>
                ))}
              </tr>
            ))}
            <tr>
              <td>Revisions</td>
              {packages.map((pkg) => (
                <td key={pkg.id}>{pkg.revisions.replace(" Revisions", "")}</td>
              ))}
            </tr>
            <tr>
              <td>Delivery Time</td>
              {packages.map((pkg) => (
                <td key={pkg.id}>{pkg.deliveryTime}</td>
              ))}
            </tr>
            <tr>
              <td>Total</td>
              {packages.map((pkg) => (
                <td key={pkg.id}>${pkg.price.toLocaleString()}</td>
              ))}
            </tr>
            <tr>
              <td></td>
              {packages.map((pkg) => (
                <td key={pkg.id}>
                  <button type="button" onClick={() => onSelect(pkg.id)}>
                    Select
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
  const recommended = getRecommendedGigs(currentId === aiGigDetailId ? "" : currentId);

  return (
    <section className="detail-section recommended-detail-section">
      <h2>Recommended for you</h2>
      <div className="detail-recommend-grid">
        {recommended.map((gig) => (
          <Link className="detail-recommend-card" to={`/gigs/${gig.id}`} key={gig.id}>
            <img src={gig.image} alt="" />
            <div>
              <strong>{gig.seller}</strong>
              <span>{gig.level}</span>
            </div>
            <p>{gig.title}</p>
            <RatingLine rating={gig.rating} reviews={gig.reviews} />
            <b>From ${gig.price}</b>
            {gig.consultation ? (
              <small>
                <Icon name="video" />
                Offers video consultations
              </small>
            ) : null}
          </Link>
        ))}
      </div>
    </section>
  );
}

function FAQSection({ detail, onToggle, openFaq }) {
  return (
    <section className="detail-section faq-section">
      <h2>FAQ</h2>
      {detail.faq.map((item, index) => (
        <div className="faq-item" key={item.question}>
          <button type="button" aria-expanded={openFaq === index} onClick={() => onToggle(openFaq === index ? null : index)}>
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
  return (
    <section className="detail-sourcing-cta">
      <div>
        <h2>
          Get the right freelancer,
          <span> without the search</span>
        </h2>
        <p>We'll handle the sourcing, interviewing, and vetting so you don't have to.</p>
        <button type="button">
          Source for me
          <Icon name="arrowRight" />
        </button>
      </div>
      <div className="sourcing-profile-stack" aria-hidden="true">
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

function ReviewsSection({ detail }) {
  const { reviews } = detail;
  const relatedTags = detail.relatedTags || ["Ai chatbot", "Ai developer", "Full stack website", "Ai website", "Ai software"];

  return (
    <section className="detail-section reviews-section" id="reviews">
      <h2>Reviews</h2>
      <div className="reviews-summary">
        <div>
          <h3>{reviews.count} reviews for this Gig</h3>
          {reviews.breakdown.map((row) => (
            <div className="review-breakdown-row" key={row.label}>
              <span>{row.label}</span>
              <i>
                <b style={{ width: `${row.value}%` }}></b>
              </i>
              <strong>({row.count})</strong>
            </div>
          ))}
        </div>
        <div>
          <RatingLine rating={reviews.rating} reviews={0} />
          <h3>Rating Breakdown</h3>
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

      <form className="review-search" role="search" onSubmit={(event) => event.preventDefault()}>
        <label className="sr-only" htmlFor="reviewSearch">
          Search reviews
        </label>
        <input id="reviewSearch" type="search" placeholder="Search reviews" />
        <button type="submit" aria-label="Search reviews">
          <Icon name="search" />
        </button>
      </form>
      <div className="review-controls">
        <span>
          Sort By <strong>Most relevant</strong>
          <Icon name="chevronDown" />
        </span>
        <label>
          <input type="checkbox" />
          Only show reviews with files (10)
        </label>
      </div>

      <article className="review-card">
        <div className="review-card-header">
          <img src="https://images.pexels.com/photos/91227/pexels-photo-91227.jpeg?auto=compress&cs=tinysrgb&w=80" alt="" />
          <div>
            <strong>{reviews.sample.name}</strong>
            <span>{reviews.sample.country}</span>
          </div>
        </div>
        <div className="review-card-body">
          <div>
            <RatingLine rating={reviews.sample.rating} reviews={0} />
            <span>{reviews.sample.date}</span>
          </div>
          <p>{reviews.sample.text}</p>
          <dl>
            <div>
              <dt>{reviews.sample.price}</dt>
              <dd>Price</dd>
            </div>
            <div>
              <dt>{reviews.sample.duration}</dt>
              <dd>Duration</dd>
            </div>
          </dl>
        </div>
        <img className="review-delivery-image" src={reviews.sample.image} alt="" />
        <button className="seller-response-toggle" type="button">
          Seller's Response
          <Icon name="chevronDown" />
        </button>
      </article>

      <div className="review-helpful-row">
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

      <button className="show-more-reviews-button" type="button">
        Show More Reviews
      </button>

      <section className="related-tags-section" aria-labelledby="relatedTagsTitle">
        <h2 id="relatedTagsTitle">Related tags</h2>
        <div className="related-tag-list">
          {relatedTags.map((tag) => (
            <Link to={`/search/gigs?query=${encodeURIComponent(tag)}&source=related-tags`} key={tag}>
              {tag}
            </Link>
          ))}
        </div>
      </section>
    </section>
  );
}

function GigDetailBottomSections({ detail, onNavigate }) {
  const [isHistoryHidden, setIsHistoryHidden] = useState(false);
  const moreFromRef = useRef(null);
  const viewedRef = useRef(null);
  const historyRef = useRef(null);
  const baseGigs = listingGigs.filter((gig) => gig.id !== detail.id);
  const byId = (id) => listingGigs.find((gig) => gig.id === id) || listingGigs[0];
  const sellerGig = (id, overrides) => ({
    ...byId(id),
    seller: detail.seller.name,
    avatar: detail.seller.avatar,
    level: detail.seller.level,
    consultation: true,
    ...overrides,
  });

  const moreFromSeller = [
    sellerGig("android-codecanyon-reskin", {
      title: "I will do android and ios mobile app development using flutter as your mobile app developer",
      image: "/assets/img/gig_images/14.png",
      price: 400,
      rating: 4.7,
      reviews: 2,
    }),
    sellerGig("codecanyon-hosting", {
      title: "I will install and set up your personal ai assistant and automation workflow",
      image: "/assets/img/gig_images/9.png",
      price: 60,
      rating: 5,
      reviews: 18,
    }),
    sellerGig("nextjs-codecanyon", {
      title: "I will do b2b lead generation, linkedin lead generation and web research",
      image: "/assets/img/gig_images/15.png",
      price: 20,
      rating: 5,
      reviews: 31,
    }),
    sellerGig("wordpress-redesign", {
      title: "I will convert figma, xd and psd to wordpress and custom websites",
      image: "/assets/img/gig_images/3.png",
      price: 80,
      rating: 4.9,
      reviews: 64,
    }),
  ];

  const viewedAlso = [
    {
      ...byId("full-stack-website"),
      seller: "Xtreeme Tech",
      badge: "Top Rated",
      title: "Our agency will integrate chatgpt openai API in wordpress website",
      image: "/assets/img/gig_images/1.png",
      price: 90,
      rating: 4.9,
      reviews: 139,
      consultation: true,
    },
    {
      ...byId("wordpress-transfer"),
      seller: "Anil Chaudhary",
      title: "I will integrate chatgpt openai API in wordpress website for automation",
      image: "/assets/img/gig_images/6.png",
      price: 90,
      rating: 5,
      reviews: 9,
    },
    {
      ...byId("shopify-store"),
      seller: "Weballures",
      badge: "Top Rated",
      title: "Our agency will ai website, ai web app creation, ai website development",
      image: "/assets/img/gig_images/12.png",
      price: 125,
      rating: 5,
      reviews: 18,
    },
    {
      ...byId("wix-redesign"),
      seller: "Abdul Ahad",
      title: "I will develop ai website, ai chatbot, ai web application and ai software",
      image: "/assets/img/gig_images/16.png",
      price: 60,
      rating: 5,
      reviews: 6,
    },
    {
      ...byId("codecanyon-envato"),
      seller: "Ahsaan Ali Khan",
      title: "I will build ai website, ai chatbot, ai web application, custom website",
      image: "/assets/img/gig_images/10.png",
      price: 110,
      rating: 5,
      reviews: 11,
      consultation: true,
    },
  ];

  const historyGigs = [
    sellerGig("full-stack-website", {
      seller: "Jasper Studio",
      avatar: byId("full-stack-website").avatar,
      title: "I will create a saas gpt4 ai content and image generator like jasper or chatgpt",
      image: "/assets/img/gig_images/20.png",
    }),
    sellerGig("shopify-store", {
      seller: "AI Platform",
      avatar: byId("shopify-store").avatar,
      title: "I will create saas ai content generator generative ai platform website",
      image: "/assets/img/gig_images/21.png",
    }),
    ...baseGigs.slice(0, 6),
  ].slice(0, 8);

  const scrollStrip = (ref, direction) => {
    ref.current?.scrollBy({ left: direction * 290, behavior: "smooth" });
  };

  const goTo = (path) => {
    if (onNavigate) {
      onNavigate(path);
      return;
    }
    window.location.assign(path);
  };

  return (
    <section className="gig-detail-bottom-surface" aria-label="More services and hiring options">
      <div className="container">
        <DetailGigStrip
          actionLabel={`More from ${detail.seller.name}`}
          heading={
            <>
              More from <Link to={profilePathForSeller(detail.seller.name)}>{detail.seller.name}</Link>
            </>
          }
          gigs={moreFromSeller}
          rowRef={moreFromRef}
          onScroll={(direction) => scrollStrip(moreFromRef, direction)}
        />

        <DetailGigStrip
          actionLabel="People who viewed this service also viewed"
          heading="People Who Viewed This Service Also Viewed"
          gigs={viewedAlso}
          rowRef={viewedRef}
          onScroll={(direction) => scrollStrip(viewedRef, direction)}
        />

        {!isHistoryHidden ? (
          <DetailHistoryStrip
            gigs={historyGigs}
            onClear={() => setIsHistoryHidden(true)}
            onScroll={(direction) => scrollStrip(historyRef, direction)}
            rowRef={historyRef}
          />
        ) : null}

        <section className="talent-way-section detail-talent-section" aria-labelledby="detailTalentWayTitle">
          <h2 id="detailTalentWayTitle">Find freelance talent - your way</h2>
          <div className="talent-way-grid">
            <DetailTalentWayCard
              action="Post a brief"
              copy="Generate a brief with AI to receive a curated shortlist of freelancer offers."
              icon="document"
              onClick={() => goTo("/search/gigs?query=project%20brief&source=detail-talent-way")}
              title="Post a project brief"
            />
            <DetailTalentWayCard
              action="Get started"
              copy="Save the endless search - we'll source, interview, and vet freelancers for you."
              icon="user"
              meta="Only $89"
              onClick={() => goTo("/search/gigs?query=expert%20sourcing&source=detail-talent-way")}
              title="Let us find your freelancer"
            />
            <DetailTalentWayCard
              action="Book free consultation"
              copy="Big project? No problem. We'll build a freelance team and fully execute your project."
              icon="verifiedUser"
              meta="Custom pricing"
              onClick={() => goTo("/search/gigs?query=freelance%20team&source=detail-talent-way")}
              title="Get a team built for you"
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
          <button type="button" aria-label={`Scroll ${actionLabel} left`} onClick={() => onScroll(-1)}>
            <Icon name="arrowRight" />
          </button>
          <button type="button" aria-label={`Scroll ${actionLabel} right`} onClick={() => onScroll(1)}>
            <Icon name="arrowRight" />
          </button>
        </div>
      </div>
      <div className="detail-strip-row" ref={rowRef}>
        {gigs.map((gig) => (
          <DetailGigStripCard gig={gig} key={`${actionLabel}-${gig.id}-${gig.title}`} />
        ))}
      </div>
    </section>
  );
}

function DetailGigStripCard({ gig }) {
  return (
    <article className="detail-strip-card">
      <div className="detail-strip-media">
        <Link to={`/gigs/${gig.id}`} aria-label={`View ${gig.title}`}>
          <img src={gig.image} alt={`${gig.title} preview`} loading="lazy" decoding="async" />
        </Link>
        <button type="button" aria-label={`Save ${gig.title}`}>
          <Icon name="heart" />
        </button>
        {gig.consultation ? (
          <span className="detail-strip-play" aria-hidden="true">
            <Icon name="play" />
          </span>
        ) : null}
      </div>
      <div className="detail-strip-seller-row">
        <Link className="gig-seller-profile-link" to={profilePathForSeller(gig.seller)}>
          <span className="gig-avatar">
            <img src={gig.avatar} alt="" loading="lazy" decoding="async" />
          </span>
          <strong>{gig.seller}</strong>
        </Link>
        {gig.badge ? <span className="detail-strip-badge">{gig.badge}</span> : null}
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
      <strong className="detail-strip-price">From ${gig.price}</strong>
      {gig.consultation ? (
        <span className="detail-strip-consultation">
          <Icon name="video" />
          Offers video consultations
        </span>
      ) : null}
    </article>
  );
}

function DetailHistoryStrip({ gigs, onClear, onScroll, rowRef }) {
  return (
    <section className="detail-history-section" aria-labelledby="detailBrowsingHistoryTitle">
      <div className="detail-strip-heading">
        <h2 id="detailBrowsingHistoryTitle">Your Browsing History</h2>
        <div className="detail-history-actions">
          <button type="button" onClick={onClear}>
            Clear All
          </button>
          <span aria-hidden="true"></span>
          <Link to="/search/gigs?source=browsing-history">See All</Link>
          <button type="button" aria-label="Scroll browsing history left" onClick={() => onScroll(-1)}>
            <Icon name="arrowRight" />
          </button>
          <button type="button" aria-label="Scroll browsing history right" onClick={() => onScroll(1)}>
            <Icon name="arrowRight" />
          </button>
        </div>
      </div>
      <div className="detail-history-row" ref={rowRef}>
        {gigs.map((gig) => (
          <Link className="detail-history-card" to={`/gigs/${gig.id}`} key={`history-${gig.id}-${gig.title}`}>
            <span className="history-gig-media">
              <img src={gig.image} alt="" loading="lazy" decoding="async" />
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

function DetailTalentWayCard({ action, copy, icon, meta = "", onClick, title }) {
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

function MessageBubble({ seller }) {
  return (
    <aside className="seller-message-bubble" aria-label={`Message ${seller.name}`}>
      <img src={seller.avatar} alt="" />
      <div>
        <strong>Message {seller.name}</strong>
        <span>Away - Avg. response time: {seller.responseTime}</span>
      </div>
    </aside>
  );
}

export default GigDetailsPage;
