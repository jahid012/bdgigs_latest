import { testimonials } from "../../data/homeData.js";
import { Rating } from "../common/Icons.jsx";

function Testimonials() {
  return (
    <section className="section testimonials-section">
      <div className="container">
        <div className="section-header center">
          <span className="eyebrow">Testimonials</span>
          <h2 className="section-title">Teams use BDGigs to hire faster</h2>
          <p className="section-copy">
            Buyers rely on clear service packages, transparent reviews, and protected collaboration.
          </p>
        </div>

        <div className="testimonial-grid">
          {testimonials.map((testimonial) => (
            <article className="card testimonial-card" key={testimonial.name}>
              <div className="testimonial-top">
                <div className="testimonial-author">
                  <span className="avatar">{testimonial.initials}</span>
                  <div>
                    <h3>{testimonial.name}</h3>
                    <span>{testimonial.role}</span>
                  </div>
                </div>
                <Rating value={testimonial.rating} />
              </div>
              <p>{testimonial.feedback}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}

export default Testimonials;
