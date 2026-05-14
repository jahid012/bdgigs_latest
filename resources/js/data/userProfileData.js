import { aiGigDetail } from "./gigDetailsData.js";
import { listingGigs } from "./gigListingData.js";

export function slugifySellerName(name = "") {
    return (
        name
            .toLowerCase()
            .replace(/&/g, "and")
            .replace(/[^a-z0-9]+/g, "-")
            .replace(/^-+|-+$/g, "") || "seller"
    );
}

export function profilePathForSeller(name) {
    return `/users/${slugifySellerName(name)}`;
}

export const ahmadProfile = {
    slug: "ahmad",
    name: "Ahmad",
    handle: "@ahmad_dev_9528",
    avatar: "https://images.pexels.com/photos/2379004/pexels-photo-2379004.jpeg?auto=compress&cs=tinysrgb&w=240",
    title: "Full Stack Web Developer",
    level: "Level 1",
    rating: 5,
    reviews: 23,
    location: "Pakistan",
    localTime: "10:52 PM",
    languages: ["English", "French", "Arabic", "Spanish", "Hindi"],
    about: "I'm Ahmad, a Full Stack Web Developer with 6+ years of experience specializing in website and mobile development. I build complex, large-scale, and high-performance web applications using React, Node.js, Express, MongoDB, PostgreSQL, and GraphQL. I've successfully handled enterprise-grade platforms with clean code, scalable architecture, and reliable delivery.",
    skills: [
        "Mobile developer",
        "Generic website expert",
        "Website developer",
        "Software developer",
        "SQL Database expert",
        "+6",
    ],
    responseTime: "1 hour",
    services: [
        {
            id: "full-stack-website",
            title: "Full Stack Web Applications",
            description:
                "I will web application, software development, full stack website development",
            image: "/assets/img/gig_images/1.png",
            price: 100,
        },
    ],
    portfolio: {
        title: "Smart School ERP & LMS Platform",
        date: "From: February 2026",
        description:
            "A production-ready School ERP System designed to digitize and automate core academic and administrative operations. It replaces manual processes with a centralized, secure, and scalable platform that helps schools manage students, staff, fees, classes, and reports.",
        image: "/assets/img/gig_images/20.png",
        thumbnails: [
            "/assets/img/gig_images/20.png",
            "/assets/img/gig_images/21.png",
            "/assets/img/gig_images/1.png",
        ],
        tags: [
            "Educacao",
            "Empresas do Setor Publico",
            "Tecnologia",
            "Tecnologia da Informacao (TI)",
            "Desenvolvimento de Software",
            "+3",
        ],
        cost: "$800-$1000",
        duration: "1-3 months",
    },
    workExperience: [
        {
            role: "Full Stack Web Developer",
            company: "Pakistan Government IT Projects",
            type: "Full-time",
            period: "May 2022 - May 2024",
            duration: "2 yrs",
            description:
                "Contributed to large-scale government web systems handling thousands of users. Built complete end-to-end solutions including frontend, backend, APIs, and databases, with a focus on security, scalability, and performance.",
        },
        {
            role: "Senior Full Stack Developer",
            company: "Dubai-Based Tech Solutions",
            type: "Full-time",
            period: "Feb 2021 - May 2022",
            duration: "1 yr 3 mos",
            description:
                "Worked on blockchain-based platforms and modern business websites. Developed secure, scalable web applications using React, Node.js, Express, MongoDB, and PostgreSQL.",
        },
    ],
    reviewsData: {
        count: 23,
        rating: 5,
        breakdown: [
            { label: "5 Stars", count: 23, value: 100 },
            { label: "4 Stars", count: 0, value: 0 },
            { label: "3 Stars", count: 0, value: 0 },
            { label: "2 Stars", count: 0, value: 0 },
            { label: "1 Star", count: 0, value: 0 },
        ],
        ratings: [
            { label: "Seller communication level", value: 5 },
            { label: "Quality of delivery", value: 5 },
            { label: "Value of delivery", value: 5 },
        ],
        sample: {
            name: "colton_dean",
            country: "United Kingdom",
            badge: "Repeat Client",
            rating: 5,
            date: "1 month ago",
            text: "Excellent experience! The developer delivered a high-quality, responsive website with clean and well-structured code. Communication was smooth, all requirements were clearly understood and implemented, and the project was completed on time with great attention to detail. Highly recommended for web development work!",
            price: "Up to $50",
            duration: "4 days",
            serviceTitle: "Full Stack Web Applications",
            serviceImage: "/assets/img/gig_images/1.png",
        },
    },
};

const explicitProfiles = [ahmadProfile];

export function getUserProfile(username) {
    const slug = slugifySellerName(username);
    const explicit = explicitProfiles.find(
        (profile) =>
            profile.slug === slug || slugifySellerName(profile.name) === slug,
    );

    if (explicit) return explicit;

    const aiSellerSlug = slugifySellerName(aiGigDetail.seller.name);
    if (slug === aiSellerSlug) {
        return createProfileFromDetail(aiGigDetail);
    }

    const sellerGig = listingGigs.find(
        (gig) => slugifySellerName(gig.seller) === slug,
    );
    if (sellerGig) {
        return createProfileFromGig(sellerGig);
    }

    return ahmadProfile;
}

function createProfileFromDetail(detail) {
    return {
        ...ahmadProfile,
        slug: slugifySellerName(detail.seller.name),
        name: detail.seller.name,
        handle: `@${slugifySellerName(detail.seller.name).replace(/-/g, "_")}`,
        avatar: detail.seller.avatar,
        title: detail.seller.tagline,
        level: detail.seller.level,
        rating: detail.seller.rating,
        reviews: detail.seller.reviews,
        location: detail.seller.from,
        languages: detail.seller.languages
            .split(",")
            .map((item) => item.trim()),
        about: detail.seller.bio,
        responseTime: detail.seller.responseTime,
        services: [
            {
                id: detail.id,
                title: "AI Website & Software",
                description: detail.title,
                image: detail.gallery[0],
                price: detail.packages[0].price,
            },
        ],
        portfolio: {
            ...ahmadProfile.portfolio,
            title: detail.portfolio.title,
            date: detail.portfolio.date,
            description: detail.portfolio.description,
            image: detail.portfolio.image,
            thumbnails: detail.portfolio.thumbnails.slice(0, 3),
            tags: detail.portfolio.tags,
            cost: detail.portfolio.cost,
            duration: detail.portfolio.duration,
        },
        reviewsData: detail.reviews,
    };
}

function createProfileFromGig(gig) {
    return {
        ...ahmadProfile,
        slug: slugifySellerName(gig.seller),
        name: gig.seller,
        handle: `@${slugifySellerName(gig.seller).replace(/-/g, "_")}`,
        avatar: gig.avatar,
        title: `${gig.categoryLabel} Specialist`,
        level: gig.level,
        rating: gig.rating,
        reviews: gig.reviews,
        location: "Bangladesh",
        languages: ["English", "Bengali", "Hindi"],
        about: `I'm ${gig.seller}, a ${gig.categoryLabel.toLowerCase()} specialist focused on clean delivery, responsive communication, and practical web solutions for growing businesses.`,
        services: [
            {
                id: gig.id,
                title: gig.categoryLabel,
                description: gig.title,
                image: gig.image,
                price: gig.price,
            },
        ],
        portfolio: {
            ...ahmadProfile.portfolio,
            title: gig.categoryLabel,
            description: gig.title,
            image: gig.image,
            thumbnails: [
                gig.image,
                "/assets/img/gig_images/20.png",
                "/assets/img/gig_images/21.png",
            ],
            cost: `$${gig.price}-${gig.price * 10}`,
            duration: `${Math.max(gig.deliveryDays, 1)}-${Math.max(gig.deliveryDays * 6, 7)} days`,
        },
        reviewsData: {
            ...ahmadProfile.reviewsData,
            count: gig.reviews,
            rating: gig.rating,
            sample: {
                ...ahmadProfile.reviewsData.sample,
                serviceTitle: gig.categoryLabel,
                serviceImage: gig.image,
            },
        },
    };
}
