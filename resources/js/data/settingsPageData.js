export const settingsProfiles = {
    buyer: {
        name: "Jahid Hasan",
        email: "j********d@g***l.com",
        visibility: "Online",
        profilePage: "profile",
        profilePath: "/dashboard/profile",
        profileLabel: "Go to profile",
        notificationIntro:
            "Select the notifications you want and how you'd like to receive them. Essential account and order notifications stay enabled.",
        identityTitle: "Verify your information in just a few steps",
        identityDescription:
            "We verify your account information with a trusted security partner so protected payments and order activity stay safe.",
        identityCardTitle: "Why is verification important?",
        identityCardText:
            "bdgigs uses verification to protect buyers, sellers, and payment workflows across the marketplace.",
    },
    seller: {
        name: "Jahid Hasan",
        email: "j********d@g***l.com",
        visibility: "Seller profile online",
        profilePage: "seller-profile",
        profilePath: "/dashboard/seller/profile",
        profileLabel: "Go to seller profile",
        notificationIntro:
            "Choose how you receive buyer, order, payout, and gig performance notifications. Critical account notifications remain active.",
        identityTitle: "Verify your seller information in just a few steps",
        identityDescription:
            "We verify your seller ID with a trusted security partner before expanding payouts, visibility, and marketplace trust signals.",
        identityCardTitle: "Why is seller verification important?",
        identityCardText:
            "bdgigs requires seller verification to build a trusted marketplace, prevent fraud, and support secure payouts.",
    },
};

export const settingsHubCards = [
    {
        id: "personal-information",
        icon: "user",
        title: "Personal Information",
        description:
            "Update your name, email address, online visibility, and account status.",
    },
    {
        id: "account-security",
        icon: "settings",
        title: "Account security",
        description:
            "Update your password and manage additional security settings.",
    },
    {
        id: "notifications",
        icon: "bell",
        title: "Notifications",
        description:
            "Select the notifications you want and how you'd like to receive them.",
    },
    {
        id: "identity-verification",
        icon: "verifiedUser",
        title: "Identity verification",
        description: "Help bdgigs maintain a safe and trustworthy marketplace.",
    },
];

export const settingsPageTitles = settingsHubCards.reduce((titles, card) => {
    titles[card.id] = card.title;
    return titles;
}, {});

export const personalInfoRows = [
    { label: "Full name", field: "name", action: "Edit" },
    { label: "Email address", field: "email", action: "Edit" },
    { label: "Visibility", field: "visibility", action: "Edit" },
    {
        label: "Deactivate account",
        value: "",
        action: "Deactivate",
        danger: true,
    },
];

export const notificationRows = {
    buyer: [
        {
            id: "inboxMessages",
            label: "Inbox messages",
            email: true,
            push: true,
        },
        {
            id: "orderMessages",
            label: "Order messages",
            email: true,
            push: true,
        },
        { id: "orderUpdates", label: "Order updates", email: true, push: true },
        { id: "customOffers", label: "Custom offers", email: true, push: true },
        { id: "payments", label: "Payments and wallet", email: true, push: true },
        { id: "disputes", label: "Resolution Center", email: true, push: true },
        {
            id: "ratingReminders",
            label: "Rating reminders",
            email: true,
            push: true,
        },
        { id: "buyerBriefs", label: "Buyer briefs", email: true, push: true },
        { id: "marketing", label: "Marketing and digest", email: false, push: false },
        {
            id: "accountUpdates",
            label: "Account updates",
            email: false,
            push: true,
        },
        {
            id: "savedServices",
            label: "Saved service updates",
            email: false,
            push: true,
        },
        { id: "other", label: "Other", email: true, push: false },
    ],
    seller: [
        {
            id: "inboxMessages",
            label: "Inbox messages",
            email: true,
            push: true,
        },
        {
            id: "orderMessages",
            label: "Order messages",
            email: true,
            push: true,
        },
        { id: "orderUpdates", label: "Order updates", email: true, push: true },
        { id: "customOffers", label: "Custom offers", email: true, push: true },
        { id: "payments", label: "Payments and wallet", email: true, push: true },
        { id: "disputes", label: "Resolution Center", email: true, push: true },
        {
            id: "ratingReminders",
            label: "Rating reminders",
            email: true,
            push: true,
        },
        { id: "buyerBriefs", label: "Buyer briefs", email: true, push: true },
        { id: "marketing", label: "Marketing and digest", email: false, push: false },
        {
            id: "accountUpdates",
            label: "Account updates",
            email: false,
            push: true,
        },
        { id: "gigUpdates", label: "Gig updates", email: false, push: true },
        {
            id: "payouts",
            label: "Payout and tax updates",
            email: true,
            push: false,
        },
    ],
};

export const securityRows = [
    {
        title: "Phone verification",
        description:
            "Your phone is verified with bdgigs. Edit it when your number changes.",
        action: "Edit",
    },
    {
        title: "Security question",
        description:
            "Add another protection layer for withdrawals, password changes, and sensitive account updates.",
        action: "Edit",
    },
];

export const connectedDevice = {
    title: "Chrome 146.0.0.0, Windows",
    status: "This device",
    detail: "Last activity 2 minutes ago - Bangladesh",
};
