const RECENTLY_VIEWED_KEY = "bdgigs_recent_gigs";
const RECENTLY_VIEWED_EVENT = "bdgigs:recently-viewed-updated";
const MAX_RECENTLY_VIEWED = 12;

export function readRecentlyViewedGigs() {
    if (typeof window === "undefined") return [];

    try {
        const parsed = JSON.parse(
            window.localStorage.getItem(RECENTLY_VIEWED_KEY) || "[]",
        );

        return Array.isArray(parsed) ? parsed.filter((gig) => gig?.id) : [];
    } catch {
        return [];
    }
}

export function rememberRecentlyViewedGig(gig) {
    if (typeof window === "undefined" || !gig?.id) return [];

    const summary = {
        id: gig.id,
        title: gig.title,
        description: gig.description,
        seller: gig.seller,
        sellerUsername: gig.sellerUsername,
        sellerProfilePath: gig.sellerProfilePath,
        sellerInitials: gig.sellerInitials,
        avatar: gig.avatar,
        level: gig.level,
        image: gig.image,
        imageAlt: gig.imageAlt || `${gig.title} preview`,
        rating: gig.rating,
        reviews: gig.reviews,
        price: gig.price,
        consultation: gig.consultation,
        saved: gig.saved,
        viewedAt: new Date().toISOString(),
    };
    const next = [
        summary,
        ...readRecentlyViewedGigs().filter((item) => item.id !== summary.id),
    ].slice(0, MAX_RECENTLY_VIEWED);

    window.localStorage.setItem(RECENTLY_VIEWED_KEY, JSON.stringify(next));
    window.dispatchEvent(new CustomEvent(RECENTLY_VIEWED_EVENT, { detail: next }));

    return next;
}

export function subscribeToRecentlyViewedGigs(callback) {
    if (typeof window === "undefined") return () => {};

    const handler = (event) => {
        callback(event.detail || readRecentlyViewedGigs());
    };

    window.addEventListener(RECENTLY_VIEWED_EVENT, handler);
    window.addEventListener("storage", handler);

    return () => {
        window.removeEventListener(RECENTLY_VIEWED_EVENT, handler);
        window.removeEventListener("storage", handler);
    };
}
