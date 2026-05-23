export const gigEditorSteps = [
    { id: "overview", label: "Overview" },
    { id: "pricing", label: "Pricing" },
    { id: "description", label: "Description & FAQ" },
    { id: "requirements", label: "Requirements" },
    { id: "gallery", label: "Gallery" },
];

export const gigEditorCategories = [
    "Programming & Tech",
    "UI/UX Design",
    "Product Design",
    "Brand Design",
    "Growth Design",
    "Graphics & Design",
    "Digital Marketing",
    "Writing & Translation",
    "Video & Animation",
    "Business",
];

export const gigEditorSubcategories = [
    "Other",
    "Website Customization",
    "Laravel",
    "PHP Scripts",
    "Marketplace Development",
];

export const gigEditorDraft = {
    title: "",
    category: "Programming & Tech",
    subcategory: "",
    tags: [],
    packages: [
        {
            id: "basic",
            label: "Basic",
            name: "Basic",
            description: "",
            delivery: "3 Days Delivery",
            revisions: "1 Revision",
            price: "",
        },
        {
            id: "standard",
            label: "Standard",
            name: "Standard",
            description: "",
            delivery: "5 Days Delivery",
            revisions: "2 Revisions",
            price: "",
        },
        {
            id: "premium",
            label: "Premium",
            name: "Premium",
            description: "",
            delivery: "7 Days Delivery",
            revisions: "3 Revisions",
            price: "",
        },
    ],
    extras: [
        {
            id: "fast-delivery",
            label: "Extra fast delivery",
            enabled: false,
            rows: [
                { packageId: "basic", delivery: "Select", price: "" },
                { packageId: "standard", delivery: "Select", price: "" },
                { packageId: "premium", delivery: "Select", price: "" },
            ],
        },
        {
            id: "additional-revision",
            label: "Additional revision",
            enabled: false,
            rows: [
                { packageId: "basic", delivery: "Select", price: "" },
                { packageId: "standard", delivery: "Select", price: "" },
                { packageId: "premium", delivery: "Select", price: "" },
            ],
        },
    ],
    description: "",
    faqs: [],
    platformQuestions: [
        {
            id: "platform-1",
            type: "Multiple choice",
            question:
                "If you're ordering for a business, what's your industry?",
            detail: "3D design, e-commerce, accounting, marketing, etc.",
        },
        {
            id: "platform-2",
            type: "Multiple choice",
            question:
                "Is this order part of a bigger project you're working on?",
            detail: "Building a mobile app, creating an animation, developing a game, etc.",
        },
    ],
    requirements: [],
    galleryImages: [],
    media: [],
};

export const requirementTypeOptions = [
    "Free text",
    "Multiple choice",
    "Attachment",
];

export const deliveryOptions = [
    "Select",
    "1 Day",
    "2 Days",
    "3 Days",
    "5 Days",
    "7 Days",
    "10 Days",
    "14 Days",
];

export const revisionOptions = [
    "Unlimited",
    "1 Revision",
    "2 Revisions",
    "3 Revisions",
    "5 Revisions",
];
