import { services as homeServices } from "./homeData.js";
import { listingGigs } from "./gigListingData.js";

export const aiGigDetailId = "ai-website-chatbot";

export const aiGigDetail = {
    id: aiGigDetailId,
    title: "I will develop ai website, ai chatbot, ai web application and ai software",
    breadcrumbs: [
        "Programming & Tech",
        "AI Development",
        "AI Websites & Software",
    ],
    relatedTags: [
        "Ai chatbot",
        "Ai developer",
        "Full stack website",
        "Ai website",
        "Ai software",
    ],
    seller: {
        name: "Wiznic Solution",
        tagline: "Bringing Your Ideas to Life with AI Brilliance!",
        level: "Level 2",
        rating: 4.8,
        reviews: 36,
        avatar: "https://images.pexels.com/photos/2379004/pexels-photo-2379004.jpeg?auto=compress&cs=tinysrgb&w=120",
        from: "Pakistan",
        memberSince: "Aug 2020",
        responseTime: "1 hour",
        lastDelivery: "4 months",
        languages: "Arabic, German, Spanish, English",
        bio: "At Wiznic Solution, we specialize in AI-powered development, chatbot integration, and custom web/app solutions. From GPT-based assistants to automation tools and full-stack websites, we deliver smart, scalable systems tailored to your business.",
    },
    gallery: [
        "/assets/img/gig_images/1.png",
        "/assets/img/gig_images/20.png",
        "/assets/img/gig_images/21.png",
        "/assets/img/gig_images/11.png",
        "/assets/img/gig_images/12.png",
        "/assets/img/gig_images/16.png",
    ],
    packages: [
        {
            id: "basic",
            name: "Basic",
            title: "Starter AI Website",
            price: 150,
            delivery: "4-day delivery",
            revisions: "Unlimited Revisions",
            description:
                "Develop a simple AI website with Basic, smooth functionality & User Authentication.",
            features: {
                "Functional Web App": true,
                "Desktop Application": false,
                "Integration of an AI model to existing app": true,
                "AI Model Fine-tuning": false,
                "Chatbot integration": false,
                "Source Code": true,
            },
            deliveryTime: "4 days",
        },
        {
            id: "standard",
            name: "Standard",
            title: "Enhanced AI Website Solutions",
            price: 500,
            delivery: "10-day delivery",
            revisions: "Unlimited Revisions",
            description:
                "Develop an Advanced AI web or App with your specific features tailored to your needs.",
            features: {
                "Functional Web App": true,
                "Desktop Application": false,
                "Integration of an AI model to existing app": true,
                "AI Model Fine-tuning": true,
                "Chatbot integration": false,
                "Source Code": true,
            },
            deliveryTime: "10 days",
        },
        {
            id: "premium",
            name: "Premium",
            title: "Premium AI Website Solutions",
            price: 1000,
            delivery: "21-day delivery",
            revisions: "Unlimited Revisions",
            description:
                "Develop premium, complete AI-powered app or web app with Database Storing, Fine-tuning + Deployment.",
            features: {
                "Functional Web App": true,
                "Desktop Application": true,
                "Integration of an AI model to existing app": true,
                "AI Model Fine-tuning": true,
                "Chatbot integration": true,
                "Source Code": true,
            },
            deliveryTime: "21 days",
        },
    ],
    about: {
        heading:
            "Transform Your AI Vision into a Powerful, High Performing Website or Software",
        paragraphs: [
            "Looking to build an AI powered website or software that actually delivers results and not just looks good? You are in the right place.",
            "We are professional AI developers specializing in intelligent, scalable, and fully customized digital solutions. From AI websites and chatbots to full stack SaaS platforms, we build systems that are practical, responsive, and ready for growth.",
        ],
        bullets: [
            { label: "Frontend", text: "React, Next.js" },
            { label: "Backend", text: "Node.js, Django, Flask, Python" },
            { label: "Databases", text: "MongoDB, MySQL, PostgreSQL" },
        ],
        why: [
            "Proven experience in AI & full-stack development",
            "Clean, scalable, and maintainable code",
            "Fully responsive across all devices",
        ],
        closing: "Send me a message now and lets get started!!!",
    },
    specs: [
        { label: "Application Type", value: "Web Application" },
        {
            label: "Desktop Frameworks",
            value: "Windows Presentation Foundation (WPF), React Native for Web, Flutter for Desktop",
        },
        {
            label: "AI Type",
            value: "Chat, Shopping, Booking, Restaurant, Health & Fitness, Education, Social Networking, Ecommerce, Custom, Real Estate",
        },
        {
            label: "Programming Language",
            value: "Dart, Java, JavaScript, Python, TypeScript, React",
        },
        {
            label: "Web Frameworks",
            value: "React, Angular, Vue.js, Express.js (Node.js), Django, Flask, Ruby on Rails, Next.js",
        },
        {
            label: "No & Low-Code Builders",
            value: "Lovable, FlutterFlow, Webflow",
        },
    ],
    portfolio: {
        title: "DO5 Steel Estimator Web Platform",
        date: "From: March 2025",
        description:
            "DO5 Estimator - AI-Powered Steel Fabrication Estimation Tool Description:",
        image: "/assets/img/gig_images/21.png",
        tags: ["Edilizia", "+12"],
        cost: "$1000-$2500",
        duration: "7-30 days",
        thumbnails: [
            "/assets/img/gig_images/21.png",
            "/assets/img/gig_images/20.png",
            "/assets/img/gig_images/11.png",
            "/assets/img/gig_images/12.png",
            "/assets/img/gig_images/13.png",
        ],
    },
    faq: [
        {
            question: "What's the process to get started?",
            answer: "Share your idea, required features, timeline, and any references. I will map the scope and recommend the right package.",
        },
        {
            question: "What is your development process?",
            answer: "I start with requirements, create a clear technical plan, build in milestones, test the app, and then deliver with support.",
        },
        {
            question:
                "Can you integrate AI features into my existing app or website?",
            answer: "Yes. I can integrate chatbots, AI assistants, recommendation flows, automation, and model connections into existing products.",
        },
        {
            question: "How do you ensure data privacy and security?",
            answer: "I follow secure coding practices, use protected keys, validate input, and can align the build with your preferred hosting and privacy requirements.",
        },
        {
            question:
                "How do you ensure client satisfaction for completed work?",
            answer: "I keep scope transparent, provide updates, include revisions, and hand over source code when included in the package.",
        },
    ],
    reviews: {
        count: 36,
        rating: 4.8,
        breakdown: [
            { label: "5 Stars", count: 33, value: 92 },
            { label: "4 Stars", count: 2, value: 22 },
            { label: "3 Stars", count: 0, value: 0 },
            { label: "2 Stars", count: 1, value: 7 },
            { label: "1 Star", count: 0, value: 0 },
        ],
        ratings: [
            { label: "Seller communication level", value: 4.8 },
            { label: "Quality of delivery", value: 4.9 },
            { label: "Value of delivery", value: 4.8 },
        ],
        sample: {
            name: "droneclipsus",
            country: "United States",
            rating: 5,
            date: "4 months ago",
            text: "Great work. They delivered exactly what I was looking for. Thanks a bunch!",
            price: "$50-$100",
            duration: "2 days",
            image: "/assets/img/gig_images/20.png",
        },
    },
};

export function getGigDetail(gigId) {
    if (!gigId || gigId === aiGigDetailId || gigId === "brand-identity") {
        return aiGigDetail;
    }

    const gig = listingGigs.find((item) => item.id === gigId);
    if (gig) {
        return createDetailFromListingGig(gig);
    }

    const homeGig = homeServices.find((item) => item.id === gigId);
    if (homeGig) {
        return createDetailFromHomeGig(homeGig);
    }

    return aiGigDetail;
}

export function getRecommendedGigs(currentId) {
    return listingGigs.filter((gig) => gig.id !== currentId).slice(0, 2);
}

export function createDetailFromListingGig(gig) {
    return {
        ...aiGigDetail,
        id: gig.id,
        title: gig.title,
        breadcrumbs: [
            "Programming & Tech",
            gig.categoryLabel,
            "Freelance Services",
        ],
        seller: {
            ...aiGigDetail.seller,
            name: gig.seller,
            level: gig.level,
            rating: gig.rating,
            reviews: gig.reviews,
            avatar: gig.avatar,
            userId: gig.sellerUserId,
        },
        gallery: [
            gig.image,
            ...aiGigDetail.gallery.filter((image) => image !== gig.image),
        ].slice(0, 6),
        packages: aiGigDetail.packages.map((pkg, index) => ({
            ...pkg,
            price: Math.max(gig.price * [1, 4, 8][index], gig.price),
        })),
    };
}

function createDetailFromHomeGig(gig) {
    return {
        ...aiGigDetail,
        id: gig.id,
        title: gig.title,
        seller: {
            ...aiGigDetail.seller,
            name: gig.seller,
            level: gig.level,
            rating: Number(gig.rating),
            reviews: Number(gig.reviews),
        },
        gallery: [
            gig.image,
            ...aiGigDetail.gallery.filter((image) => image !== gig.image),
        ].slice(0, 6),
    };
}
