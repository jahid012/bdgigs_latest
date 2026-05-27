import { Icon } from "./Icons.jsx";

function SearchSuggestionDropdown({
    emptyText = "No suggestions found.",
    error = "",
    isLoading = false,
    onSelect,
    query = "",
    suggestions = [],
}) {
    const shouldShow = String(query || "").trim().length >= 2;

    if (!shouldShow) {
        return null;
    }

    return (
        <div className="search-suggestion-dropdown" role="listbox">
            {isLoading ? (
                <p className="search-suggestion-status">Finding matches...</p>
            ) : null}
            {!isLoading && error ? (
                <p className="search-suggestion-status">{error}</p>
            ) : null}
            {!isLoading && !error && suggestions.length === 0 ? (
                <p className="search-suggestion-status">{emptyText}</p>
            ) : null}
            {!isLoading && !error
                ? suggestions.map((suggestion) => (
                      <button
                          type="button"
                          role="option"
                          key={suggestion.id || suggestion.path}
                          onMouseDown={(event) => event.preventDefault()}
                          onClick={() => onSelect(suggestion)}
                      >
                          <span>
                              <Icon
                                  name={
                                      suggestion.type === "Gig"
                                          ? "orders"
                                          : "search"
                                  }
                              />
                          </span>
                          <strong>{suggestion.title}</strong>
                          <small>
                              {suggestion.type}
                              {suggestion.description
                                  ? ` - ${suggestion.description}`
                                  : ""}
                          </small>
                      </button>
                  ))
                : null}
        </div>
    );
}

export default SearchSuggestionDropdown;
