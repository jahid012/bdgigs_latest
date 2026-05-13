import { marketplaceCategories } from "../../data/homeData.js";
import { Icon } from "../common/Icons.jsx";

function PopularCategories() {
  return (
    <section className="categories-section" id="categories" aria-label="Popular categories">
      <div className="container">
        <div className="category-strip">
          {marketplaceCategories.map((category) => (
            <a className="category-card" href="#services" aria-label={`Explore ${category.title}`} key={category.title}>
              <Icon name={category.icon} />
              <span>{category.title}</span>
            </a>
          ))}
        </div>
      </div>
    </section>
  );
}

export default PopularCategories;
