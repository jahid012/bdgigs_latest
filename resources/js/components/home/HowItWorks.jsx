import { processSteps } from "../../data/homeData.js";
import { Icon } from "../common/Icons.jsx";

function HowItWorks() {
  return (
    <section className="section how-section" id="how-it-works">
      <div className="container">
        <div className="section-header center">
          <span className="eyebrow">How it works</span>
          <h2 className="section-title">From idea to delivery in three simple steps</h2>
          <p className="section-copy">
            BDGigs gives buyers a clear path from discovery to secure checkout, collaboration, and final approval.
          </p>
        </div>

        <div className="steps-grid">
          {processSteps.map((step) => (
            <article className={`card step-card${step.featured ? " featured" : ""}`} key={step.number}>
              <div className="step-top">
                <span className="step-icon" aria-hidden="true">
                  <Icon name={step.icon} />
                </span>
                <span className="step-number">{step.number}</span>
              </div>
              <div>
                <h3>{step.title}</h3>
                <p>{step.description}</p>
              </div>
              <ul className="step-highlights" aria-label={`${step.title} benefits`}>
                {step.highlights.map((highlight) => (
                  <li key={highlight}>
                    <span>{highlight}</span>
                  </li>
                ))}
              </ul>
            </article>
          ))}
        </div>

        <div className="how-cta" aria-label="How it works actions">
          <a className="btn btn-primary" href="#services">
            Start your first project
          </a>
          <a className="btn btn-secondary" href="#seller">
            Sell your services
          </a>
        </div>
      </div>
    </section>
  );
}

export default HowItWorks;
