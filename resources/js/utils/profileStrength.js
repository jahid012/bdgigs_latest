function hasValue(value) {
    if (Array.isArray(value)) {
        return value.length > 0;
    }

    if (value && typeof value === "object") {
        return Object.values(value).some(hasValue);
    }

    return Boolean(String(value || "").trim());
}

function scoreProfile(items) {
    const completed = items.filter(({ value }) => hasValue(value)).length;
    const total = items.length || 1;

    return {
        completed,
        total,
        percent: Math.round((completed / total) * 100),
    };
}

export function buyerProfileStrength(profile) {
    return scoreProfile([
        { value: profile.avatar },
        { value: profile.name },
        { value: profile.location },
        { value: profile.overview },
        { value: profile.workingDays },
        { value: profile.workingHours },
        { value: profile.timezone },
        { value: profile.languages },
    ]);
}

export function sellerProfileStrength(profile) {
    return scoreProfile([
        { value: profile.avatar },
        { value: profile.name },
        { value: profile.location },
        { value: profile.title },
        { value: profile.about },
        { value: profile.languageItems },
        { value: profile.skills },
        { value: profile.projects },
        { value: profile.featuredClients },
        { value: profile.workExperience },
        { value: profile.education },
        { value: profile.certification },
    ]);
}
