import { useState } from "react";
import { services } from "../../data/homeData.js";
import { Icon, Rating } from "../common/Icons.jsx";

function FeaturedServices() {
  const [favorites, setFavorites] = useState(() => new Set());
  const visibleServices = services.slice(0, 5);

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
    <section className="recently-viewed-section" id="services">
      <div className="container">
        <div className="recently-viewed-heading">
          <h2>Recently Viewed & More</h2>
        </div>

        <div className="recently-viewed-row">
          {visibleServices.map((service) => {
            const isFavorite = favorites.has(service.id);

            return (
              <article className="gig-card" key={service.id}>
                <div className="gig-thumb">
                  <img src={service.image} alt={service.imageAlt} loading="lazy" decoding="async" />
                  <button className="gig-play-button" type="button" aria-label={`Preview ${service.title}`}>
                    <Icon name="play" />
                  </button>
                  <button
                    className={`gig-favorite-button${isFavorite ? " is-favorite" : ""}`}
                    type="button"
                    aria-label={`Save ${service.title}`}
                    aria-pressed={isFavorite}
                    onClick={() => toggleFavorite(service.id)}
                  >
                    <Icon name="heart" />
                  </button>
                </div>

                <div className="gig-seller-row">
                  <span className="gig-seller-avatar">{service.initials}</span>
                  <strong>{service.seller}</strong>
                  <span>{service.level}</span>
                </div>

                <h3>{service.title}</h3>

                <div className="gig-rating-row">
                  <Rating value={service.rating} reviews={service.reviews} />
                </div>

                <strong className="gig-price">From {service.price}</strong>
                {service.id === "brand-identity" ? <span className="gig-consultation">Offers video consultations</span> : null}
              </article>
            );
          })}

          <button className="gig-carousel-button" type="button" aria-label="View more services">
            <Icon name="arrowRight" />
          </button>
        </div>
      </div>
    </section>
  );
}

export default FeaturedServices;
