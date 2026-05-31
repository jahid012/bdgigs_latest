import { useEffect, useState } from "react";
import { apiRequest } from "../api/apiClient.js";
import { useMarketplaceStore } from "../stores/useMarketplaceStore.js";
import { useSessionStore } from "../stores/useSessionStore.js";
import { readRecentlyViewedGigs } from "../utils/recentlyViewedGigs.js";

const emptyBootstrap = {
    creatorMarketplace: [],
    error: null,
    featuredGigs: [],
    hasLoaded: false,
    marketplaceCategories: [],
};

export function useHomeBootstrap() {
    const hydrateFromBootstrap = useSessionStore(
        (state) => state.hydrateFromBootstrap,
    );
    const setCurrentUser = useSessionStore((state) => state.setCurrentUser);
    const hydrateHomeGigs = useMarketplaceStore(
        (state) => state.hydrateHomeGigs,
    );
    const [bootstrap, setBootstrap] = useState(emptyBootstrap);

    useEffect(() => {
        let active = true;

        apiRequest(homeBootstrapPath())
            .then((data) => {
                if (!active) return;

                const session = data?.session || null;
                const featuredGigs = data?.featuredGigs || [];

                hydrateSession(session, hydrateFromBootstrap, setCurrentUser);
                hydrateHomeGigs(featuredGigs);
                setBootstrap({
                    creatorMarketplace: data?.creatorMarketplace || [],
                    error: null,
                    featuredGigs,
                    hasLoaded: true,
                    marketplaceCategories: data?.marketplaceCategories || [],
                });
            })
            .catch((error) => {
                if (!active) return;

                setBootstrap((current) => ({
                    ...current,
                    error: error.message,
                    hasLoaded: true,
                }));
            });

        return () => {
            active = false;
        };
    }, [hydrateFromBootstrap, hydrateHomeGigs, setCurrentUser]);

    return bootstrap;
}

function hydrateSession(session, hydrateFromBootstrap, setCurrentUser) {
    if (typeof hydrateFromBootstrap === "function") {
        hydrateFromBootstrap(session);
        return;
    }

    setCurrentUser(session?.authenticated ? session : null);
}

function homeBootstrapPath() {
    const params = new URLSearchParams();

    readRecentlyViewedGigs()
        .slice(0, 12)
        .forEach((gig) => {
            if (gig?.id) {
                params.append("recentGigs[]", gig.id);
            }
        });

    return params.size
        ? `/api/home/bootstrap?${params.toString()}`
        : "/api/home/bootstrap";
}
