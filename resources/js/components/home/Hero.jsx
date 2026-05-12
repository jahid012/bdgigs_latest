import { useCallback, useRef, useState } from "react";
import { heroCategories, popularTags } from "../../data/homeData.js";
import { useDismissOnInteractOutside } from "../../hooks/useDismissOnInteractOutside.js";
import { Icon } from "../common/Icons.jsx";

function Hero() {
  const [selectedCategory, setSelectedCategory] = useState("All Categories");
  const [isCategoryOpen, setIsCategoryOpen] = useState(false);
  const categoryRef = useRef(null);
  const closeCategoryMenu = useCallback(() => setIsCategoryOpen(false), []);

  useDismissOnInteractOutside(categoryRef, isCategoryOpen, closeCategoryMenu);

  return (
    <section className="hero">
      <video
        className="hero-bg-video"
        autoPlay
        muted
        loop
        playsInline
        preload="metadata"
        aria-hidden="true"
      >
        <source src="https://assets.mixkit.co/videos/4809/4809-720.mp4" type="video/mp4" />
      </video>

      <span className="hero-orbit hero-orbit-1">
        <Icon name="bolt" /> Fast Delivery
      </span>
      <span className="hero-orbit hero-orbit-2">
        <Icon name="verifiedUser" /> Verified Experts
      </span>
      <span className="hero-orbit hero-orbit-3">
        <Icon name="payment" /> Secure Payment
      </span>
      <span className="hero-orbit hero-orbit-4">
        <Icon name="star" /> 4.9 Avg Rating
      </span>

      <div className="container hero-grid">
        <div className="hero-copy">
          <span className="hero-badge">
            <Icon name="star" />
            Trusted by 25k+ businesses worldwide
          </span>
          <h1 className="hero-title">
            Find top freelancers for every <span className="hero-title-highlight">business need</span>
          </h1>
          <p className="hero-description">
            Connect with skilled freelancers, compare services, and get quality work delivered faster — all in one
            simple marketplace.
          </p>

          <form className="hero-search" role="search" aria-label="Search freelance services" onSubmit={(event) => event.preventDefault()}>
            <label className="hero-search-field">
              <Icon name="search" />
              <span className="sr-only">Search service</span>
              <input type="search" placeholder="What service are you looking for?" autoComplete="off" />
            </label>

            <div className={`hero-search-category${isCategoryOpen ? " is-open" : ""}`} ref={categoryRef}>
              <input type="hidden" value={selectedCategory} readOnly />
              <button
                className="hero-category-toggle"
                type="button"
                aria-haspopup="listbox"
                aria-expanded={isCategoryOpen}
                onClick={(event) => {
                  event.stopPropagation();
                  setIsCategoryOpen((open) => !open);
                }}
              >
                <span>{selectedCategory}</span>
                <Icon name="chevronDown" />
              </button>
              <div className="hero-category-menu" role="listbox" aria-label="Service category">
                {heroCategories.map((category) => (
                  <button
                    className="hero-category-option"
                    type="button"
                    role="option"
                    aria-selected={selectedCategory === category}
                    key={category}
                    onClick={() => {
                      setSelectedCategory(category);
                      setIsCategoryOpen(false);
                    }}
                  >
                    {category}
                  </button>
                ))}
              </div>
            </div>

            <button className="btn btn-primary" type="submit">
              Search
            </button>
          </form>

          <div className="popular-tags" aria-label="Popular searches">
            <span>Popular:</span>
            {popularTags.map((tag) => (
              <a className="tag" href="#services" key={tag}>
                {tag}
              </a>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

export default Hero;
