import FeaturedServices from "../components/home/FeaturedServices.jsx";
import Hero from "../components/home/Hero.jsx";
import HowItWorks from "../components/home/HowItWorks.jsx";
import PopularCategories from "../components/home/PopularCategories.jsx";
import Testimonials from "../components/home/Testimonials.jsx";
import Footer from "../components/layout/Footer.jsx";
import Header from "../components/layout/Header.jsx";

function HomePage({ onNavigate }) {
  return (
    <div className="home-page">
      <a className="skip-link" href="#main">
        Skip to content
      </a>
      <Header onNavigate={onNavigate} />
      <main id="main">
        <Hero onNavigate={onNavigate} />
        <PopularCategories />
        <FeaturedServices />
        <HowItWorks />
        <Testimonials />
      </main>
      <Footer />
    </div>
  );
}

export default HomePage;
