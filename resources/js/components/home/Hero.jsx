import { useState } from "react";
import { popularTags, trustedBrands } from "../../data/homeData.js";
import { useSearchSuggestions } from "../../hooks/useSearchSuggestions.js";
import SearchSuggestionDropdown from "../common/SearchSuggestionDropdown.jsx";
import { Icon } from "../common/Icons.jsx";
import { useTranslation } from "react-i18next";
function Hero({ onNavigate }) {
    const { t } = useTranslation();
    const [query, setQuery] = useState("");
    const [focused, setFocused] = useState(false);
    const suggestions = useSearchSuggestions(query);
    const handleSearch = (event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const query = String(formData.get("query") || "").trim();
        const queryString = query
            ? `?query=${encodeURIComponent(query)}&source=hero`
            : "?source=hero";
        onNavigate("/search/gigs", queryString);
    };
    const selectSuggestion = (suggestion) => {
        setFocused(false);
        onNavigate(suggestion.path || "/search/gigs");
    };
    return (
        <section className="hero">
            <video
                className="hero-bg-video"
                autoPlay
                muted
                loop
                playsInline
                preload="metadata"
                aria-hidden="true"
            >
                <source
                    src="https://assets.mixkit.co/videos/4809/4809-720.mp4"
                    type="video/mp4"
                />
            </video>

            <div className="container hero-content">
                <div className="hero-copy">
                    <h1 className="hero-title">
                        {" "}
                        {t("components.home.hero.ourFreelancers")} <br />{" "}
                        {t("components.home.hero.willTakeItFromHere")}{" "}
                    </h1>

                    <form
                        className="hero-search"
                        role="search"
                        aria-label={t(
                            "components.home.hero.searchFreelanceServices",
                        )}
                        onSubmit={handleSearch}
                    >
                        <label className="hero-search-field search-suggestion-host">
                            <span className="sr-only">
                                {t("components.home.hero.searchService")}
                            </span>
                            <input
                                name="query"
                                type="search"
                                placeholder={t(
                                    "components.home.hero.searchForAnyService",
                                )}
                                autoComplete="off"
                                value={query}
                                onBlur={() =>
                                    window.setTimeout(
                                        () => setFocused(false),
                                        120,
                                    )
                                }
                                onChange={(event) =>
                                    setQuery(event.target.value)
                                }
                                onFocus={() => setFocused(true)}
                            />
                            {focused ? (
                                <SearchSuggestionDropdown
                                    {...suggestions}
                                    query={query}
                                    onSelect={selectSuggestion}
                                />
                            ) : null}
                        </label>
                        <button
                            className="hero-search-button"
                            type="submit"
                            aria-label={t("components.home.hero.search")}
                        >
                            <Icon name="search" />
                        </button>
                    </form>

                    <div
                        className="popular-tags"
                        aria-label={t(
                            "components.home.hero.popularServiceSearches",
                        )}
                    >
                        {popularTags.map((tag) => {
                            const path = `/search/gigs?query=${encodeURIComponent(tag)}&source=hero-tag`;
                            return (
                                <a
                                    className="hero-tag"
                                    href={path}
                                    key={tag}
                                    onClick={(event) => {
                                        event.preventDefault();
                                        onNavigate(path);
                                    }}
                                >
                                    {tag}
                                    <Icon name="arrowRight" />
                                </a>
                            );
                        })}
                    </div>

                    <div
                        className="trusted-row"
                        aria-label={t("components.home.hero.trustedBy")}
                    >
                        <span>{t("components.home.hero.trustedBy2")}</span>
                        {trustedBrands.map((brand) => (
                            <strong key={brand}>{brand}</strong>
                        ))}
                    </div>
                </div>
            </div>

            <button
                className="hero-pause-button"
                type="button"
                aria-label={t("components.home.hero.pauseBackgroundVideo")}
            >
                <span aria-hidden="true">{t("components.home.hero.ii")}</span>
            </button>
        </section>
    );
}
export default Hero;
