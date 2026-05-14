import { useCallback, useEffect, useRef, useState } from "react";
import { useTranslation } from "react-i18next";
import {
    exploreLinks,
    marketplaceHeaderCategories,
    navLinks,
} from "../../data/siteNavigation.js";
import { useDismissOnInteractOutside } from "../../hooks/useDismissOnInteractOutside.js";
import { useScrolledPast } from "../../hooks/useScrolledPast.js";
import { supportedLanguages } from "../../i18n/index.js";
import { BrandMark, Icon } from "../common/Icons.jsx";

const authBenefitKeys = [
    "auth.benefits.categories",
    "auth.benefits.quality",
    "auth.benefits.globalAccess",
];
const authVisualImage =
    "https://images.pexels.com/photos/3769021/pexels-photo-3769021.jpeg?auto=compress&cs=tinysrgb&w=900";

const navigationKeyMap = {
    "AI Services": "aiServices",
    Business: "business",
    "Become a Seller": "becomeSeller",
    "Digital Marketing": "digitalMarketing",
    Finance: "finance",
    "Graphics & Design": "graphicsDesign",
    "How it Works": "howItWorks",
    "Music & Audio": "musicAudio",
    "Programming & Tech": "programmingTech",
    Services: "services",
    Trending: "trending",
    "Video & Animation": "videoAnimation",
    "Writing & Translation": "writingTranslation",
};

function getNavigationKey(label) {
    return (
        navigationKeyMap[label] ||
        label
            .replace(/[^a-z0-9]+/gi, " ")
            .trim()
            .replace(/\s+([a-z0-9])/gi, (_, letter) => letter.toUpperCase())
            .replace(/^[A-Z]/, (letter) => letter.toLowerCase())
    );
}

function Header({
    enableMarketplaceHeader = true,
    forceSearch = false,
    onNavigate,
    searchQuery = "",
}) {
    const { i18n, t } = useTranslation();
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
        const queryString = query
            ? `?query=${encodeURIComponent(query)}&source=topbar`
            : "?source=topbar";
        onNavigate("/search/gigs", queryString);
    };

    const navigateToPath = (event, path) => {
        event.preventDefault();
        setIsMenuOpen(false);
        setIsExploreOpen(false);
        onNavigate(path);
    };

    const navigateLink = (event, link) => {
        if (link.path) {
            navigateToPath(event, link.path);
            return;
        }

        goHome(event, link.hash);
    };

    const currentLanguage = supportedLanguages.some(
        (language) => language.code === i18n.resolvedLanguage,
    )
        ? i18n.resolvedLanguage
        : supportedLanguages.some((language) => language.code === i18n.language)
          ? i18n.language
          : "en";

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
                        <a
                            className="brand"
                            href="/"
                            aria-label={t("header.brandAria")}
                            onClick={(event) => goHome(event)}
                        >
                            <BrandMark />
                            BDGigs
                        </a>

                        <form
                            className="marketplace-header-search"
                            role="search"
                            onSubmit={handleHeaderSearch}
                        >
                            <label
                                className="sr-only"
                                htmlFor="marketplaceHeaderSearch"
                            >
                                {t("header.searchLabel")}
                            </label>
                            <input
                                id="marketplaceHeaderSearch"
                                name="query"
                                type="search"
                                placeholder={t("header.searchPlaceholder")}
                                defaultValue={searchQuery}
                            />
                            <button
                                type="submit"
                                aria-label={t("header.searchLabel")}
                            >
                                <Icon name="search" />
                            </button>
                        </form>

                        <nav
                            className="nav-links"
                            aria-label={t("header.primaryNavigation")}
                        >
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
                                    {t("header.explore")}
                                    <Icon name="chevronDown" />
                                </button>
                                <div
                                    className={`nav-dropdown-panel${isExploreOpen ? " is-open" : ""}`}
                                >
                                    <div className="nav-dropdown-grid">
                                        {exploreLinks.map((link) => {
                                            const navigationKey =
                                                getNavigationKey(link.label);

                                            return (
                                                <a
                                                    key={link.label}
                                                    href={
                                                        link.path || link.hash
                                                    }
                                                    onClick={(event) =>
                                                        navigateLink(
                                                            event,
                                                            link,
                                                        )
                                                    }
                                                >
                                                    <span
                                                        className="nav-dropdown-icon"
                                                        aria-hidden="true"
                                                    >
                                                        <Icon name="spark" />
                                                    </span>
                                                    <span>
                                                        <strong>
                                                            {t(
                                                                `header.categories.${navigationKey}`,
                                                                {
                                                                    defaultValue:
                                                                        link.label,
                                                                },
                                                            )}
                                                        </strong>
                                                        <small>
                                                            {t(
                                                                `header.exploreCopy.${navigationKey}`,
                                                                {
                                                                    defaultValue:
                                                                        link.copy,
                                                                },
                                                            )}
                                                        </small>
                                                    </span>
                                                </a>
                                            );
                                        })}
                                    </div>
                                    <div className="nav-dropdown-cta">
                                        <span>
                                            <strong>
                                                {t("header.readyToManage")}
                                            </strong>
                                            <small>
                                                {t("header.jumpTools")}
                                            </small>
                                        </span>
                                        <div>
                                            <a
                                                href="/dashboard"
                                                onClick={goDashboard}
                                            >
                                                {t("header.buyer")}
                                            </a>
                                            <a
                                                href="/dashboard/seller"
                                                onClick={goSellerDashboard}
                                            >
                                                {t("header.seller")}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <label
                                className="marketplace-language"
                                aria-label={t("header.languageAria")}
                            >
                                <span aria-hidden="true"></span>
                                <select
                                    value={currentLanguage}
                                    onChange={(event) =>
                                        i18n.changeLanguage(event.target.value)
                                    }
                                >
                                    {supportedLanguages.map((language) => (
                                        <option
                                            value={language.code}
                                            key={language.code}
                                        >
                                            {language.label}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <a
                                href="/dashboard/seller"
                                onClick={(event) =>
                                    navigateToPath(event, "/dashboard/seller")
                                }
                            >
                                {t("header.becomeSeller")}
                            </a>
                        </nav>

                        <div className="nav-actions">
                            <a
                                className="btn btn-ghost"
                                href="/login"
                                onClick={(event) =>
                                    openAuthModal(event, "login")
                                }
                            >
                                {t("header.signIn")}
                            </a>
                            <a
                                className="btn btn-primary nav-join-button"
                                href="/register"
                                onClick={(event) =>
                                    openAuthModal(event, "register")
                                }
                            >
                                {t("header.join")}
                            </a>
                        </div>

                        <button
                            className={`nav-toggle${isMenuOpen ? " is-open" : ""}`}
                            type="button"
                            aria-label={
                                isMenuOpen
                                    ? t("header.closeMenu")
                                    : t("header.openMenu")
                            }
                            aria-expanded={isMenuOpen}
                            aria-controls="mobileMenu"
                            onClick={() => setIsMenuOpen((open) => !open)}
                        >
                            <span aria-hidden="true"></span>
                        </button>
                    </div>

                    <div
                        className={`mobile-menu${isMenuOpen ? " is-open" : ""}`}
                        id="mobileMenu"
                    >
                        <div className="mobile-menu-panel">
                            <form
                                className="mobile-header-search"
                                role="search"
                                onSubmit={handleHeaderSearch}
                            >
                                <label
                                    className="sr-only"
                                    htmlFor="mobileHeaderSearch"
                                >
                                    {t("header.searchLabel")}
                                </label>
                                <input
                                    id="mobileHeaderSearch"
                                    name="query"
                                    type="search"
                                    placeholder={t("header.searchPlaceholder")}
                                    defaultValue={searchQuery}
                                />
                                <button
                                    type="submit"
                                    aria-label={t("header.searchLabel")}
                                >
                                    <Icon name="search" />
                                </button>
                            </form>

                            <label
                                className="marketplace-language mobile-language-switch"
                                aria-label={t("header.languageAria")}
                            >
                                <span aria-hidden="true"></span>
                                <select
                                    value={currentLanguage}
                                    onChange={(event) =>
                                        i18n.changeLanguage(event.target.value)
                                    }
                                >
                                    {supportedLanguages.map((language) => (
                                        <option
                                            value={language.code}
                                            key={language.code}
                                        >
                                            {language.name}
                                        </option>
                                    ))}
                                </select>
                            </label>

                            <div className="mobile-menu-group">
                                <span>{t("header.mobileExplore")}</span>
                                {exploreLinks.map((link) => (
                                    <a
                                        key={link.label}
                                        href={link.path || link.hash}
                                        onClick={(event) =>
                                            navigateLink(event, link)
                                        }
                                    >
                                        {t(
                                            `header.categories.${getNavigationKey(link.label)}`,
                                            { defaultValue: link.label },
                                        )}
                                    </a>
                                ))}
                            </div>
                            {navLinks.map((link) => (
                                <a
                                    key={link.label}
                                    href={link.path || link.hash}
                                    onClick={(event) =>
                                        navigateLink(event, link)
                                    }
                                >
                                    {t(
                                        `header.categories.${getNavigationKey(link.label)}`,
                                        { defaultValue: link.label },
                                    )}
                                </a>
                            ))}
                            <div className="mobile-menu-actions">
                                <a
                                    className="btn btn-secondary"
                                    href="/login"
                                    onClick={(event) =>
                                        openAuthModal(event, "login")
                                    }
                                >
                                    {t("header.signIn")}
                                </a>
                                <a
                                    className="btn btn-primary"
                                    href="/register"
                                    onClick={(event) =>
                                        openAuthModal(event, "register")
                                    }
                                >
                                    {t("header.join")}
                                </a>
                            </div>
                            <a
                                className="mobile-seller-link"
                                href="/dashboard/seller"
                                onClick={goSellerDashboard}
                            >
                                {t("header.sellerDashboard")}
                            </a>
                        </div>
                    </div>

                    <nav
                        className="marketplace-category-nav"
                        aria-label={t("header.marketplaceCategories")}
                    >
                        {marketplaceHeaderCategories.map((category) => (
                            <a
                                key={category.label}
                                className={
                                    category.isHot ? "is-hot" : undefined
                                }
                                href={category.path}
                                onClick={(event) =>
                                    navigateToPath(event, category.path)
                                }
                            >
                                {t(
                                    `header.categories.${getNavigationKey(category.label)}`,
                                    { defaultValue: category.label },
                                )}
                                {category.isHot ? (
                                    <span aria-hidden="true"></span>
                                ) : null}
                            </a>
                        ))}
                    </nav>
                </div>
            </header>

            {authMode ? (
                <AuthModal
                    mode={authMode}
                    onClose={() => setAuthMode(null)}
                    onModeChange={setAuthMode}
                />
            ) : null}
        </>
    );
}

function AuthModal({ mode, onClose, onModeChange }) {
    const { t } = useTranslation();
    const isRegister = mode === "register";

    return (
        <div
            className="auth-modal-backdrop"
            role="presentation"
            onMouseDown={onClose}
        >
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
                        <h2>{t("auth.successTitle")}</h2>
                        <ul>
                            {authBenefitKeys.map((benefitKey) => (
                                <li key={benefitKey}>
                                    <span
                                        className="auth-check"
                                        aria-hidden="true"
                                    ></span>
                                    {t(benefitKey)}
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>

                <div className="auth-modal-panel">
                    <button
                        className="auth-close-button"
                        type="button"
                        aria-label={t("auth.closeDialog")}
                        onClick={onClose}
                    >
                        <Icon name="close" />
                    </button>

                    <div className="auth-heading">
                        <h2 id="authModalTitle">
                            {isRegister
                                ? t("auth.createAccount")
                                : t("auth.signInTitle")}
                        </h2>
                        <p>
                            {isRegister
                                ? t("auth.alreadyHaveAccount")
                                : t("auth.dontHaveAccount")}{" "}
                            <button
                                type="button"
                                onClick={() =>
                                    onModeChange(
                                        isRegister ? "login" : "register",
                                    )
                                }
                            >
                                {isRegister
                                    ? t("auth.signIn")
                                    : t("auth.joinHere")}
                            </button>
                        </p>
                    </div>

                    <div className="auth-provider-list">
                        <button type="button">
                            <span className="auth-provider-mark google">G</span>
                            {t("auth.continueGoogle")}
                        </button>
                        {isRegister ? (
                            <>
                                <button type="button">
                                    <span className="auth-provider-mark apple">
                                        A
                                    </span>
                                    {t("auth.continueApple")}
                                </button>
                                <button type="button">
                                    <span className="auth-provider-mark facebook">
                                        f
                                    </span>
                                    {t("auth.continueFacebook")}
                                </button>
                            </>
                        ) : (
                            <>
                                <button type="button">
                                    <span className="auth-provider-mark email">
                                        @
                                    </span>
                                    {t("auth.continueEmail")}
                                </button>
                                <div className="auth-divider">
                                    <span>{t("auth.or")}</span>
                                </div>
                                <div className="auth-provider-split">
                                    <button type="button">
                                        <span className="auth-provider-mark apple">
                                            A
                                        </span>
                                        {t("auth.apple")}
                                    </button>
                                    <button type="button">
                                        <span className="auth-provider-mark facebook">
                                            f
                                        </span>
                                        {t("auth.facebook")}
                                    </button>
                                </div>
                            </>
                        )}
                    </div>

                    {isRegister ? (
                        <div className="auth-email-link">
                            {t("auth.orPrefix")}{" "}
                            <button type="button">
                                {t("auth.emailSignup")}
                            </button>
                            <span>{t("auth.verificationNote")}</span>
                        </div>
                    ) : null}

                    <p className="auth-legal">
                        {t("auth.legalBefore")}{" "}
                        <a href="#">{t("auth.terms")}</a> {t("auth.legalAfter")}{" "}
                        <a href="#">{t("auth.privacyPolicy")}</a>{" "}
                        {t("auth.legalEnd")}
                    </p>
                </div>
            </section>
        </div>
    );
}

export default Header;
