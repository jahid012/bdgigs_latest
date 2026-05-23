export const websiteCategoryPage = {
    parentLabel: "Programming & Tech",
    title: "Website Development",
    description:
        "Create, build, and develop your website with skilled website developers",
    resultLabel: "190,000+ results",
    chips: [
        {
            label: "WordPress",
            optionId: "wordpress",
            icon: "https://cdn.simpleicons.org/wordpress/21759B",
        },
        {
            label: "Custom Websites",
            optionId: "custom-websites",
            icon: "https://cdn.simpleicons.org/html5/E34F26",
        },
        {
            label: "Shopify",
            optionId: "shopify",
            icon: "https://cdn.simpleicons.org/shopify/7AB55C",
        },
        {
            label: "Wix",
            optionId: "wix",
            icon: "https://cdn.simpleicons.org/wix/111111",
        },
        {
            label: "Webflow",
            optionId: "webflow",
            icon: "https://cdn.simpleicons.org/webflow/4353FF",
        },
        {
            label: "Squarespace",
            optionId: "squarespace",
            icon: "https://cdn.simpleicons.org/squarespace/111111",
        },
        {
            label: "WooCommerce",
            optionId: "woocommerce",
            icon: "https://cdn.simpleicons.org/woocommerce/96588A",
        },
    ],
};

export const listingSortOptions = [
    { id: "relevance", label: "Relevance" },
    { id: "best-selling", label: "Best selling" },
    { id: "rating", label: "Highest rated" },
    { id: "price-low", label: "Price low to high" },
    { id: "fastest", label: "Fastest delivery" },
];

export const deliveryOptions = [
    { id: "1", label: "Express 24H", maxDays: 1, count: 120 },
    { id: "3", label: "Up to 3 days", maxDays: 3, count: 240 },
    { id: "7", label: "Up to 7 days", maxDays: 7, count: 500 },
    { id: "anytime", label: "Anytime", maxDays: 99, count: 900 },
];

export const listingFilterGroups = {
    category: [
        {
            id: "all-categories",
            label: "All Categories",
            count: null,
        },
        {
            id: "web-application-development",
            label: "Web Application Development",
            count: 100,
        },
        {
            id: "script-development",
            label: "Script Development",
            count: 50,
        },
        {
            id: "cross-platform-mobile-app-development",
            label: "Cross-Platform Mobile App Development",
            count: 20,
        },
        {
            id: "mobile-app-customization",
            label: "Mobile App Customization",
            count: 100,
        },
        {
            id: "website-installation",
            label: "Website Installation",
            count: 30,
        },
        {
            id: "android-app-development",
            label: "Android App Development",
            count: 20,
        },
        {
            id: "custom-websites-development",
            label: "Custom Websites Development",
            count: 20,
        },
        {
            id: "software-bug-fixes",
            label: "Software Bug Fixes",
            count: 10,
        },
        {
            id: "website-customization",
            label: "Website Customization",
            count: 9,
        },
        {
            id: "software-installation",
            label: "Software Installation",
            count: 9,
        },
    ],
    serviceOptions: [
        {
            title: "Programming language",
            options: [
                { id: "php", label: "PHP", count: 300 },
                { id: "dart", label: "Dart", count: 100 },
                { id: "java", label: "Java", count: 100 },
                { id: "kotlin", label: "Kotlin", count: 100 },
            ],
            more: "+14 more",
        },
        {
            title: "Service offerings",
            options: [
                {
                    id: "subscriptions",
                    label: "Offers subscriptions",
                    count: 30,
                },
                {
                    id: "paid-video",
                    label: "Paid video consultations",
                    count: 50,
                },
            ],
        },
        {
            title: "Expertise",
            options: [
                { id: "performance", label: "Performance", count: 200 },
                { id: "design", label: "Design", count: 200 },
                { id: "security", label: "Security", count: 200 },
                { id: "localization", label: "Localization", count: 100 },
            ],
            more: "+6 more",
        },
        {
            title: "Frontend framework",
            options: [
                { id: "bootstrap", label: "Bootstrap", count: 100 },
                { id: "react", label: "React.js", count: 100 },
                { id: "jquery", label: "jQuery", count: 90 },
                { id: "tailwind", label: "Tailwind CSS", count: 80 },
            ],
            more: "+11 more",
        },
        {
            title: "Platform",
            options: [
                { id: "wordpress", label: "WordPress", count: 420 },
                { id: "shopify", label: "Shopify", count: 220 },
                { id: "wix", label: "Wix", count: 180 },
                { id: "custom-websites", label: "Custom Websites", count: 160 },
            ],
        },
    ],
    sellerDetails: [
        {
            title: "Seller level",
            options: [
                { id: "top-rated", label: "Top Rated Seller", count: 10 },
                { id: "level-2", label: "Level 2", count: 100 },
                { id: "level-1", label: "Level 1", count: 100 },
                { id: "new-seller", label: "New Seller", count: 300 },
            ],
        },
        {
            title: "Hourly rate",
            options: [
                {
                    id: "hourly",
                    label: "Hourly rate",
                    count: 200,
                    badge: "New",
                    hint: "Hire hourly for long-term projects",
                },
            ],
        },
        {
            title: "Seller type",
            options: [
                {
                    id: "agency",
                    label: "Agency",
                    count: 1,
                    hint: "Find an agency that can handle the scope of your project.",
                },
            ],
        },
        {
            title: "Seller availability",
            options: [{ id: "online", label: "Online Now", count: 40 }],
        },
        {
            title: "Seller speaks",
            options: [
                { id: "english", label: "English", count: 500 },
                { id: "urdu", label: "Urdu", count: 100 },
                { id: "hindi", label: "Hindi", count: 100 },
                { id: "bengali", label: "Bengali", count: 80 },
            ],
        },
    ],
};
