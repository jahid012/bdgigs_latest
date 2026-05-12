function SellerCTA() {
  return (
    <section className="seller-cta" id="seller">
      <div className="container">
        <div className="seller-cta-panel">
          <div>
            <span className="eyebrow">For freelancers</span>
            <h2>Turn your skills into a premium service business</h2>
            <p>
              Create packages, attract serious buyers, and manage orders with tools designed for independent experts
              and growing studios.
            </p>
            <div className="seller-cta-list" aria-label="Seller benefits">
              <span>Fast payouts</span>
              <span>Protected orders</span>
              <span>Portfolio-first profiles</span>
            </div>
          </div>
          <a className="btn btn-primary" href="#seller">
            Become a Seller
          </a>
        </div>
      </div>
    </section>
  );
}

export default SellerCTA;
