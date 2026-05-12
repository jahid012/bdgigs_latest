import { useCallback, useRef, useState } from "react";
import { exploreLinks, navLinks } from "../../data/siteNavigation.js";
import { useDismissOnInteractOutside } from "../../hooks/useDismissOnInteractOutside.js";
import { useScrolledPast } from "../../hooks/useScrolledPast.js";
import { BrandMark, Icon } from "../common/Icons.jsx";

function Header({ onNavigate }) {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isExploreOpen, setIsExploreOpen] = useState(false);
  const exploreRef = useRef(null);
  const isScrolled = useScrolledPast(24);
  const closeExploreMenu = useCallback(() => setIsExploreOpen(false), []);

  useDismissOnInteractOutside(exploreRef, isExploreOpen, closeExploreMenu);

  const goHome = (event, hash = "") => {
    event.preventDefault();
    setIsMenuOpen(false);
    setIsExploreOpen(false);
    onNavigate("home", hash);
  };

  const goDashboard = (event) => {
    event.preventDefault();
    setIsMenuOpen(false);
    setIsExploreOpen(false);
    onNavigate("dashboard");
  };

  const goSellerDashboard = (event) => {
    event.preventDefault();
    setIsMenuOpen(false);
    setIsExploreOpen(false);
    onNavigate("seller-dashboard");
  };

  const headerClass = ["site-header", isScrolled ? "is-scrolled" : "", isMenuOpen ? "has-open-menu" : ""]
    .filter(Boolean)
    .join(" ");

  return (
    <header className={headerClass}>
      <div className="container">
        <div className="nav-shell">
          <a className="brand" href="/" aria-label="BDGigs home" onClick={(event) => goHome(event)}>
            <BrandMark />
            BDGigs
          </a>

          <nav className="nav-links" aria-label="Primary navigation">
            <div className="nav-dropdown" ref={exploreRef}>
              <button
                className={`nav-dropdown-trigger${isExploreOpen ? " is-open" : ""}`}
                type="button"
                aria-haspopup="true"
                aria-expanded={isExploreOpen}
                onClick={(event) => {
                  event.stopPropagation();
                  setIsExploreOpen((open) => !open);
                }}
              >
                Explore
                <Icon name="chevronDown" />
              </button>
              <div className={`nav-dropdown-panel${isExploreOpen ? " is-open" : ""}`}>
                <div className="nav-dropdown-grid">
                  {exploreLinks.map((link) => (
                    <a key={link.label} href={link.hash} onClick={(event) => goHome(event, link.hash)}>
                      <span className="nav-dropdown-icon" aria-hidden="true">
                        <Icon name="spark" />
                      </span>
                      <span>
                        <strong>{link.label}</strong>
                        <small>{link.copy}</small>
                      </span>
                    </a>
                  ))}
                </div>
                <div className="nav-dropdown-cta">
                  <span>
                    <strong>Ready to manage work?</strong>
                    <small>Jump into buyer or seller tools.</small>
                  </span>
                  <div>
                    <a href="/dashboard" onClick={goDashboard}>
                      Buyer
                    </a>
                    <a href="/dashboard/seller" onClick={goSellerDashboard}>
                      Seller
                    </a>
                  </div>
                </div>
              </div>
            </div>
            {navLinks.map((link) => (
              <a key={link.hash} href={link.hash} onClick={(event) => goHome(event, link.hash)}>
                {link.label}
              </a>
            ))}
          </nav>

          <div className="nav-actions">
            <a className="btn btn-ghost" href="/dashboard" onClick={goDashboard}>
              Sign In
            </a>
            <a className="btn btn-primary" href="#join" onClick={(event) => goHome(event, "#services")}>
              Join
            </a>
          </div>

          <button
            className={`nav-toggle${isMenuOpen ? " is-open" : ""}`}
            type="button"
            aria-label={isMenuOpen ? "Close menu" : "Open menu"}
            aria-expanded={isMenuOpen}
            aria-controls="mobileMenu"
            onClick={() => setIsMenuOpen((open) => !open)}
          >
            <span aria-hidden="true"></span>
          </button>
        </div>

        <div className={`mobile-menu${isMenuOpen ? " is-open" : ""}`} id="mobileMenu">
          <div className="mobile-menu-panel">
            <div className="mobile-menu-group">
              <span>Explore</span>
              {exploreLinks.map((link) => (
                <a key={link.label} href={link.hash} onClick={(event) => goHome(event, link.hash)}>
                  {link.label}
                </a>
              ))}
            </div>
            {navLinks.map((link) => (
              <a key={link.hash} href={link.hash} onClick={(event) => goHome(event, link.hash)}>
                {link.label}
              </a>
            ))}
            <div className="mobile-menu-actions">
              <a className="btn btn-secondary" href="/dashboard" onClick={goDashboard}>
                Sign In
              </a>
              <a className="btn btn-primary" href="#services" onClick={(event) => goHome(event, "#services")}>
                Join
              </a>
            </div>
            <a className="mobile-seller-link" href="/dashboard/seller" onClick={goSellerDashboard}>
              Open seller dashboard
            </a>
          </div>
        </div>
      </div>
    </header>
  );
}

export default Header;
