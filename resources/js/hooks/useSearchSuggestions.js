import { useEffect, useState } from "react";
import { apiRequest } from "../api/apiClient.js";

export function useSearchSuggestions(query) {
    const [state, setState] = useState({
        error: "",
        isLoading: false,
        suggestions: [],
    });

    useEffect(() => {
        const trimmed = String(query || "").trim();

        if (trimmed.length < 2) {
            setState({ error: "", isLoading: false, suggestions: [] });
            return undefined;
        }

        let active = true;
        const timer = window.setTimeout(() => {
            setState((current) => ({ ...current, error: "", isLoading: true }));
            apiRequest(
                `/api/search/suggestions?q=${encodeURIComponent(trimmed)}`,
            )
                .then((suggestions) => {
                    if (active) {
                        setState({
                            error: "",
                            isLoading: false,
                            suggestions: suggestions || [],
                        });
                    }
                })
                .catch((error) => {
                    if (active) {
                        setState({
                            error:
                                error.message ||
                                "Search suggestions are unavailable.",
                            isLoading: false,
                            suggestions: [],
                        });
                    }
                });
        }, 180);

        return () => {
            active = false;
            window.clearTimeout(timer);
        };
    }, [query]);

    return state;
}
