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
    title: "I will customize your codecanyon laravel script to your needs",
    category: "Programming & Tech",
    subcategory: "Other",
    tags: [
        "Laravel",
        "PHP Scripts",
        "Modify Website",
        "Codecanyon Script",
        "Customization",
    ],
    packages: [
        {
            id: "basic",
            label: "Basic",
            name: "Basic",
            description: "I will do some basic customization",
            delivery: "1 Day Delivery",
            revisions: "Unlimited",
            price: "5",
        },
        {
            id: "standard",
            label: "Standard",
            name: "Standard",
            description:
                "I will remove some functionality and do some basic modification as your requirements",
            delivery: "3 Days Delivery",
            revisions: "Unlimited",
            price: "50",
        },
        {
            id: "premium",
            label: "Premium",
            name: "Premium",
            description:
                "I will modify the features as your requirements",
            delivery: "14 Days Delivery",
            revisions: "Unlimited",
            price: "200",
        },
    ],
    extras: [
        {
            id: "fast-delivery",
            label: "Extra fast delivery",
            enabled: true,
            rows: [
                { packageId: "basic", delivery: "Select", price: "" },
                { packageId: "standard", delivery: "2 Days", price: "10" },
                { packageId: "premium", delivery: "10 Days", price: "50" },
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
    description:
        "Service Include:\n- Custom feature integration\n- UI/UX adjustments\n- Database modifications\n- Bug fixes and enhancements\n\nLet's collaborate to transform your Codecanyon script into a personalized, high-functioning solution for you. Drop me a message to discuss your project specifics and let's get started on creating something exceptional together!",
    faqs: [
        {
            id: "faq-1",
            question: "Can you work with any type of Codecanyon script?",
            answer: "Yes. I can review most PHP and Laravel based Codecanyon scripts before confirming the final scope.",
        },
        {
            id: "faq-2",
            question: "What modifications can you make to Laravel scripts?",
            answer: "I can adjust features, fix bugs, update layouts, connect APIs, and customize the admin or user experience.",
        },
        {
            id: "faq-3",
            question:
                "How do you ensure the customized script meets my requirements?",
            answer: "I confirm the requirements first, share progress updates, and test the changes against your agreed scope.",
        },
        {
            id: "faq-4",
            question: "What's your turnaround time for the customizations?",
            answer: "Simple changes can often be delivered within a few days. Larger changes depend on the final scope.",
        },
        {
            id: "faq-5",
            question: "Can you integrate new features into the existing scripts?",
            answer: "Yes. I can add new features when the script architecture supports the requested workflow.",
        },
        {
            id: "faq-6",
            question: "Do you offer post-customization support?",
            answer: "Yes. Support can be included in the package or added as an extra service.",
        },
        {
            id: "faq-7",
            question: "How do we initiate the customization process?",
            answer: "Send the script details, access notes, and a clear list of required changes before ordering.",
        },
    ],
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
    requirements: [
        {
            id: "requirement-1",
            type: "Multiple choice",
            question:
                "Please make sure you have contacted me in my inbox before placing an order.",
            detail: "Yes, I contacted, No. I don't need to contact. It's a simple project and I have the clear requirements.",
            required: true,
            options: [
                "Yes, I contacted",
                "No. I don't need to contact",
                "It's a simple project and I have the clear requirements",
            ],
            allowMultiple: false,
        },
        {
            id: "requirement-2",
            type: "Attachment",
            question: "If you have any requirements Please attach them here.",
            detail: "",
            required: false,
            options: [],
            allowMultiple: false,
        },
        {
            id: "requirement-3",
            type: "Free text",
            question:
                "Please share your cpanel (URL, Username, Password) and admin panel access (URL, username and password). Please make sure you have disabled the two-factor authentication.",
            detail: "",
            required: true,
            options: [],
            allowMultiple: false,
        },
    ],
    galleryImages: [
        "/assets/img/gig_images/1.png",
        "/assets/img/gig_images/2.png",
        "/assets/img/gig_images/3.png",
    ],
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
