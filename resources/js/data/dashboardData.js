export const sidebarItems = [
    { label: "Overview", icon: "dashboard", path: "/dashboard", end: true },
    { label: "Orders", icon: "orders", path: "/dashboard/orders" },
    { label: "Messages", icon: "message", path: "/dashboard/messages" },
    {
        label: "Saved Services",
        icon: "heart",
        path: "/dashboard/saved-services",
    },
    { label: "Payments", icon: "payment", path: "/dashboard/payments" },
    { label: "Profile", icon: "user", path: "/dashboard/profile" },
    { label: "Settings", icon: "settings", path: "/dashboard/settings" },
];

export const sellerSidebarItems = [
    {
        label: "Overview",
        icon: "dashboard",
        path: "/dashboard/seller",
        end: true,
    },
    { label: "Orders", icon: "orders", path: "/dashboard/seller/orders" },
    { label: "Messages", icon: "message", path: "/dashboard/seller/messages" },
    {
        label: "My Services",
        icon: "packageCheck",
        path: "/dashboard/seller/services",
    },
    { label: "Earnings", icon: "payment", path: "/dashboard/seller/earnings" },
    {
        label: "Seller Profile",
        icon: "user",
        path: "/dashboard/seller/profile",
    },
    { label: "Settings", icon: "settings", path: "/dashboard/seller/settings" },
];

export const stats = [
    {
        label: "Active Orders",
        value: "8",
        trend: "+2 this week",
        icon: "orders",
    },
    {
        label: "Completed Jobs",
        value: "42",
        trend: "96% success",
        icon: "packageCheck",
    },
    {
        label: "Total Spent",
        value: "$8.4k",
        trend: "On budget",
        icon: "payment",
    },
    { label: "Saved Services", value: "19", trend: "5 new", icon: "heart" },
];

export const sellerStats = [
    {
        label: "Active Orders",
        value: "14",
        trend: "+4 this week",
        icon: "orders",
    },
    {
        label: "Completed Jobs",
        value: "186",
        trend: "98% success",
        icon: "packageCheck",
    },
    {
        label: "Monthly Revenue",
        value: "$5.4k",
        trend: "+18% growth",
        icon: "payment",
    },
    {
        label: "Unread Messages",
        value: "9",
        trend: "3 priority",
        icon: "message",
    },
];

export const dashboardHighlights = [
    { label: "Response rate", value: "98%" },
    { label: "On-time orders", value: "94%" },
    { label: "Avg seller rating", value: "4.9" },
];

export const sellerDashboardHighlights = [
    { label: "Seller rating", value: "4.9" },
    { label: "Response rate", value: "99%" },
    { label: "On-time delivery", value: "96%" },
];

export const orders = [
    {
        id: "#SH-1048",
        service: "Landing page design",
        seller: "Nadia R.",
        status: "In Progress",
        statusClass: "status-progress",
        dueDate: "May 12, 2026",
        price: "$320",
    },
    {
        id: "#SH-1042",
        service: "SEO content audit",
        seller: "Ayesha K.",
        status: "Delivered",
        statusClass: "status-delivered",
        dueDate: "May 8, 2026",
        price: "$140",
    },
    {
        id: "#SH-1039",
        service: "AI workflow assistant",
        seller: "Daniel S.",
        status: "Completed",
        statusClass: "status-completed",
        dueDate: "Apr 29, 2026",
        price: "$580",
    },
    {
        id: "#SH-1031",
        service: "Product explainer video",
        seller: "Lina P.",
        status: "Cancelled",
        statusClass: "status-cancelled",
        dueDate: "Apr 25, 2026",
        price: "$210",
    },
];

export const sellerOrders = [
    {
        id: "#SH-2094",
        service: "Premium marketplace landing page",
        buyer: "CloudPeak Labs",
        status: "In Progress",
        statusClass: "status-progress",
        dueDate: "May 11, 2026",
        earnings: "$480",
    },
    {
        id: "#SH-2089",
        service: "Mobile app UI kit",
        buyer: "BrightCart",
        status: "Delivered",
        statusClass: "status-delivered",
        dueDate: "May 8, 2026",
        earnings: "$360",
    },
    {
        id: "#SH-2082",
        service: "SaaS dashboard redesign",
        buyer: "Northstar CRM",
        status: "Completed",
        statusClass: "status-completed",
        dueDate: "May 3, 2026",
        earnings: "$720",
    },
    {
        id: "#SH-2075",
        service: "Brand system starter pack",
        buyer: "LaunchNest",
        status: "Cancelled",
        statusClass: "status-cancelled",
        dueDate: "Apr 28, 2026",
        earnings: "$0",
    },
];

export const buyerOrderInsights = [
    {
        label: "Next delivery",
        value: "May 8",
        detail: "SEO audit from Ayesha K.",
    },
    {
        label: "In review",
        value: "2",
        detail: "Landing page and content audit",
    },
    {
        label: "Protected spend",
        value: "$1.2k",
        detail: "Held safely until approval",
    },
];

export const sellerOrderInsights = [
    {
        label: "Next milestone",
        value: "Today",
        detail: "CloudPeak wireframe review",
    },
    {
        label: "Pending revisions",
        value: "2",
        detail: "BrightCart and LaunchNest",
    },
    {
        label: "Ready to clear",
        value: "$1.5k",
        detail: "Projected from active orders",
    },
];

export const messages = [
    {
        initials: "NR",
        name: "Nadia R.",
        message: "I uploaded the revised hero section for review.",
        time: "12 min ago",
    },
    {
        initials: "AK",
        name: "Ayesha K.",
        message: "The SEO audit summary is ready in the order thread.",
        time: "1 hr ago",
    },
    {
        initials: "DS",
        name: "Daniel S.",
        message: "I can connect the assistant to your CRM next.",
        time: "Yesterday",
    },
];

export const sellerMessages = [
    {
        initials: "CP",
        name: "CloudPeak Labs",
        message: "Can we review the pricing block before the next milestone?",
        time: "8 min ago",
    },
    {
        initials: "BC",
        name: "BrightCart",
        message: "The delivered screens look great. I added two notes.",
        time: "42 min ago",
    },
    {
        initials: "NC",
        name: "Northstar CRM",
        message:
            "We approved the final files. Thank you for the clean handoff.",
        time: "Yesterday",
    },
];

export const buyerNotifications = [
    {
        title: "Delivery ready for review",
        detail: "Ayesha K. submitted the SEO audit summary.",
        time: "18 min ago",
        type: "Order update",
    },
    {
        title: "Milestone due soon",
        detail: "Landing page design is due on May 12.",
        time: "1 hr ago",
        type: "Reminder",
    },
    {
        title: "Saved service price changed",
        detail: "A WordPress expert lowered their starter package.",
        time: "Yesterday",
        type: "Marketplace",
    },
];

export const sellerNotifications = [
    {
        title: "New buyer note",
        detail: "CloudPeak Labs added feedback to the pricing block.",
        time: "8 min ago",
        type: "Needs reply",
    },
    {
        title: "Payout scheduled",
        detail: "$720 from Northstar CRM is moving to available balance.",
        time: "2 hr ago",
        type: "Earnings",
    },
    {
        title: "Gig performance rising",
        detail: "Your landing page gig gained 18% more clicks this week.",
        time: "Yesterday",
        type: "Growth",
    },
];

export const buyerMessageThreads = [
    {
        id: "buyer-thread-1",
        initials: "NR",
        name: "Nadia R.",
        role: "Landing Page Designer",
        service: "Landing page design",
        status: "Active order",
        statusClass: "status-progress",
        time: "12 min ago",
        unread: 2,
        priority: "Milestone review",
        preview: "I uploaded the revised hero section for review.",
        messages: [
            {
                from: "Nadia R.",
                text: "I uploaded the revised hero section for review.",
                time: "10:32 AM",
                own: false,
            },
            {
                from: "Jahid",
                text: "Great. I will check the mobile spacing and send notes today.",
                time: "10:38 AM",
                own: true,
            },
            {
                from: "Nadia R.",
                text: "Perfect. I also added a cleaner CTA state for the pricing block.",
                time: "10:41 AM",
                own: false,
            },
        ],
    },
    {
        id: "buyer-thread-2",
        initials: "AK",
        name: "Ayesha K.",
        role: "SEO Strategist",
        service: "SEO content audit",
        status: "Delivered",
        statusClass: "status-delivered",
        time: "1 hr ago",
        unread: 0,
        priority: "Awaiting approval",
        preview: "The SEO audit summary is ready in the order thread.",
        messages: [
            {
                from: "Ayesha K.",
                text: "The SEO audit summary is ready in the order thread.",
                time: "9:18 AM",
                own: false,
            },
            {
                from: "Jahid",
                text: "Thanks. I will review the keyword map and confirm the next batch.",
                time: "9:45 AM",
                own: true,
            },
        ],
    },
    {
        id: "buyer-thread-3",
        initials: "DS",
        name: "Daniel S.",
        role: "AI Automation Expert",
        service: "AI workflow assistant",
        status: "Completed",
        statusClass: "status-completed",
        time: "Yesterday",
        unread: 0,
        priority: "Follow-up idea",
        preview: "I can connect the assistant to your CRM next.",
        messages: [
            {
                from: "Daniel S.",
                text: "I can connect the assistant to your CRM next.",
                time: "Yesterday",
                own: false,
            },
            {
                from: "Jahid",
                text: "That sounds useful. Please send a scope for the integration.",
                time: "Yesterday",
                own: true,
            },
        ],
    },
    {
        id: "buyer-thread-4",
        initials: "LP",
        name: "Lina P.",
        role: "Motion Designer",
        service: "Product explainer video",
        status: "Cancelled",
        statusClass: "status-cancelled",
        time: "Apr 25",
        unread: 0,
        priority: "Closed thread",
        preview: "The project has been closed and payment was released back.",
        messages: [
            {
                from: "Lina P.",
                text: "The project has been closed and payment was released back.",
                time: "Apr 25",
                own: false,
            },
        ],
    },
];

export const sellerMessageThreads = [
    {
        id: "seller-thread-1",
        initials: "CP",
        name: "CloudPeak Labs",
        role: "Buyer",
        service: "Premium marketplace landing page",
        status: "In Progress",
        statusClass: "status-progress",
        time: "8 min ago",
        unread: 3,
        priority: "Needs reply",
        preview: "Can we review the pricing block before the next milestone?",
        messages: [
            {
                from: "CloudPeak Labs",
                text: "Can we review the pricing block before the next milestone?",
                time: "10:48 AM",
                own: false,
            },
            {
                from: "Jahid",
                text: "Yes. I can send an updated comparison layout in the next delivery.",
                time: "10:51 AM",
                own: true,
            },
            {
                from: "CloudPeak Labs",
                text: "Great. Please keep the annual plan most prominent.",
                time: "10:53 AM",
                own: false,
            },
        ],
    },
    {
        id: "seller-thread-2",
        initials: "BC",
        name: "BrightCart",
        role: "Buyer",
        service: "Mobile app UI kit",
        status: "Delivered",
        statusClass: "status-delivered",
        time: "42 min ago",
        unread: 1,
        priority: "Revision notes",
        preview: "The delivered screens look great. I added two notes.",
        messages: [
            {
                from: "BrightCart",
                text: "The delivered screens look great. I added two notes.",
                time: "10:12 AM",
                own: false,
            },
            {
                from: "Jahid",
                text: "Thanks. I will clean up the empty states and upload the updated file.",
                time: "10:22 AM",
                own: true,
            },
        ],
    },
    {
        id: "seller-thread-3",
        initials: "NC",
        name: "Northstar CRM",
        role: "Buyer",
        service: "SaaS dashboard redesign",
        status: "Completed",
        statusClass: "status-completed",
        time: "Yesterday",
        unread: 0,
        priority: "Approved",
        preview:
            "We approved the final files. Thank you for the clean handoff.",
        messages: [
            {
                from: "Northstar CRM",
                text: "We approved the final files. Thank you for the clean handoff.",
                time: "Yesterday",
                own: false,
            },
            {
                from: "Jahid",
                text: "Happy to help. I included the final style guide in the delivery folder.",
                time: "Yesterday",
                own: true,
            },
        ],
    },
    {
        id: "seller-thread-4",
        initials: "LN",
        name: "LaunchNest",
        role: "Buyer",
        service: "Brand system starter pack",
        status: "In Progress",
        statusClass: "status-progress",
        time: "May 6",
        unread: 0,
        priority: "Requirements pending",
        preview:
            "We are collecting the brand references and will share them soon.",
        messages: [
            {
                from: "LaunchNest",
                text: "We are collecting the brand references and will share them soon.",
                time: "May 6",
                own: false,
            },
        ],
    },
];

export const recommendedServices = [
    {
        title: "Conversion-ready SaaS landing page",
        seller: "Marco L.",
        rating: "5.0",
        price: "$220",
        image: "/assets/img/gig_images/2.png",
        tag: "Web Design",
        delivery: "5 days",
    },
    {
        title: "Full-funnel paid ads setup",
        seller: "Tara M.",
        rating: "4.8",
        price: "$175",
        image: "/assets/img/gig_images/3.png",
        tag: "Marketing",
        delivery: "3 days",
    },
    {
        title: "Pitch deck copy and visual polish",
        seller: "Elena V.",
        rating: "4.9",
        price: "$130",
        image: "/assets/img/gig_images/1.png",
        tag: "Brand Copy",
        delivery: "2 days",
    },
    {
        title: "WordPress speed and conversion cleanup",
        seller: "Omar H.",
        rating: "4.9",
        price: "$95",
        image: "/assets/img/gig_images/4.png",
        tag: "WordPress",
        delivery: "4 days",
    },
];

export const sellerServices = [
    {
        title: "Modern Website Landing Page Design",
        category: "UI/UX Design",
        rating: "4.9",
        price: "$75",
        image: "/assets/img/gig_images/6.png",
        tag: "Best Seller",
        delivery: "3 days",
        orders: "42 active",
        conversion: "18% conversion",
        status: "Live",
        statusClass: "status-completed",
    },
    {
        title: "Complete SaaS Dashboard UI Design",
        category: "Product Design",
        rating: "5.0",
        price: "$140",
        image: "/assets/img/gig_images/8.png",
        tag: "Trending",
        delivery: "5 days",
        orders: "26 active",
        conversion: "14% conversion",
        status: "Live",
        statusClass: "status-completed",
    },
    {
        title: "Premium Brand Identity Starter Pack",
        category: "Brand Design",
        rating: "4.8",
        price: "$95",
        image: "/assets/img/gig_images/11.png",
        tag: "New Leads",
        delivery: "4 days",
        orders: "18 active",
        conversion: "11% conversion",
        status: "Optimize",
        statusClass: "status-delivered",
    },
    {
        title: "AI Landing Page Conversion Audit",
        category: "Growth Design",
        rating: "4.9",
        price: "$110",
        image: "/assets/img/gig_images/13.png",
        tag: "High Intent",
        delivery: "2 days",
        orders: "15 active",
        conversion: "16% conversion",
        status: "Live",
        statusClass: "status-completed",
    },
];

export const chartData = [
    { label: "Nov", value: 42 },
    { label: "Dec", value: 65 },
    { label: "Jan", value: 52 },
    { label: "Feb", value: 78 },
    { label: "Mar", value: 58 },
    { label: "Apr", value: 86 },
    { label: "May", value: 70 },
];

export const sellerChartData = [
    { label: "Nov", value: 48 },
    { label: "Dec", value: 56 },
    { label: "Jan", value: 62 },
    { label: "Feb", value: 74 },
    { label: "Mar", value: 68 },
    { label: "Apr", value: 91 },
    { label: "May", value: 82 },
];

export const sellerPipeline = [
    {
        title: "CloudPeak landing page",
        detail: "Wireframe approval due today",
        progress: 72,
        due: "May 11",
    },
    {
        title: "BrightCart UI kit",
        detail: "Final revisions in review",
        progress: 88,
        due: "May 8",
    },
    {
        title: "LaunchNest brand system",
        detail: "Awaiting buyer requirements",
        progress: 34,
        due: "May 14",
    },
];
