import { profilePathForSeller, slugifySellerName } from "./profilePaths.js";

const featureRows = [
    "Functional Web App",
    "Desktop Application",
    "Integration of an AI model to existing app",
    "AI Model Fine-tuning",
    "Chatbot integration",
    "Source Code",
];

export function createDetailFromGig(gig) {
    const media = normalizeMedia(gig);
    const gallery = media.length
        ? media
        : [
              {
                  type: "image",
                  url: gig.image,
                  thumbnailUrl: gig.image,
                  altText: `${gig.title} preview`,
              },
          ].filter((item) => item.url);
    const packages = normalizePackages(gig);
    const description = String(gig.description || "").trim();
    const paragraphs = description
        ? description.split(/\n{2,}|\r?\n/).filter(Boolean)
        : [];
    const relatedTags = [
        ...(gig.relatedTags || []),
        gig.categoryLabel,
        ...(gig.serviceOptions || []),
    ].filter(Boolean);

    return {
        id: gig.id,
        title: gig.title,
        breadcrumbs: [
            "Programming & Tech",
            gig.categoryLabel || "Services",
            "Freelance Services",
        ],
        relatedTags: [...new Set(relatedTags)].slice(0, 8),
        seller: {
            name: gig.seller,
            tagline: gig.sellerTitle || gig.categoryLabel || "bdgigs seller",
            level: gig.level || gig.sellerLevel || "New Seller",
            rating: numberOrZero(gig.rating),
            reviews: Number(gig.reviews || 0),
            avatar: gig.avatar,
            initials: gig.sellerInitials,
            from: gig.sellerCountry || "Not shared",
            memberSince: gig.sellerMemberSince || "Not shared",
            responseTime: "Inbox",
            lastDelivery: "Not shared",
            languages: (gig.sellerLanguages || []).join(", ") || "Not shared",
            bio: gig.sellerBio || "",
            userId: gig.sellerUserId,
            username: gig.sellerUsername,
            profilePath:
                gig.sellerProfilePath ||
                profilePathForSeller(gig.seller, gig.sellerUsername),
        },
        gallery,
        packages,
        about: {
            heading: gig.title,
            paragraphs,
            bullets: (gig.serviceOptions || []).slice(0, 6).map((option) => ({
                label: "Service",
                text: titleFromToken(option),
            })),
            why: [],
            closing: "",
        },
        specs: [
            { label: "Category", value: gig.categoryLabel || "Service" },
            {
                label: "Delivery",
                value: `${gig.deliveryDays || 0} ${gig.deliveryDays === 1 ? "day" : "days"}`,
            },
            {
                label: "Consultation",
                value: gig.consultation ? "Available" : "Not offered",
            },
        ].filter((item) => item.value),
        portfolio: null,
        faq: normalizeFaqs(gig.faqs),
        reviews: normalizeReviews(gig),
        media,
    };
}

function normalizeMedia(gig) {
    const media = Array.isArray(gig.media) ? gig.media : [];

    if (media.length) {
        return media
            .filter((item) => item?.url && ["image", "video"].includes(item.type))
            .map((item, index) => ({
                type: item.type || "image",
                url: item.url,
                thumbnailUrl: item.thumbnailUrl || item.url,
                altText: item.altText || `${gig.title} preview ${index + 1}`,
            }));
    }

    return (gig.galleryImages || [])
        .filter(Boolean)
        .map((image, index) => ({
            type: "image",
            url: image,
            thumbnailUrl: image,
            altText: `${gig.title} preview ${index + 1}`,
        }));
}

function normalizePackages(gig) {
    const source = Array.isArray(gig.packages) && gig.packages.length
        ? gig.packages
        : [
              {
                  id: "basic",
                  name: "Basic",
                  title: "Starter package",
                  description: "A focused package for this service.",
                  delivery: `${gig.deliveryDays || 3}-day delivery`,
                  revisions: "Revisions included",
                  price: gig.price || 0,
              },
          ];

    return source.map((pkg, index) => {
        const fallbackId = ["basic", "standard", "premium"][index] || `package-${index + 1}`;
        const price = Number.parseFloat(
            String(pkg.price || gig.price || 0).replace(/[^0-9.]/g, ""),
        );

        return {
            id: pkg.id || fallbackId,
            name: pkg.name || pkg.label || titleFromToken(fallbackId),
            title: pkg.title || pkg.name || "Service package",
            description: pkg.description || "Package details will be confirmed before checkout.",
            delivery: pkg.delivery || `${gig.deliveryDays || 3}-day delivery`,
            deliveryTime: pkg.deliveryTime || pkg.delivery || `${gig.deliveryDays || 3} days`,
            revisions: pkg.revisions || "Revisions included",
            price: Number.isFinite(price) ? price : 0,
            features: normalizeFeatures(pkg.features),
        };
    });
}

function normalizeFeatures(features = {}) {
    return featureRows.reduce((normalized, feature) => {
        normalized[feature] = Boolean(features[feature]);
        return normalized;
    }, {});
}

function normalizeFaqs(faqs = []) {
    return Array.isArray(faqs)
        ? faqs.filter((item) => item?.question && item?.answer)
        : [];
}

function normalizeReviews(gig) {
    const count = Number(gig.reviews || 0);
    const rating = numberOrZero(gig.rating);

    return {
        count,
        rating,
        breakdown: count
            ? [{ label: "Reviews", count, value: Math.min(100, count) }]
            : [],
        ratings: rating
            ? [{ label: "Overall rating", value: rating }]
            : [],
        sample: null,
    };
}

function titleFromToken(value = "") {
    return String(value)
        .replace(/[-_]+/g, " ")
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function numberOrZero(value) {
    const number = Number(value);

    return Number.isFinite(number) ? number : 0;
}

export { slugifySellerName };
