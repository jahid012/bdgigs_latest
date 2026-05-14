import { useMemo, useRef, useState } from "react";
import { Link, useLocation } from "react-router-dom";
import {
  deliveryOptions,
  listingFilterGroups,
  listingGigs,
  listingSortOptions,
  websiteCategoryPage,
} from "../data/gigListingData.js";
import { profilePathForSeller } from "../data/userProfileData.js";
import { useDismissOnInteractOutside } from "../hooks/useDismissOnInteractOutside.js";
import { Icon } from "../components/common/Icons.jsx";
import Footer from "../components/layout/Footer.jsx";
import Header from "../components/layout/Header.jsx";

const defaultFilters = {
  category: "",
  serviceOptions: [],
  sellerDetails: [],
  deliveryTime: "",
  maxBudget: "",
  pro: false,
  instant: false,
  sort: "relevance",
};

const filterButtonLabels = {
  category: "Category",
  serviceOptions: "Service options",
  sellerDetails: "Seller details",
  budget: "Budget",
  deliveryTime: "Delivery time",
};

function GigListingPage({ onNavigate }) {
  const location = useLocation();
  const searchParams = new URLSearchParams(location.search);
  const query = searchParams.get("query")?.trim() || "";
  const isSearchPage = location.pathname.startsWith("/search");
  const pageMeta = getPageMeta(location.pathname, query, isSearchPage);
  const [filters, setFilters] = useState(defaultFilters);
  const [draftFilters, setDraftFilters] = useState(defaultFilters);
  const [activePanel, setActivePanel] = useState(null);
  const [activePage, setActivePage] = useState(4);
  const [isHistoryHidden, setIsHistoryHidden] = useState(false);
  const [isHistoryExpanded, setIsHistoryExpanded] = useState(false);
  const filterRef = useRef(null);
  const historyRef = useRef(null);

  useDismissOnInteractOutside(filterRef, Boolean(activePanel), () => setActivePanel(null));

  const scopedGigs = useMemo(() => getScopedGigs({ isSearchPage, query, pathname: location.pathname }), [isSearchPage, location.pathname, query]);
  const filteredGigs = useMemo(() => sortGigs(applyFilters(scopedGigs, filters), filters.sort), [filters, scopedGigs]);
  const firstOrderGigs = useMemo(() => scopedGigs.filter((gig) => gig.featured).slice(0, 4), [scopedGigs]);
  const displayedGigs = useMemo(() => getPagedGigs(filteredGigs, activePage), [activePage, filteredGigs]);
  const hasFilters = hasActiveFilters(filters);
  const resultLabel = hasFilters ? `${filteredGigs.length} results` : pageMeta.resultLabel;
  const activeButtons = isSearchPage
    ? ["category", "serviceOptions", "sellerDetails", "budget", "deliveryTime"]
    : ["serviceOptions", "sellerDetails", "budget", "deliveryTime"];

  const openPanel = (panel) => {
    setDraftFilters(filters);
    setActivePanel((current) => (current === panel ? null : panel));
  };

  const applyDraftFilters = () => {
    setFilters(draftFilters);
    setActivePanel(null);
  };

  const clearActivePanel = () => {
    setDraftFilters((current) => clearFilterGroup(current, activePanel));
  };

  const toggleCategoryChip = (optionId) => {
    setFilters((current) => ({
      ...current,
      serviceOptions: toggleValue(current.serviceOptions, optionId),
    }));
  };

  return (
    <div className="listing-page">
      <Header enableMarketplaceHeader={false} forceSearch onNavigate={onNavigate} searchQuery={query} />

      <main className="listing-main">
        <div className="container">
          {isSearchPage ? <SearchHeading query={query} /> : <CategoryHeading pageMeta={pageMeta} onNavigate={onNavigate} />}

          {!isSearchPage ? (
            <CategoryChipRail chips={pageMeta.chips} selectedOptions={filters.serviceOptions} onToggle={toggleCategoryChip} />
          ) : null}

          <section className="listing-toolbar" aria-label="Gig filters and sorting">
            <div className="listing-filter-row" ref={filterRef}>
              <div className="listing-filter-buttons">
                {activeButtons.map((panel) => (
                  <div className="listing-filter-group" key={panel}>
                    <button
                      className={`listing-filter-button${activePanel === panel ? " is-open" : ""}`}
                      type="button"
                      aria-expanded={activePanel === panel}
                      onClick={() => openPanel(panel)}
                    >
                      {filterButtonLabels[panel]}
                      <Icon name="chevronDown" />
                    </button>
                    {activePanel === panel ? (
                      <FilterPopover
                        panel={panel}
                        draftFilters={draftFilters}
                        setDraftFilters={setDraftFilters}
                        onApply={applyDraftFilters}
                        onClear={clearActivePanel}
                      />
                    ) : null}
                  </div>
                ))}
              </div>

              <div className="listing-toolbar-actions">
                <ToggleFilter
                  checked={filters.pro}
                  label="Pro services"
                  onChange={(checked) => setFilters((current) => ({ ...current, pro: checked }))}
                />
                <ToggleFilter
                  checked={filters.instant}
                  label="Instant response"
                  onChange={(checked) => setFilters((current) => ({ ...current, instant: checked }))}
                />
              </div>
            </div>

            <div className="listing-result-row">
              <span>{resultLabel}</span>
              <label className="listing-sort">
                <span>Sort by:</span>
                <select value={filters.sort} onChange={(event) => setFilters((current) => ({ ...current, sort: event.target.value }))}>
                  {listingSortOptions.map((option) => (
                    <option value={option.id} key={option.id}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </label>
            </div>
          </section>

          {isSearchPage && firstOrderGigs.length ? (
            <section className="first-order-panel" aria-labelledby="firstOrderTitle">
              <div className="first-order-heading">
                <span className="first-order-icon" aria-hidden="true">
                  <Icon name="packageCheck" />
                </span>
                <div>
                  <h2 id="firstOrderTitle">Top freelancers for your first order</h2>
                  <p>Discover sellers with a great track record in guiding new buyers.</p>
                </div>
              </div>
              <GigGrid gigs={firstOrderGigs} compact />
            </section>
          ) : null}

          {filteredGigs.length ? (
            <>
              <GigGrid gigs={displayedGigs} />
              <ListingPagination activePage={activePage} onPageChange={setActivePage} />
            </>
          ) : (
            <div className="listing-empty-state">
              <Icon name="search" />
              <h2>No gigs match these filters</h2>
              <p>Clear one or two filters to bring more services back into view.</p>
              <button type="button" onClick={() => setFilters(defaultFilters)}>
                Clear filters
              </button>
            </div>
          )}
        </div>

        <ListingBottomSections
          historyGigs={listingGigs.slice(0, isHistoryExpanded ? 12 : 8)}
          historyRef={historyRef}
          isHistoryHidden={isHistoryHidden}
          isHistoryExpanded={isHistoryExpanded}
          onClearHistory={() => setIsHistoryHidden(true)}
          onNavigate={onNavigate}
          onScrollHistory={(direction) => historyRef.current?.scrollBy({ left: direction * 280, behavior: "smooth" })}
          onToggleHistory={() => setIsHistoryExpanded((expanded) => !expanded)}
        />
      </main>

      <Footer />
    </div>
  );
}

function ListingPagination({ activePage, onPageChange }) {
  const pages = Array.from({ length: 10 }, (_, index) => index + 1);

  const changePage = (page) => {
    const nextPage = Math.min(Math.max(page, 1), pages.length);
    onPageChange(nextPage);
    window.scrollTo({ top: 160, behavior: "smooth" });
  };

  return (
    <nav className="listing-pagination" aria-label="Gig results pagination">
      <button type="button" aria-label="Previous page" onClick={() => changePage(activePage - 1)}>
        <Icon name="arrowRight" />
      </button>
      {pages.map((page) => (
        <button className={page === activePage ? "is-active" : ""} type="button" key={page} onClick={() => changePage(page)}>
          {page}
        </button>
      ))}
      <button type="button" aria-label="Next page" onClick={() => changePage(activePage + 1)}>
        <Icon name="arrowRight" />
      </button>
    </nav>
  );
}

function ListingBottomSections({
  historyGigs,
  historyRef,
  isHistoryHidden,
  isHistoryExpanded,
  onClearHistory,
  onNavigate,
  onScrollHistory,
  onToggleHistory,
}) {
  return (
    <section className="listing-bottom-surface" aria-label="More ways to hire">
      <div className="container">
        {!isHistoryHidden ? (
          <section className="browsing-history-section" aria-labelledby="browsingHistoryTitle">
            <div className="listing-bottom-heading">
              <h2 id="browsingHistoryTitle">Your Browsing History</h2>
              <div className="browsing-history-actions">
                <button type="button" onClick={onClearHistory}>
                  Clear All
                </button>
                <span aria-hidden="true"></span>
                <button type="button" onClick={onToggleHistory}>
                  {isHistoryExpanded ? "Show Less" : "See All"}
                </button>
                <button type="button" aria-label="Scroll history left" onClick={() => onScrollHistory(-1)}>
                  <Icon name="arrowRight" />
                </button>
                <button type="button" aria-label="Scroll history right" onClick={() => onScrollHistory(1)}>
                  <Icon name="arrowRight" />
                </button>
              </div>
            </div>

            <div className="browsing-history-row" ref={historyRef}>
              {historyGigs.map((gig) => (
                <a
                  className="history-gig-card"
                  href={`/gigs/${gig.id}`}
                  key={gig.id}
                  onClick={(event) => {
                    event.preventDefault();
                    onNavigate(`/gigs/${gig.id}`);
                  }}
                >
                  <span className="history-gig-media">
                    <img src={gig.image} alt="" loading="lazy" decoding="async" />
                    <span aria-hidden="true">
                      <Icon name="heart" />
                    </span>
                  </span>
                  <strong>{gig.title}</strong>
                </a>
              ))}
            </div>
          </section>
        ) : null}

        <section className="talent-way-section" aria-labelledby="talentWayTitle">
          <h2 id="talentWayTitle">Find freelance talent - your way</h2>
          <div className="talent-way-grid">
            <TalentWayCard
              action="Post a brief"
              copy="Generate a brief with AI to receive a curated shortlist of freelancer offers."
              icon="document"
              meta=""
              onClick={() => onNavigate("/search/gigs?query=project%20brief&source=talent-way")}
              title="Post a project brief"
            />
            <TalentWayCard
              action="Get started"
              copy="Save the endless search - we'll source, interview, and vet freelancers for you."
              icon="user"
              meta="Only $89"
              onClick={() => onNavigate("/search/gigs?query=expert%20sourcing&source=talent-way")}
              title="Let us find your freelancer"
            />
            <TalentWayCard
              action="Book free consultation"
              copy="Big project? No problem. We'll build a freelance team and fully execute your project."
              icon="verifiedUser"
              meta="Custom pricing"
              onClick={() => onNavigate("/search/gigs?query=freelance%20team&source=talent-way")}
              title="Get a team built for you"
            />
          </div>
        </section>
      </div>
    </section>
  );
}

function TalentWayCard({ action, copy, icon, meta, onClick, title }) {
  return (
    <article className="talent-way-card">
      <Icon name={icon} />
      <h3>{title}</h3>
      <p>{copy}</p>
      <div>
        {meta ? <strong>{meta}</strong> : <span></span>}
        <button type="button" onClick={onClick}>
          {action}
        </button>
      </div>
    </article>
  );
}

function SearchHeading({ query }) {
  return (
    <header className="listing-search-heading">
      <h1>
        Results for <strong>{query || "all services"}</strong>
      </h1>
    </header>
  );
}

function CategoryHeading({ pageMeta, onNavigate }) {
  return (
    <header className="listing-category-heading">
      <nav className="listing-breadcrumb" aria-label="Breadcrumb">
        <button type="button" aria-label="Go to home page" onClick={() => onNavigate("home")}>
          <Icon name="home" />
        </button>
        <span aria-hidden="true">/</span>
        <span>{pageMeta.parentLabel}</span>
      </nav>
      <div className="listing-heading-row">
        <div>
          <h1>{pageMeta.title}</h1>
          <p>
            {pageMeta.description}
            <a href="#how-bdgigs-works" onClick={(event) => event.preventDefault()}>
              <Icon name="play" />
              How BDGigs Works
            </a>
          </p>
        </div>
      </div>
    </header>
  );
}

function CategoryChipRail({ chips, selectedOptions, onToggle }) {
  return (
    <section className="category-chip-section" aria-label="Website development service types">
      <div className="category-chip-rail">
        {chips.map((chip) => {
          const selected = selectedOptions.includes(chip.optionId);
          return (
            <button className={selected ? "is-selected" : ""} type="button" aria-pressed={selected} key={chip.label} onClick={() => onToggle(chip.optionId)}>
              <span className="category-chip-icon" aria-hidden="true">
                <img src={chip.icon} alt="" />
              </span>
              {chip.label}
            </button>
          );
        })}
      </div>
    </section>
  );
}

function FilterPopover({ panel, draftFilters, setDraftFilters, onApply, onClear }) {
  return (
    <div className={`listing-filter-popover ${panel}`} role="dialog" aria-label={`${filterButtonLabels[panel]} filters`}>
      <div className="listing-filter-scroll">
        {panel === "category" ? <CategoryFilter draftFilters={draftFilters} setDraftFilters={setDraftFilters} /> : null}
        {panel === "serviceOptions" ? (
          <CheckboxSections
            field="serviceOptions"
            sections={listingFilterGroups.serviceOptions}
            draftFilters={draftFilters}
            setDraftFilters={setDraftFilters}
          />
        ) : null}
        {panel === "sellerDetails" ? (
          <CheckboxSections
            field="sellerDetails"
            sections={listingFilterGroups.sellerDetails}
            draftFilters={draftFilters}
            setDraftFilters={setDraftFilters}
          />
        ) : null}
        {panel === "budget" ? <BudgetFilter draftFilters={draftFilters} setDraftFilters={setDraftFilters} /> : null}
        {panel === "deliveryTime" ? <DeliveryFilter draftFilters={draftFilters} setDraftFilters={setDraftFilters} /> : null}
      </div>
      <div className="listing-filter-footer">
        <button type="button" onClick={onClear}>
          Clear all
        </button>
        <button type="button" onClick={onApply}>
          Apply
        </button>
      </div>
    </div>
  );
}

function CategoryFilter({ draftFilters, setDraftFilters }) {
  return (
    <div className="category-filter-list">
      {listingFilterGroups.category.map((item) => {
        const selected = item.id === "all-categories" ? !draftFilters.category : draftFilters.category === item.id;
        return (
          <button
            className={selected ? "is-selected" : ""}
            type="button"
            key={item.id}
            onClick={() => setDraftFilters((current) => ({ ...current, category: item.id === "all-categories" ? "" : item.id }))}
          >
            <span aria-hidden="true"></span>
            <strong>{item.label}</strong>
            {item.count ? <small>({item.count})</small> : null}
          </button>
        );
      })}
    </div>
  );
}

function CheckboxSections({ field, sections, draftFilters, setDraftFilters }) {
  return (
    <>
      {sections.map((section) => (
        <section className="listing-filter-section" key={section.title}>
          <h3>{section.title}</h3>
          <div className="listing-filter-options">
            {section.options.map((option) => {
              const checked = draftFilters[field].includes(option.id);
              return (
                <label className="listing-checkbox-option" key={option.id}>
                  <input
                    type="checkbox"
                    checked={checked}
                    onChange={() =>
                      setDraftFilters((current) => ({
                        ...current,
                        [field]: toggleValue(current[field], option.id),
                      }))
                    }
                  />
                  <span aria-hidden="true"></span>
                  <span>
                    <strong>
                      {option.label}
                      {option.badge ? <em>{option.badge}</em> : null}
                    </strong>
                    <small>({option.count})</small>
                    {option.hint ? <p>{option.hint}</p> : null}
                  </span>
                </label>
              );
            })}
          </div>
          {section.more ? <button className="listing-more-filter" type="button">{section.more}</button> : null}
        </section>
      ))}
    </>
  );
}

function BudgetFilter({ draftFilters, setDraftFilters }) {
  return (
    <section className="budget-filter">
      <label>
        <span>Up to</span>
        <strong>
          $
          <input
            type="number"
            min="0"
            value={draftFilters.maxBudget}
            onChange={(event) => setDraftFilters((current) => ({ ...current, maxBudget: event.target.value }))}
            aria-label="Maximum budget"
          />
        </strong>
      </label>
    </section>
  );
}

function DeliveryFilter({ draftFilters, setDraftFilters }) {
  return (
    <section className="listing-filter-section delivery-filter">
      <h3>Delivery time</h3>
      <div className="listing-filter-options single-column">
        {deliveryOptions.map((option) => (
          <label className="listing-checkbox-option" key={option.id}>
            <input
              type="radio"
              name="deliveryTime"
              checked={draftFilters.deliveryTime === option.id}
              onChange={() => setDraftFilters((current) => ({ ...current, deliveryTime: option.id }))}
            />
            <span aria-hidden="true"></span>
            <span>
              <strong>{option.label}</strong>
              <small>({option.count})</small>
            </span>
          </label>
        ))}
      </div>
    </section>
  );
}

function ToggleFilter({ checked, label, onChange }) {
  return (
    <label className="listing-switch">
      <input type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)} />
      <span aria-hidden="true"></span>
      {label}
    </label>
  );
}

function GigGrid({ gigs, compact = false }) {
  return (
    <div className={`gig-listing-grid${compact ? " is-compact" : ""}`}>
      {gigs.map((gig) => (
        <GigCard gig={gig} key={gig.id} />
      ))}
    </div>
  );
}

function GigCard({ gig }) {
  return (
    <article className="gig-listing-card">
      <div className="gig-listing-media">
        <Link className="gig-listing-image-link" to={`/gigs/${gig.id}`} aria-label={`View ${gig.title}`}>
          <img src={gig.image} alt={`${gig.title} preview`} />
        </Link>
        <button className="gig-favorite-button" type="button" aria-label={`Save ${gig.title}`}>
          <Icon name="heart" />
        </button>
      </div>
      <div className="gig-listing-body">
        <div className="gig-seller-row">
          <Link className="gig-seller-profile-link" to={profilePathForSeller(gig.seller)}>
            <span className="gig-avatar">
              <img src={gig.avatar} alt="" />
            </span>
            <strong>{gig.seller}</strong>
          </Link>
          <span>{gig.level}</span>
        </div>
        <h2>
          <Link to={`/gigs/${gig.id}`}>{gig.title}</Link>
        </h2>
        <div className="gig-rating-row">
          <Icon name="star" />
          <strong>{gig.rating.toFixed(1)}</strong>
          <span>({gig.reviews})</span>
        </div>
        <p className="gig-price">From ${gig.price}</p>
        {gig.consultation ? (
          <p className="gig-consultation">
            <Icon name="video" />
            Offers video consultations
          </p>
        ) : null}
      </div>
    </article>
  );
}

function getPageMeta(pathname, query, isSearchPage) {
  if (isSearchPage) {
    return {
      title: `Results for ${query || "all services"}`,
      resultLabel: query.toLowerCase() === "codecanyon" ? "555 results" : "1,200+ results",
    };
  }

  const segments = pathname.split("/").filter(Boolean);
  const parentSlug = segments[1] || "programming-tech";
  const titleSlug = segments[2] || "website-development";
  const isWebsiteDevelopment = titleSlug === "website-development";

  return {
    parentLabel: titleFromSlug(parentSlug),
    title: isWebsiteDevelopment ? websiteCategoryPage.title : titleFromSlug(titleSlug),
    description: isWebsiteDevelopment
      ? websiteCategoryPage.description
      : `Find skilled freelancers for ${titleFromSlug(titleSlug).toLowerCase()} projects.`,
    resultLabel: isWebsiteDevelopment ? websiteCategoryPage.resultLabel : "12,000+ results",
    chips: websiteCategoryPage.chips,
  };
}

function getScopedGigs({ isSearchPage, query, pathname }) {
  if (isSearchPage) {
    const normalizedQuery = query.toLowerCase();
    if (!normalizedQuery) return listingGigs;
    const matches = listingGigs.filter((gig) => [gig.title, gig.seller, gig.categoryLabel, gig.searchText].join(" ").toLowerCase().includes(normalizedQuery));
    return matches.length ? matches : listingGigs;
  }

  const titleSlug = pathname.split("/").filter(Boolean)[2] || "website-development";
  if (titleSlug !== "website-development") return listingGigs;

  const websiteCategories = ["web-application-development", "custom-websites-development", "website-customization", "website-installation"];
  return listingGigs.filter((gig) => websiteCategories.includes(gig.categoryId) || gig.searchText.includes("website"));
}

function applyFilters(gigs, filters) {
  return gigs.filter((gig) => {
    if (filters.category && gig.categoryId !== filters.category) return false;
    if (filters.serviceOptions.length && !filters.serviceOptions.every((option) => gig.serviceOptions.includes(option))) return false;
    if (filters.sellerDetails.length && !filters.sellerDetails.every((detail) => gig.sellerDetails.includes(detail))) return false;
    if (filters.maxBudget && gig.price > Number(filters.maxBudget)) return false;
    if (filters.deliveryTime) {
      const delivery = deliveryOptions.find((option) => option.id === filters.deliveryTime);
      if (delivery && gig.deliveryDays > delivery.maxDays) return false;
    }
    if (filters.pro && !gig.pro) return false;
    if (filters.instant && !gig.instant) return false;
    return true;
  });
}

function sortGigs(gigs, sort) {
  const sorted = [...gigs];
  if (sort === "price-low") return sorted.sort((a, b) => a.price - b.price);
  if (sort === "rating") return sorted.sort((a, b) => b.rating - a.rating || b.reviews - a.reviews);
  if (sort === "fastest") return sorted.sort((a, b) => a.deliveryDays - b.deliveryDays);
  if (sort === "best-selling") return sorted.sort((a, b) => b.reviews - a.reviews);
  return sorted;
}

function getPagedGigs(gigs, activePage) {
  const pageSize = 8;
  if (gigs.length <= pageSize) return gigs;

  const start = ((activePage - 1) * pageSize) % gigs.length;
  return [...gigs.slice(start), ...gigs.slice(0, start)].slice(0, pageSize);
}

function clearFilterGroup(filters, panel) {
  if (panel === "category") return { ...filters, category: "" };
  if (panel === "serviceOptions") return { ...filters, serviceOptions: [] };
  if (panel === "sellerDetails") return { ...filters, sellerDetails: [] };
  if (panel === "budget") return { ...filters, maxBudget: "" };
  if (panel === "deliveryTime") return { ...filters, deliveryTime: "" };
  return filters;
}

function hasActiveFilters(filters) {
  return Boolean(
    filters.category ||
      filters.serviceOptions.length ||
      filters.sellerDetails.length ||
      filters.deliveryTime ||
      filters.maxBudget ||
      filters.pro ||
      filters.instant,
  );
}

function toggleValue(values, value) {
  return values.includes(value) ? values.filter((item) => item !== value) : [...values, value];
}

function titleFromSlug(slug) {
  const special = {
    "programming-tech": "Programming & Tech",
    "graphics-design": "Graphics & Design",
    "digital-marketing": "Digital Marketing",
    "video-animation": "Video & Animation",
    "writing-translation": "Writing & Translation",
    "music-audio": "Music & Audio",
    "ai-services": "AI Services",
  };

  if (special[slug]) return special[slug];
  return slug
    .split("-")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
}

export default GigListingPage;
