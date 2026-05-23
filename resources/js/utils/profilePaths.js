export function slugifySellerName(name = "") {
    return (
        String(name)
            .toLowerCase()
            .replace(/&/g, "and")
            .replace(/[^a-z0-9]+/g, "-")
            .replace(/^-+|-+$/g, "") || "seller"
    );
}

export function profilePathForSeller(name, username = "") {
    const handle = String(username || "")
        .trim()
        .replace(/^@/, "");

    return `/users/${handle || slugifySellerName(name)}`;
}

export function initialsFromName(name = "") {
    const initials = String(name)
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.slice(0, 1).toUpperCase())
        .join("");

    return initials || "BD";
}
