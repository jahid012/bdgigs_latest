import { useRef } from "react";
import { guideCards } from "../../data/homeData.js";
import { Icon } from "../common/Icons.jsx";

function Testimonials({ onNavigate }) {
  const guidesRef = useRef(null);

  const scrollGuides = () => {
    guidesRef.current?.scrollBy({ left: 360, behavior: "smooth" });
  };

  return (
    <>
      <section className="guides-section" id="guides" aria-labelledby="guidesTitle">
        <div className="container">
          <div className="guides-heading">
            <h2 id="guidesTitle">Guides to help you grow</h2>
            <a
              href="/search/gigs?query=guides&source=home"
              onClick={(event) => {
                event.preventDefault();
                onNavigate("/search/gigs?query=guides&source=home");
              }}
            >
              See more guides
            </a>
          </div>

          <div className="guide-card-row" ref={guidesRef}>
            {guideCards.map((guide) => (
              <article className="guide-card" key={guide.title}>
                <a
                  href={`/search/gigs?query=${encodeURIComponent(guide.title)}&source=guide-card`}
                  onClick={(event) => {
                    event.preventDefault();
                    onNavigate(`/search/gigs?query=${encodeURIComponent(guide.title)}&source=guide-card`);
                  }}
                >
                  <img src={guide.image} alt="" loading="eager" decoding="async" />
                  <h3>{guide.title}</h3>
                </a>
              </article>
            ))}
          </div>

          <button className="guide-carousel-button" type="button" aria-label="View more guides" onClick={scrollGuides}>
            <Icon name="arrowRight" />
          </button>
        </div>
      </section>

      <section className="fingerprints-cta-section" aria-labelledby="fingerprintsTitle">
        <div className="container">
          <div className="fingerprints-cta-panel">
            <h2 id="fingerprintsTitle">
              Freelance services at your <span>fingertips</span>
            </h2>
            <a href="/register">Join BDGigs</a>
          </div>
        </div>
      </section>
    </>
  );
}

export default Testimonials;
