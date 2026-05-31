import FeaturedServices from "../components/home/FeaturedServices.jsx";
import Hero from "../components/home/Hero.jsx";
import HowItWorks from "../components/home/HowItWorks.jsx";
import PopularCategories from "../components/home/PopularCategories.jsx";
import Testimonials from "../components/home/Testimonials.jsx";
import Footer from "../components/layout/Footer.jsx";
import Header from "../components/layout/Header.jsx";
import { useHomeBootstrap } from "../hooks/useHomeBootstrap.js";
import { useTranslation } from "react-i18next";
function HomePage({ onNavigate }) {
    const { t } = useTranslation();
    const { creatorMarketplace, featuredGigs, marketplaceCategories } =
        useHomeBootstrap();

    return (
        <div className="home-page">
            <a className="skip-link" href="#main">
                {" "}
                {t("pages.homepage.skipToContent")}{" "}
            </a>
            <Header
                fetchMarketplaceCategories={false}
                hydrateSessionOnMount={false}
                marketplaceCategories={marketplaceCategories}
                onNavigate={onNavigate}
            />
            <main id="main">
                <Hero onNavigate={onNavigate} />
                <PopularCategories onNavigate={onNavigate} />
                <FeaturedServices
                    onNavigate={onNavigate}
                    services={featuredGigs}
                />
                <HowItWorks
                    creatorItems={creatorMarketplace}
                    onNavigate={onNavigate}
                />
                <Testimonials onNavigate={onNavigate} />
            </main>
            <Footer />
        </div>
    );
}
export default HomePage;
