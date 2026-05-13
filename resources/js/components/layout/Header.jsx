import { useCallback, useEffect, useRef, useState } from "react";
import { exploreLinks, marketplaceHeaderCategories, navLinks } from "../../data/siteNavigation.js";
import { useDismissOnInteractOutside } from "../../hooks/useDismissOnInteractOutside.js";
import { useScrolledPast } from "../../hooks/useScrolledPast.js";
import { BrandMark, Icon } from "../common/Icons.jsx";

const authBenefits = ["Over 700 categories", "Quality work done faster", "Access to talent and businesses across the globe"];
const authVisualImage = "https://images.pexels.com/photos/3769021/pexels-photo-3769021.jpeg?auto=compress&cs=tinysrgb&w=900";

function Header({ enableMarketplaceHeader = true, forceSearch = false, onNavigate, searchQuery = "" }) {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isExploreOpen, setIsExploreOpen] = useState(false);
  const [authMode, setAuthMode] = useState(null);
  const exploreRef = useRef(null);
  const isScrolled = useScrolledPast(24);
  const isPastHomeHero = useScrolledPast(520);
  const showMarketplaceHeader = enableMarketplaceHeader && isPastHomeHero;
  const showHeaderSearch = forceSearch || showMarketplaceHeader;
  const closeExploreMenu = useCallback(() => setIsExploreOpen(false), []);

  useDismissOnInteractOutside(exploreRef, isExploreOpen, closeExploreMenu);

  useEffect(() => {
    if (authMode === null) return undefined;

    const handleKeyDown = (event) => {
      if (event.key === "Escape") {
        setAuthMode(null);
      }
    };

    document.body.classList.toggle("auth-modal-open", authMode !== null);
    document.addEventListener("keydown", handleKeyDown);

    return () => {
      document.body.classList.remove("auth-modal-open");
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, [authMode]);

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

  const openAuthModal = (event, mode) => {
    event.preventDefault();
    setIsMenuOpen(false);
    setIsExploreOpen(false);
    setAuthMode(mode);
  };

  const handleHeaderSearch = (event) => {
    event.preventDefault();
    setIsMenuOpen(false);
    setIsExploreOpen(false);
    const formData = new FormData(event.currentTarget);
    const query = String(formData.get("query") || "").trim();
    const queryString = query ? `?query=${encodeURIComponent(query)}&source=topbar` : "?source=topbar";
    onNavigate("/search/gigs", queryString);
  };

  const navigateToPath = (event, path) => {
    event.preventDefault();
    setIsMenuOpen(false);
    setIsExploreOpen(false);
    onNavigate(path);
  };

  const headerClass = [
    "site-header",
    isScrolled ? "is-scrolled" : "",
    showHeaderSearch ? "has-header-search" : "",
    showMarketplaceHeader ? "has-marketplace-header" : "",
    isMenuOpen ? "has-open-menu" : "",
  ]
    .filter(Boolean)
    .join(" ");

  return (
    <>
      <header className={headerClass}>
        <div className="container">
          <div className="nav-shell">
            <a className="brand" href="/" aria-label="BDGigs home" onClick={(event) => goHome(event)}>
              <BrandMark />
              BDGigs
            </a>

            <form className="marketplace-header-search" role="search" onSubmit={handleHeaderSearch}>
              <label className="sr-only" htmlFor="marketplaceHeaderSearch">
                Search services
              </label>
              <input
                id="marketplaceHeaderSearch"
                name="query"
                type="search"
                placeholder="What service are you looking for today?"
                defaultValue={searchQuery}
              />
              <button type="submit" aria-label="Search services">
                <Icon name="search" />
              </button>
            </form>

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
              <span className="marketplace-language" aria-label="Language: English">
                <span aria-hidden="true"></span>
                EN
              </span>
              <a href="#seller" onClick={(event) => goHome(event, "#seller")}>
                Become a Seller
              </a>
            </nav>

            <div className="nav-actions">
              <a className="btn btn-ghost" href="/login" onClick={(event) => openAuthModal(event, "login")}>
                Sign In
              </a>
              <a className="btn btn-primary nav-join-button" href="/register" onClick={(event) => openAuthModal(event, "register")}>
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
              <form className="mobile-header-search" role="search" onSubmit={handleHeaderSearch}>
                <label className="sr-only" htmlFor="mobileHeaderSearch">
                  Search services
                </label>
                <input
                  id="mobileHeaderSearch"
                  name="query"
                  type="search"
                  placeholder="What service are you looking for today?"
                  defaultValue={searchQuery}
                />
                <button type="submit" aria-label="Search services">
                  <Icon name="search" />
                </button>
              </form>

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
                <a className="btn btn-secondary" href="/login" onClick={(event) => openAuthModal(event, "login")}>
                  Sign In
                </a>
                <a className="btn btn-primary" href="/register" onClick={(event) => openAuthModal(event, "register")}>
                  Join
                </a>
              </div>
              <a className="mobile-seller-link" href="/dashboard/seller" onClick={goSellerDashboard}>
                Open seller dashboard
              </a>
            </div>
          </div>

          <nav className="marketplace-category-nav" aria-label="Marketplace categories">
            {marketplaceHeaderCategories.map((category) => (
              <a
                key={category.label}
                className={category.isHot ? "is-hot" : undefined}
                href={category.path}
                onClick={(event) => navigateToPath(event, category.path)}
              >
                {category.label}
                {category.isHot ? <span aria-hidden="true"></span> : null}
              </a>
            ))}
          </nav>
        </div>
      </header>

      {authMode ? <AuthModal mode={authMode} onClose={() => setAuthMode(null)} onModeChange={setAuthMode} /> : null}
    </>
  );
}

function AuthModal({ mode, onClose, onModeChange }) {
  const isRegister = mode === "register";

  return (
    <div className="auth-modal-backdrop" role="presentation" onMouseDown={onClose}>
      <section
        className="auth-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="authModalTitle"
        onMouseDown={(event) => event.stopPropagation()}
      >
        <div className="auth-modal-visual">
          <img src={authVisualImage} alt="" aria-hidden="true" />
          <div className="auth-visual-copy">
            <h2>Success starts here</h2>
            <ul>
              {authBenefits.map((benefit) => (
                <li key={benefit}>
                  <span className="auth-check" aria-hidden="true"></span>
                  {benefit}
                </li>
              ))}
            </ul>
          </div>
        </div>

        <div className="auth-modal-panel">
          <button className="auth-close-button" type="button" aria-label="Close sign in dialog" onClick={onClose}>
            <Icon name="close" />
          </button>

          <div className="auth-heading">
            <h2 id="authModalTitle">{isRegister ? "Create a new account" : "Sign in to your account"}</h2>
            <p>
              {isRegister ? "Already have an account?" : "Don't have an account?"}{" "}
              <button type="button" onClick={() => onModeChange(isRegister ? "login" : "register")}>
                {isRegister ? "Sign in" : "Join here"}
              </button>
            </p>
          </div>

          <div className="auth-provider-list">
            <button type="button">
              <span className="auth-provider-mark google">G</span>
              Continue with Google
            </button>
            {isRegister ? (
              <>
                <button type="button">
                  <span className="auth-provider-mark apple">A</span>
                  Continue with Apple
                </button>
                <button type="button">
                  <span className="auth-provider-mark facebook">f</span>
                  Continue with Facebook
                </button>
              </>
            ) : (
              <>
                <button type="button">
                  <span className="auth-provider-mark email">@</span>
                  Continue with email/username
                </button>
                <div className="auth-divider">
                  <span>OR</span>
                </div>
                <div className="auth-provider-split">
                  <button type="button">
                    <span className="auth-provider-mark apple">A</span>
                    Apple
                  </button>
                  <button type="button">
                    <span className="auth-provider-mark facebook">f</span>
                    Facebook
                  </button>
                </div>
              </>
            )}
          </div>

          {isRegister ? (
            <div className="auth-email-link">
              Or <button type="button">sign up using email</button>
              <span>Additional verification may be required at a later stage</span>
            </div>
          ) : null}

          <p className="auth-legal">
            By joining, you agree to the BDGigs <a href="#">Terms of Service</a> and to occasionally receive emails from
            us. Please read our <a href="#">Privacy Policy</a> to learn how we use your personal data.
          </p>
        </div>
      </section>
    </div>
  );
}

export default Header;
