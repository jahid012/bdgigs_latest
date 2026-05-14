export const dashboardDetailCopy = {
    buyer: {
        savedServices: {
            eyebrow: "Buyer shortlist",
            title: "Saved Services",
            titleId: "savedServicesTitle",
            description:
                "Compare your shortlisted services, keep the best sellers close, and move faster when a project is ready.",
            stats: [
                { value: "19", label: "Saved" },
                { value: "5", label: "New matches" },
                { value: "4.9", label: "Avg rating" },
            ],
            actionLabel: "Explore Services",
            actionPage: "home",
            actionHash: "#services",
            mode: "services",
            kicker: "Shortlisted talent",
            heading: "Services ready to compare",
        },
        profile: {
            eyebrow: "Buyer identity",
            title: "Profile",
            titleId: "buyerProfileTitle",
            description:
                "Make your buyer profile clearer so freelancers understand your company, project style, and collaboration preferences.",
            stats: [
                { value: "92%", label: "Complete" },
                { value: "14", label: "Hires" },
                { value: "4.9", label: "Buyer rating" },
            ],
            actionLabel: "Edit Profile",
            mode: "profile",
        },
        settings: {
            eyebrow: "Workspace control",
            title: "Settings",
            titleId: "buyerSettingsTitle",
            description:
                "Tune notifications, privacy, saved payment behavior, and marketplace preferences for a cleaner buying workflow.",
            stats: [
                { value: "6", label: "Enabled" },
                { value: "2FA", label: "Secure" },
                { value: "Daily", label: "Digest" },
            ],
            actionLabel: "Save Changes",
            mode: "settings",
        },
    },
    seller: {
        services: {
            eyebrow: "Gig studio",
            title: "My Services",
            titleId: "sellerServicesTitle",
            description:
                "Manage live gigs, pricing signals, delivery timing, and optimization opportunities across your seller catalog.",
            stats: [
                { value: "4", label: "Live gigs" },
                { value: "101", label: "Active orders" },
                { value: "15%", label: "Avg conversion" },
            ],
            actionLabel: "Create New Gig",
            mode: "services",
            kicker: "Service catalog",
            heading: "Active seller services",
        },
        profile: {
            eyebrow: "Seller trust",
            title: "Seller Profile",
            titleId: "sellerProfileTitle",
            description:
                "Sharpen your seller presence with clearer positioning, trust signals, response quality, and portfolio readiness.",
            stats: [
                { value: "98%", label: "Complete" },
                { value: "Level 2", label: "Status" },
                { value: "1h", label: "Response" },
            ],
            actionLabel: "Preview Profile",
            mode: "profile",
        },
        settings: {
            eyebrow: "Seller controls",
            title: "Settings",
            titleId: "sellerSettingsTitle",
            description:
                "Control gig availability, payout preferences, notification rhythm, and client communication rules.",
            stats: [
                { value: "Live", label: "Availability" },
                { value: "Weekly", label: "Payouts" },
                { value: "2FA", label: "Secure" },
            ],
            actionLabel: "Save Changes",
            mode: "settings",
        },
    },
};

export const buyerSettings = [
    {
        title: "Order and delivery alerts",
        description:
            "Send milestone, delivery, and revision updates instantly.",
        enabled: true,
    },
    {
        title: "Saved service price changes",
        description:
            "Notify when a shortlisted service changes pricing or availability.",
        enabled: true,
    },
    {
        title: "Marketplace discovery",
        description: "Use recent orders to improve service recommendations.",
        enabled: true,
    },
    {
        title: "Weekly spending digest",
        description:
            "Bundle receipts, protected payments, and invoice notes each week.",
        enabled: false,
    },
];

export const sellerSettings = [
    {
        title: "Accept new orders",
        description:
            "Keep services discoverable and available for new buyer requests.",
        enabled: true,
    },
    {
        title: "Priority buyer messages",
        description: "Highlight urgent order threads and revision requests.",
        enabled: true,
    },
    {
        title: "Weekly payout schedule",
        description:
            "Move available earnings to your payout method every week.",
        enabled: true,
    },
    {
        title: "Promotion insights",
        description:
            "Receive visibility and conversion suggestions for active gigs.",
        enabled: false,
    },
];

export const buyerProfileDetails = [
    { label: "Name", value: "Jahid" },
    { label: "Company", value: "BDGigs Studio" },
    { label: "Primary need", value: "Marketplace growth" },
    { label: "Preferred budget", value: "$100 - $750" },
];

export const sellerProfileDetails = [
    { label: "Seller name", value: "Jahid" },
    { label: "Specialty", value: "Marketplace UI/UX" },
    { label: "Seller level", value: "Level 2" },
    { label: "Response time", value: "1 hour" },
];

export const sellerPayoutHistory = [
    {
        id: "FO428C81B90C8",
        date: "Dec 6, 2025",
        amount: "$16.00",
        status: "Earning",
        from: "mrvanninnocent",
        activity: "earning",
        description: "Order",
    },
    {
        id: "-",
        date: "May 30, 2025",
        amount: "-$56.00",
        status: "Withdrawal",
        from: "Payoneer",
        activity: "withdrawal",
        description: "Transferred successfully",
    },
    {
        id: "FO52691AA49C7",
        date: "Jan 3, 2025",
        amount: "$12.00",
        status: "Earning",
        from: "rjblancos",
        activity: "earning",
        description: "Tip",
    },
];

export const billingTabs = [
    { id: "history", label: "Billing history" },
    { id: "info", label: "Billing info" },
    { id: "balances", label: "Balances" },
    { id: "methods", label: "Payment methods" },
];

export const earningTabs = [
    { id: "overview", label: "Overview" },
    { id: "documents", label: "Financial documents" },
];
