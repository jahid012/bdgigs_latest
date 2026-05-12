import { BrandMark, Icon } from "../common/Icons.jsx";

const footerColumns = [
  {
    title: "Categories",
    links: ["Graphics & Design", "Programming & Tech", "Digital Marketing", "AI Services"],
  },
  {
    title: "Support",
    links: ["Help Center", "Trust & Safety", "Order Protection", "Contact"],
  },
  {
    title: "Company",
    links: ["About", "Careers", "Become a Seller", "Terms"],
  },
];

function Footer() {
  return (
    <footer className="site-footer">
      <div className="container">
        <div className="footer-grid">
          <div className="footer-brand">
            <a className="brand" href="/" aria-label="BDGigs home">
              <BrandMark />
              BDGigs
            </a>
            <p>
              A premium freelance marketplace for hiring creative, technical, and marketing experts with clarity and
              confidence.
            </p>
            <div className="social-links" aria-label="Social links">
              {["message", "user", "spark"].map((icon) => (
                <a key={icon} href="#" aria-label={`BDGigs ${icon}`}>
                  <Icon name={icon} />
                </a>
              ))}
            </div>
          </div>

          {footerColumns.map((column) => (
            <div className="footer-column" key={column.title}>
              <h3>{column.title}</h3>
              {column.links.map((link) => (
                <a href="#" key={link}>
                  {link}
                </a>
              ))}
            </div>
          ))}
        </div>

        <div className="footer-bottom">
          <span>© 2026 BDGigs. All rights reserved.</span>
          <span>Built for modern freelance teams.</span>
        </div>
      </div>
    </footer>
  );
}

export default Footer;
