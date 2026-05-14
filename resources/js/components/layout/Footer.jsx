import { Link } from "react-router-dom";

const footerColumns = [
  {
    title: "Categories",
    links: [
      { label: "Graphics & Design", to: "/categories/graphics-design/logo-design" },
      { label: "Digital Marketing", to: "/categories/digital-marketing/seo" },
      { label: "Writing & Translation", to: "/categories/writing-translation/articles-blog-posts" },
      { label: "Video & Animation", to: "/categories/video-animation/video-editing" },
      { label: "Music & Audio", to: "/categories/music-audio/music-production" },
      { label: "Programming & Tech", to: "/categories/programming-tech/website-development" },
      { label: "AI Services", to: "/categories/ai-services/ai-applications" },
      { label: "Consulting", to: "/categories/business/business-consulting" },
      { label: "Data", to: "/search/gigs?query=data&source=footer" },
      { label: "Business", to: "/categories/business/business-consulting" },
      { label: "Personal Growth & Hobbies", to: "/search/gigs?query=personal%20growth&source=footer" },
      { label: "Photography", to: "/search/gigs?query=photography&source=footer" },
      { label: "Finance", to: "/categories/business/financial-consulting" },
      { label: "End-to-End Projects", to: "/search/gigs?query=end%20to%20end%20projects&source=footer" },
      { label: "Service Catalog", to: "/search/gigs?source=footer" },
    ],
  },
  {
    title: "For Clients",
    links: [
      { label: "How BDGigs Works", to: "/#how-it-works" },
      { label: "Customer Success Stories", to: "/search/gigs?query=success%20stories&source=footer" },
      { label: "Quality Guide", to: "/search/gigs?query=quality%20guide&source=footer" },
      { label: "BDGigs Guides", to: "/#guides" },
      { label: "BDGigs Answers", to: "/search/gigs?query=answers&source=footer" },
      { label: "Browse Freelance By Skill", to: "/search/gigs?source=footer" },
    ],
  },
  {
    title: "For Freelancers",
    links: [
      { label: "Become a BDGigs Freelancer", to: "/dashboard/seller" },
      { label: "Become an Agency", to: "/dashboard/seller/services" },
      { label: "Community Hub", to: "/search/gigs?query=community&source=footer" },
      { label: "Forum", to: "/search/gigs?query=forum&source=footer" },
      { label: "Events", to: "/search/gigs?query=events&source=footer" },
    ],
  },
  {
    title: "Business Solutions",
    links: [
      { label: "BDGigs Pro", to: "/search/gigs?query=pro&source=footer" },
      { label: "Project Management Service", to: "/search/gigs?query=project%20management&source=footer" },
      { label: "Expert Sourcing Service", to: "/search/gigs?query=expert%20sourcing&source=footer" },
      { label: "ClearVoice - Content Marketing", to: "/search/gigs?query=content%20marketing&source=footer" },
      { label: "AutoDS - Dropshipping Tool", to: "/search/gigs?query=dropshipping&source=footer" },
      { label: "BDGigs - Software Development", to: "/categories/programming-tech/website-development" },
      { label: "AI store builder", to: "/categories/ai-services/ai-applications" },
      { label: "BDGigs Logo Maker", to: "/categories/graphics-design/logo-design" },
      { label: "Contact Sales", to: "/search/gigs?query=sales&source=footer" },
    ],
  },
  {
    title: "Company",
    links: [
      { label: "About BDGigs", to: "/" },
      { label: "Help Center", to: "/search/gigs?query=help&source=footer" },
      { label: "Trust & Safety", to: "/search/gigs?query=trust%20safety&source=footer" },
      { label: "Social Impact", to: "/search/gigs?query=social%20impact&source=footer" },
      { label: "Careers", to: "/search/gigs?query=careers&source=footer" },
      { label: "Terms of Service", to: "/search/gigs?query=terms&source=footer" },
      { label: "Privacy Policy", to: "/search/gigs?query=privacy&source=footer" },
      { label: "Do not sell or share my personal information", to: "/search/gigs?query=privacy%20choices&source=footer" },
      { label: "Partnerships", to: "/search/gigs?query=partnerships&source=footer" },
      { label: "Creator Network", to: "/search/gigs?query=creator%20network&source=footer" },
      { label: "Affiliates", to: "/search/gigs?query=affiliates&source=footer" },
      { label: "Invite a Friend", to: "/search/gigs?query=invite&source=footer" },
      { label: "Press & News", to: "/search/gigs?query=news&source=footer" },
      { label: "Investor Relations", to: "/search/gigs?query=investor%20relations&source=footer" },
    ],
  },
];

function Footer() {
  return (
    <footer className="site-footer">
      <div className="container">
        <div className="footer-grid">
          {footerColumns.map((column) => (
            <div className="footer-column" key={column.title}>
              <h3>{column.title}</h3>
              {column.links.map((link) => (
                <Link to={link.to} key={link.label}>
                  {link.label}
                </Link>
              ))}
            </div>
          ))}
        </div>

        <div className="footer-bottom">
          <span>(c) 2026 BDGigs. All rights reserved.</span>
        </div>
      </div>
    </footer>
  );
}

export default Footer;
