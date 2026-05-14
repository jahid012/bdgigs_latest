import { marketplaceCategories } from "../../data/homeData.js";
import { Icon } from "../common/Icons.jsx";

const categoryRoutes = {
  "Programming & Tech": "/categories/programming-tech/website-development",
  "Graphics & Design": "/categories/graphics-design/logo-design",
  "Digital Marketing": "/categories/digital-marketing/seo",
  "Writing & Translation": "/categories/writing-translation/articles-blog-posts",
  "Video & Animation": "/categories/video-animation/video-editing",
  "AI Services": "/categories/ai-services/ai-applications",
  "Music & Audio": "/categories/music-audio/music-production",
  Business: "/categories/business/business-consulting",
  Consulting: "/categories/business/business-consulting",
};

function PopularCategories({ onNavigate }) {
  return (
    <section className="categories-section" id="categories" aria-label="Popular categories">
      <div className="container">
        <div className="category-strip">
          {marketplaceCategories.map((category) => {
            const path = categoryRoutes[category.title] || "/search/gigs?source=category";

            return (
              <a
                className="category-card"
                href={path}
                aria-label={`Explore ${category.title}`}
                key={category.title}
                onClick={(event) => {
                  event.preventDefault();
                  onNavigate(path);
                }}
              >
                <Icon name={category.icon} />
                <span>{category.title}</span>
              </a>
            );
          })}
        </div>
      </div>
    </section>
  );
}

export default PopularCategories;
