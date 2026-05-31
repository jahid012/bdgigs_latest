import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useTranslation } from "react-i18next";
import { useLocation, useNavigate } from "react-router-dom";
import {
    exploreLinks,
    marketplaceHeaderCategories,
    navLinks,
} from "../../data/siteNavigation.js";
import { useDismissOnInteractOutside } from "../../hooks/useDismissOnInteractOutside.js";
import { useSearchSuggestions } from "../../hooks/useSearchSuggestions.js";
import { useScrolledPast } from "../../hooks/useScrolledPast.js";
import { supportedLanguages } from "../../i18n/index.js";
import { apiRequest } from "../../api/apiClient.js";
import { useDashboardStore } from "../../stores/useDashboardStore.js";
import { useSessionStore } from "../../stores/useSessionStore.js";
import { BrandMark, Icon } from "../common/Icons.jsx";
import SearchSuggestionDropdown from "../common/SearchSuggestionDropdown.jsx";

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
    fetchMarketplaceCategories = true,
    forceSearch = false,
    hydrateSessionOnMount = true,
    marketplaceCategories: preloadedMarketplaceCategories = [],
    onNavigate,
    searchQuery = "",
}) {
    const { i18n, t } = useTranslation();
    const location = useLocation();
    const routerNavigate = useNavigate();
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isExploreOpen, setIsExploreOpen] = useState(false);
    const [authMode, setAuthMode] = useState(null);
    const [pendingPath, setPendingPath] = useState("");
    const [headerSearchValue, setHeaderSearchValue] = useState(searchQuery);
    const [headerSearchFocused, setHeaderSearchFocused] = useState(false);
    const [loadedMarketplaceCategories, setLoadedMarketplaceCategories] =
        useState([]);
    const [activeMegaSlug, setActiveMegaSlug] = useState("");
    const exploreRef = useRef(null);
    const currentUser = useSessionStore((state) => state.currentUser);
    const hydrateSession = useSessionStore((state) => state.hydrateSession);
    const isScrolled = useScrolledPast(24);
    const isPastHomeHero = useScrolledPast(520);
    const showMarketplaceHeader = enableMarketplaceHeader && isPastHomeHero;
    const showHeaderSearch = forceSearch || showMarketplaceHeader;
    const closeExploreMenu = useCallback(() => setIsExploreOpen(false), []);
    const headerSuggestions = useSearchSuggestions(headerSearchValue);
    const marketplaceCategories = useMemo(
        () => {
            const categories = preloadedMarketplaceCategories.length
                ? preloadedMarketplaceCategories
                : loadedMarketplaceCategories;

            return categories.length ? categories : marketplaceHeaderCategories;
        },
        [loadedMarketplaceCategories, preloadedMarketplaceCategories],
    );
    const activeMegaCategory =
        marketplaceCategories.find((category) => category.slug === activeMegaSlug) ||
        null;

    useDismissOnInteractOutside(exploreRef, isExploreOpen, closeExploreMenu);

    useEffect(() => {
        if (!hydrateSessionOnMount) {
            return;
        }

        hydrateSession();
    }, [hydrateSession, hydrateSessionOnMount]);

    useEffect(() => {
        setHeaderSearchValue(searchQuery);
    }, [searchQuery]);

    useEffect(() => {
        if (!fetchMarketplaceCategories || preloadedMarketplaceCategories.length) {
            return;
        }

        let active = true;

        apiRequest("/api/marketplace/categories")
            .then((categories) => {
                if (active) {
                    setLoadedMarketplaceCategories(categories || []);
                }
            })
            .catch(() => {
                if (active) {
                    setLoadedMarketplaceCategories([]);
                }
            });

        return () => {
            active = false;
        };
    }, [fetchMarketplaceCategories, preloadedMarketplaceCategories.length]);

    useEffect(() => {
        const params = new URLSearchParams(location.search);
        const requestedMode = params.get("auth");

        if (requestedMode !== "login" && requestedMode !== "register") {
            return;
        }

        setAuthMode(requestedMode);
        setPendingPath(params.get("redirect") || "");
        params.delete("auth");
        params.delete("redirect");

        routerNavigate(
            {
                pathname: location.pathname,
                search: params.toString() ? `?${params.toString()}` : "",
                hash: location.hash,
            },
            { replace: true },
        );
    }, [location.hash, location.pathname, location.search, routerNavigate]);

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

        if (!currentUser?.authenticated) {
            setPendingPath("/dashboard");
            setAuthMode("login");
            return;
        }

        onNavigate("dashboard");
    };

    const goSellerDashboard = (event) => {
        event.preventDefault();
        setIsMenuOpen(false);
        setIsExploreOpen(false);

        if (!currentUser?.authenticated) {
            setPendingPath("/dashboard/seller");
            setAuthMode("login");
            return;
        }

        onNavigate("seller-dashboard");
    };

    const openAuthModal = (event, mode) => {
        event.preventDefault();
        setIsMenuOpen(false);
        setIsExploreOpen(false);
        setPendingPath("");
        setAuthMode(mode);
    };

    const handleAuthSuccess = () => {
        const nextPath = pendingPath;
        setAuthMode(null);
        setPendingPath("");

        if (nextPath) {
            onNavigate(nextPath);
        }
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

    const handleSuggestionSelect = (suggestion) => {
        setHeaderSearchFocused(false);
        setIsMenuOpen(false);
        setIsExploreOpen(false);
        onNavigate(suggestion.path || "/search/gigs");
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
    const useWhiteLogo =
        location.pathname === "/" &&
        !isScrolled &&
        !showMarketplaceHeader &&
        !isMenuOpen;

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
                            <BrandMark
                                variant={useWhiteLogo ? "light" : "default"}
                            />
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
                            <div className="header-search-field search-suggestion-host">
                                <input
                                    id="marketplaceHeaderSearch"
                                    name="query"
                                    type="search"
                                    placeholder={t("header.searchPlaceholder")}
                                    value={headerSearchValue}
                                    autoComplete="off"
                                    onBlur={() =>
                                        window.setTimeout(
                                            () => setHeaderSearchFocused(false),
                                            120,
                                        )
                                    }
                                    onChange={(event) =>
                                        setHeaderSearchValue(event.target.value)
                                    }
                                    onFocus={() =>
                                        setHeaderSearchFocused(true)
                                    }
                                />
                                {headerSearchFocused ? (
                                    <SearchSuggestionDropdown
                                        {...headerSuggestions}
                                        query={headerSearchValue}
                                        onSelect={handleSuggestionSelect}
                                    />
                                ) : null}
                            </div>
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
                                onClick={goSellerDashboard}
                            >
                                {t("header.becomeSeller")}
                            </a>
                        </nav>

                        <div className="nav-actions">
                            {currentUser?.authenticated ? (
                                <HeaderAccountActions
                                    currentUser={currentUser}
                                    onNavigate={onNavigate}
                                />
                            ) : (
                                <>
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
                                </>
                            )}
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
                                {currentUser?.authenticated ? (
                                    <HeaderAccountActions
                                        currentUser={currentUser}
                                        mobile
                                        onMenuClose={() =>
                                            setIsMenuOpen(false)
                                        }
                                        onNavigate={onNavigate}
                                    />
                                ) : (
                                    <>
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
                                    </>
                                )}
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
                        onMouseLeave={() => setActiveMegaSlug("")}
                    >
                        <div className="marketplace-category-strip">
                            {marketplaceCategories.map((category) => {
                                const categoryPath =
                                    category.path || category.linkUrl || "/search/gigs";
                                const categorySlug =
                                    category.slug ||
                                    getNavigationKey(category.label);
                                const hasChildren = Boolean(
                                    category.children?.length,
                                );

                                return (
                                    <a
                                        key={categorySlug}
                                        className={
                                            category.isHot
                                                ? "is-hot"
                                                : undefined
                                        }
                                        href={categoryPath}
                                        onClick={(event) =>
                                            navigateToPath(event, categoryPath)
                                        }
                                        onFocus={() =>
                                            setActiveMegaSlug(categorySlug)
                                        }
                                        onMouseEnter={() =>
                                            setActiveMegaSlug(categorySlug)
                                        }
                                    >
                                        {t(
                                            `header.categories.${getNavigationKey(category.label)}`,
                                            { defaultValue: category.label },
                                        )}
                                        {category.isHot ? (
                                            <span aria-hidden="true"></span>
                                        ) : null}
                                        {hasChildren ? (
                                            <Icon name="chevronDown" />
                                        ) : null}
                                    </a>
                                );
                            })}
                        </div>
                        {activeMegaCategory?.children?.length ? (
                            <div className="marketplace-mega-menu">
                                <div>
                                    <strong>{activeMegaCategory.label}</strong>
                                    <p>
                                        {activeMegaCategory.description ||
                                            "Browse marketplace services by specialty."}
                                    </p>
                                </div>
                                <div className="marketplace-mega-grid">
                                    {activeMegaCategory.children.map((child) => (
                                        <a
                                            href={child.path}
                                            key={child.slug || child.label}
                                            onClick={(event) =>
                                                navigateToPath(
                                                    event,
                                                    child.path,
                                                )
                                            }
                                        >
                                            <span>
                                                <Icon
                                                    name={
                                                        child.icon ||
                                                        activeMegaCategory.icon ||
                                                        "spark"
                                                    }
                                                />
                                            </span>
                                            <strong>{child.label}</strong>
                                            <small>
                                                {child.description ||
                                                    "Explore services"}
                                            </small>
                                        </a>
                                    ))}
                                </div>
                            </div>
                        ) : null}
                    </nav>
                </div>
            </header>

            {authMode ? (
                <AuthModal
                    mode={authMode}
                    onClose={() => setAuthMode(null)}
                    onNavigate={onNavigate}
                    onSuccess={handleAuthSuccess}
                    onModeChange={setAuthMode}
                />
            ) : null}
        </>
    );
}

function HeaderAccountActions({
    currentUser,
    mobile = false,
    onMenuClose,
    onNavigate,
}) {
    const [openMenu, setOpenMenu] = useState(null);
    const actionsRef = useRef(null);
    const messageItems = useDashboardStore((state) => state.messages);
    const notificationItems = useDashboardStore(
        (state) => state.buyerNotifications,
    );
    const fetchConversations = useDashboardStore(
        (state) => state.fetchConversations,
    );
    const fetchNotifications = useDashboardStore(
        (state) => state.fetchNotifications,
    );
    const logout = useSessionStore((state) => state.logout);
    const closeMenus = useCallback(() => setOpenMenu(null), []);

    useDismissOnInteractOutside(actionsRef, openMenu !== null, closeMenus);

    useEffect(() => {
        fetchConversations();
        fetchNotifications();
    }, [fetchConversations, fetchNotifications]);

    const toggleMenu = (event, menu) => {
        event.stopPropagation();
        setOpenMenu((current) => (current === menu ? null : menu));
    };
    const openPath = (event, path) => {
        event.preventDefault();
        closeMenus();
        onMenuClose?.();
        onNavigate(path);
    };
    const signOut = async () => {
        await logout();
        closeMenus();
        onMenuClose?.();
        onNavigate("home");
    };

    return (
        <div
            className={`header-account-actions${mobile ? " is-mobile" : ""}`}
            ref={actionsRef}
        >
            <HeaderFeedMenu
                actionLabel="Open inbox"
                emptyText="Your conversations will appear here."
                icon="message"
                items={messageItems}
                kind="messages"
                onAction={(event) => openPath(event, "/dashboard/messages")}
                onToggle={toggleMenu}
                openMenu={openMenu}
                title="Messages"
            />
            <HeaderFeedMenu
                actionLabel="Open dashboard"
                emptyText="No new notifications."
                icon="bell"
                items={notificationItems}
                kind="notifications"
                onAction={(event) => openPath(event, "/dashboard")}
                onToggle={toggleMenu}
                openMenu={openMenu}
                title="Notifications"
            />
            <div className="header-account-menu">
                <button
                    className="btn btn-ghost header-profile-trigger"
                    type="button"
                    aria-haspopup="true"
                    aria-expanded={openMenu === "profile"}
                    onClick={(event) => toggleMenu(event, "profile")}
                >
                    <HeaderUserAvatar currentUser={currentUser} />
                    <span>{currentUser.name}</span>
                    <Icon name="chevronDown" />
                </button>
                <div
                    className={`header-account-dropdown profile${openMenu === "profile" ? " is-open" : ""}`}
                >
                    <a
                        href="/dashboard/profile"
                        onClick={(event) => openPath(event, "/dashboard/profile")}
                    >
                        Profile
                    </a>
                    <a
                        href="/dashboard"
                        onClick={(event) => openPath(event, "/dashboard")}
                    >
                        Buyer dashboard
                    </a>
                    <a
                        href="/dashboard/seller"
                        onClick={(event) =>
                            openPath(event, "/dashboard/seller")
                        }
                    >
                        Seller dashboard
                    </a>
                    <a
                        href="/dashboard/settings"
                        onClick={(event) =>
                            openPath(event, "/dashboard/settings")
                        }
                    >
                        Settings
                    </a>
                    <button type="button" onClick={signOut}>
                        Sign out
                    </button>
                </div>
            </div>
        </div>
    );
}

function HeaderUserAvatar({ currentUser, large = false }) {
    const avatar = normalizeAvatarUrl(currentUser?.avatar);
    const initials = currentUser?.initials || currentUser?.name?.slice(0, 2) || "BD";

    return (
        <span
            className={`avatar header-user-avatar${large ? " is-large" : ""}`}
            aria-label={`${currentUser?.name || "User"} ${
                currentUser?.online ? "online" : "offline"
            }`}
        >
            {avatar ? (
                <img src={avatar} alt="" loading="lazy" decoding="async" />
            ) : (
                initials
            )}
            <i
                className={currentUser?.online ? "is-online" : "is-offline"}
                aria-hidden="true"
            ></i>
        </span>
    );
}

function normalizeAvatarUrl(avatar) {
    if (!avatar) return "";

    if (
        avatar.startsWith("/") ||
        avatar.startsWith("http://") ||
        avatar.startsWith("https://") ||
        avatar.startsWith("data:")
    ) {
        return avatar;
    }

    if (avatar.startsWith("assets/")) {
        return `/${avatar}`;
    }

    return `/storage/${avatar.replace(/^storage\//, "")}`;
}

function HeaderFeedMenu({
    actionLabel,
    emptyText,
    icon,
    items,
    kind,
    onAction,
    onToggle,
    openMenu,
    title,
}) {
    return (
        <div className="header-account-menu">
            <button
                className="btn btn-ghost header-feed-trigger"
                type="button"
                aria-label={title}
                aria-haspopup="true"
                aria-expanded={openMenu === kind}
                onClick={(event) => onToggle(event, kind)}
            >
                <Icon name={icon} />
                {items.length ? <span aria-hidden="true"></span> : null}
            </button>
            <div
                className={`header-account-dropdown feed${openMenu === kind ? " is-open" : ""}`}
            >
                <strong>{title}</strong>
                <div className="header-feed-list">
                    {items.slice(0, 4).map((item) => (
                        <article
                            key={`${title}-${item.id || item.name || item.title}-${item.time || ""}`}
                        >
                            <span className="avatar">
                                {item.initials || <Icon name={icon} />}
                            </span>
                            <span>
                                <b>{item.name || item.title}</b>
                                <small>
                                    {item.message || item.detail || item.type}
                                </small>
                                {item.time ? <em>{item.time}</em> : null}
                            </span>
                        </article>
                    ))}
                    {items.length === 0 ? <p>{emptyText}</p> : null}
                </div>
                <a href="#" onClick={onAction}>
                    {actionLabel}
                </a>
            </div>
        </div>
    );
}

function AuthModal({ mode, onClose, onModeChange, onNavigate, onSuccess }) {
    const { t } = useTranslation();
    const isRegister = mode === "register";
    const login = useSessionStore((state) => state.login);
    const register = useSessionStore((state) => state.register);
    const completeTwoFactor = useSessionStore(
        (state) => state.completeTwoFactor,
    );
    const isLoading = useSessionStore((state) => state.isLoading);
    const [form, setForm] = useState({
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
        remember: false,
    });
    const [formError, setFormError] = useState("");
    const [showPassword, setShowPassword] = useState(false);
    const [twoFactorForm, setTwoFactorForm] = useState({
        active: false,
        code: "",
        recoveryCode: "",
        useRecoveryCode: false,
    });

    const updateForm = (field, value) => {
        setForm((current) => ({
            ...current,
            [field]: value,
        }));
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setFormError("");

        if (isRegister && form.password !== form.password_confirmation) {
            setFormError("Passwords do not match.");
            return;
        }

        try {
            if (isRegister) {
                await register(form);
            } else {
                const loginResult = await login({
                    email: form.email,
                    password: form.password,
                    remember: form.remember,
                });

                if (loginResult?.requiresTwoFactor) {
                    setTwoFactorForm((current) => ({
                        ...current,
                        active: true,
                    }));
                    return;
                }
            }

            onSuccess?.();
        } catch (error) {
            const validationMessage =
                error.payload?.errors?.email?.[0] ||
                error.payload?.errors?.password?.[0] ||
                error.payload?.errors?.name?.[0] ||
                error.message;
            setFormError(validationMessage);
        }
    };

    const handleTwoFactorSubmit = async (event) => {
        event.preventDefault();
        setFormError("");

        try {
            await completeTwoFactor({
                code: twoFactorForm.useRecoveryCode
                    ? ""
                    : twoFactorForm.code,
                recoveryCode: twoFactorForm.useRecoveryCode
                    ? twoFactorForm.recoveryCode
                    : "",
            });
            onSuccess?.();
        } catch (error) {
            setFormError(
                error.payload?.errors?.code?.[0] ||
                    error.payload?.errors?.recovery_code?.[0] ||
                    error.message,
            );
        }
    };
    const openForgotPassword = () => {
        onClose?.();
        onNavigate?.("/forgot-password");
    };

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

                    {twoFactorForm.active ? (
                        <form
                            className="auth-email-form"
                            onSubmit={handleTwoFactorSubmit}
                        >
                            <label>
                                <span>
                                    {twoFactorForm.useRecoveryCode
                                        ? "Recovery code"
                                        : "Authenticator code"}
                                </span>
                                <input
                                    autoComplete="one-time-code"
                                    value={
                                        twoFactorForm.useRecoveryCode
                                            ? twoFactorForm.recoveryCode
                                            : twoFactorForm.code
                                    }
                                    onChange={(event) =>
                                        setTwoFactorForm((current) => ({
                                            ...current,
                                            [current.useRecoveryCode
                                                ? "recoveryCode"
                                                : "code"]: event.target.value,
                                        }))
                                    }
                                    required
                                />
                            </label>
                            <button
                                className="auth-forgot-link"
                                type="button"
                                onClick={() =>
                                    setTwoFactorForm((current) => ({
                                        ...current,
                                        useRecoveryCode:
                                            !current.useRecoveryCode,
                                    }))
                                }
                            >
                                {twoFactorForm.useRecoveryCode
                                    ? "Use authenticator code"
                                    : "Use a recovery code"}
                            </button>
                            {formError ? (
                                <p className="auth-form-error">{formError}</p>
                            ) : null}
                            <button
                                className="auth-submit-button"
                                type="submit"
                                disabled={
                                    isLoading ||
                                    !(twoFactorForm.useRecoveryCode
                                        ? twoFactorForm.recoveryCode
                                        : twoFactorForm.code)
                                }
                            >
                                {isLoading ? "Please wait..." : "Verify"}
                            </button>
                        </form>
                    ) : (
                    <form className="auth-email-form" onSubmit={handleSubmit}>
                        {isRegister ? (
                            <label>
                                <span>Name</span>
                                <input
                                    type="text"
                                    value={form.name}
                                    autoComplete="name"
                                    onChange={(event) =>
                                        updateForm("name", event.target.value)
                                    }
                                    required
                                />
                            </label>
                        ) : null}
                        <label>
                            <span>Email</span>
                            <input
                                type="email"
                                value={form.email}
                                autoComplete="email"
                                onChange={(event) =>
                                    updateForm("email", event.target.value)
                                }
                                required
                            />
                        </label>
                        <label>
                            <span>Password</span>
                            <span className="auth-password-field">
                                <input
                                    type={showPassword ? "text" : "password"}
                                    value={form.password}
                                    autoComplete={
                                        isRegister
                                            ? "new-password"
                                            : "current-password"
                                    }
                                    minLength={isRegister ? 8 : undefined}
                                    onChange={(event) =>
                                        updateForm(
                                            "password",
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                                <button
                                    type="button"
                                    aria-label={
                                        showPassword
                                            ? "Hide password"
                                            : "Show password"
                                    }
                                    onClick={() =>
                                        setShowPassword((visible) => !visible)
                                    }
                                >
                                    <Icon name="eye" />
                                </button>
                            </span>
                        </label>
                        {isRegister ? (
                            <label>
                                <span>Confirm password</span>
                                <input
                                    type="password"
                                    value={form.password_confirmation}
                                    autoComplete="new-password"
                                    minLength="8"
                                    onChange={(event) =>
                                        updateForm(
                                            "password_confirmation",
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                            </label>
                        ) : (
                            <div className="auth-login-options">
                                <label className="auth-remember-row">
                                    <input
                                        type="checkbox"
                                        checked={form.remember}
                                        onChange={(event) =>
                                            updateForm(
                                                "remember",
                                                event.target.checked,
                                            )
                                        }
                                    />
                                    <span>Remember me for 30 days</span>
                                </label>
                                <button
                                    className="auth-forgot-link"
                                    type="button"
                                    onClick={openForgotPassword}
                                >
                                    Forgot password?
                                </button>
                            </div>
                        )}
                        {formError ? (
                            <p className="auth-form-error">{formError}</p>
                        ) : null}
                        <button
                            className="auth-submit-button"
                            type="submit"
                            disabled={
                                isLoading ||
                                !form.email ||
                                !form.password ||
                                (isRegister &&
                                    (!form.name ||
                                        !form.password_confirmation))
                            }
                        >
                            {isLoading
                                ? "Please wait..."
                                : isRegister
                                  ? "Create account"
                                  : "Sign in"}
                        </button>
                    </form>
                    )}

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
