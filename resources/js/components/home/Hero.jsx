import { popularTags, trustedBrands } from "../../data/homeData.js";
import { Icon } from "../common/Icons.jsx";

function Hero({ onNavigate }) {
  const handleSearch = (event) => {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const query = String(formData.get("query") || "").trim();
    const queryString = query ? `?query=${encodeURIComponent(query)}&source=hero` : "?source=hero";
    onNavigate("/search/gigs", queryString);
  };

  return (
    <section className="hero">
      <video className="hero-bg-video" autoPlay muted loop playsInline preload="metadata" aria-hidden="true">
        <source src="https://assets.mixkit.co/videos/4809/4809-720.mp4" type="video/mp4" />
      </video>

      <div className="container hero-content">
        <div className="hero-copy">
          <h1 className="hero-title">
            Our freelancers
            <br />
            will take it from here
          </h1>

          <form className="hero-search" role="search" aria-label="Search freelance services" onSubmit={handleSearch}>
            <label className="hero-search-field">
              <span className="sr-only">Search service</span>
              <input name="query" type="search" placeholder="Search for any service..." autoComplete="off" />
            </label>
            <button className="hero-search-button" type="submit" aria-label="Search">
              <Icon name="search" />
            </button>
          </form>

          <div className="popular-tags" aria-label="Popular service searches">
            {popularTags.map((tag) => {
              const path = `/search/gigs?query=${encodeURIComponent(tag)}&source=hero-tag`;

              return (
                <a
                  className="hero-tag"
                  href={path}
                  key={tag}
                  onClick={(event) => {
                    event.preventDefault();
                    onNavigate(path);
                  }}
                >
                  {tag}
                  <Icon name="arrowRight" />
                </a>
              );
            })}
          </div>

          <div className="trusted-row" aria-label="Trusted by">
            <span>Trusted by:</span>
            {trustedBrands.map((brand) => (
              <strong key={brand}>{brand}</strong>
            ))}
          </div>
        </div>
      </div>

      <button className="hero-pause-button" type="button" aria-label="Pause background video">
        <span aria-hidden="true">II</span>
      </button>
    </section>
  );
}

export default Hero;
