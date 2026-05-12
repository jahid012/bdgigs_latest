import { useState } from "react";
import { serviceFilters, services } from "../../data/homeData.js";
import { Icon, Rating } from "../common/Icons.jsx";

function FeaturedServices() {
  const [activeFilter, setActiveFilter] = useState("all");
  const [favorites, setFavorites] = useState(() => new Set());

  const toggleFavorite = (serviceId) => {
    setFavorites((current) => {
      const next = new Set(current);
      if (next.has(serviceId)) {
        next.delete(serviceId);
      } else {
        next.add(serviceId);
      }
      return next;
    });
  };

  return (
    <section className="section services-section" id="services">
      <div className="container">
        <div className="services-header">
          <div className="section-header">
            <span className="eyebrow">Featured services</span>
            <h2 className="section-title">Premium freelance services ready to start</h2>
            <p className="section-copy">
              Browse high-performing gigs from trusted sellers with clear pricing, strong reviews, and fast response
              times.
            </p>
          </div>
          <a className="btn btn-secondary services-header-action" href="#services">
            View all services
          </a>
        </div>

        <div className="service-filter-tabs" aria-label="Service filters">
          {serviceFilters.map((filter) => (
            <button
              className={`filter-tab${activeFilter === filter.value ? " active" : ""}`}
              type="button"
              aria-pressed={activeFilter === filter.value}
              key={filter.value}
              onClick={() => setActiveFilter(filter.value)}
            >
              {filter.label}
            </button>
          ))}
        </div>

        <div className="service-grid">
          {services.map((service) => {
            const isVisible = activeFilter === "all" || service.category === activeFilter;
            const isFavorite = favorites.has(service.id);

            return (
              <article
                className={`card service-card${isVisible ? "" : " is-hidden"}`}
                data-service-category={service.category}
                key={service.id}
              >
                <div className="service-thumb">
                  <img src={service.image} alt={service.imageAlt} loading="lazy" decoding="async" />
                  <button
                    className={`favorite-btn${isFavorite ? " is-favorite" : ""}`}
                    type="button"
                    aria-label={`Save ${service.pill} service`}
                    aria-pressed={isFavorite}
                    onClick={() => toggleFavorite(service.id)}
                  >
                    <Icon name="heart" />
                  </button>
                  <div className="service-badge-row">
                    <span className="service-category-pill">{service.pill}</span>
                    <span className="service-status-badge">{service.badge}</span>
                  </div>
                </div>

                <div className="service-body">
                  <div className="seller-profile">
                    <span className="seller-avatar">{service.initials}</span>
                    <div>
                      <strong>{service.seller}</strong>
                      <span className="seller-level">{service.level}</span>
                    </div>
                  </div>
                  <h3>{service.title}</h3>
                  <div className="service-meta-row">
                    <Rating value={service.rating} reviews={service.reviews} />
                    <span className="delivery-meta">
                      <Icon name="bolt" />
                      {service.delivery}
                    </span>
                  </div>
                  <div className="service-footer">
                    <span className="price">
                      <span>Starting at</span>
                      <strong>{service.price}</strong>
                    </span>
                    <a className="tag" href="#">
                      View
                    </a>
                  </div>
                </div>
              </article>
            );
          })}
        </div>
      </div>
    </section>
  );
}

export default FeaturedServices;
