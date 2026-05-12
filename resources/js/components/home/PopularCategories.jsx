import { categories } from "../../data/homeData.js";
import { Icon } from "../common/Icons.jsx";

function PopularCategories() {
  return (
    <section className="section categories-section" id="categories">
      <div className="container">
        <div className="section-header center">
          <span className="eyebrow">Popular categories</span>
          <h2 className="section-title">Explore talent by business outcome</h2>
          <p className="section-copy">
            Find curated freelance services across creative, technical, marketing, and AI-powered work.
          </p>
        </div>

        <div className="category-grid">
          {categories.map((category) => (
            <a
              className={`card category-card${category.featured ? " featured" : ""}`}
              href="#services"
              aria-label={`Explore ${category.title} services`}
              key={category.title}
            >
              <div className="category-top">
                <span className="icon-box" aria-hidden="true">
                  <Icon name={category.icon} />
                </span>
                {category.badge ? <span className="category-badge">{category.badge}</span> : null}
              </div>
              <div>
                <h3>{category.title}</h3>
                <p>{category.description}</p>
              </div>
              <div className="category-meta">
                <strong>{category.count}</strong>
                <span>services available</span>
              </div>
              <div className="category-tags">
                {category.tags.map((tag) => (
                  <span key={tag}>{tag}</span>
                ))}
              </div>
              <div className="category-footer">
                Explore category <Icon name="arrowRight" />
              </div>
            </a>
          ))}
        </div>

        <div className="category-section-cta">
          <a className="btn btn-secondary" href="#services">
            Explore all categories
          </a>
        </div>
      </div>
    </section>
  );
}

export default PopularCategories;
