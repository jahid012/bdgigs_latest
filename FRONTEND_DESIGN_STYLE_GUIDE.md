# Frontend Design Style Guide

Use this guide when creating new templates or improving existing UI in this project. The target style is a clean Fiverr-inspired freelance marketplace: practical, commercial, trust-focused, image-rich, and easy to scan.

## Design Direction

- Build real product screens, not decorative landing pages.
- Prioritize marketplace usability: search, categories, seller trust, ratings, pricing, filters, reviews, and clear CTAs.
- Keep layouts dense but calm. Information should be easy to compare.
- Use real images, service thumbnails, profile photos, portfolio previews, and video-style cards whenever possible.
- Make each section feel functional. Avoid visible instructional text that explains the UI.

## Visual Feel

- Professional marketplace mixed with clean SaaS discipline.
- Mostly white surfaces with light grey page bands.
- Strong black text, muted grey metadata, and subtle green trust accents.
- Small radii, thin borders, restrained shadows.
- Design should feel polished, but not overly decorative.
- Avoid a boxed-up interface where every element has its own border, background, and shadow.
- Prefer open sections, whitespace, soft dividers, and shared grouping before adding another card.
- Use borders to clarify structure, not to decorate every block.

## Color System

Use these core colors:

```css
--color-primary: #1dbf73;
--color-primary-dark: #14975b;
--color-primary-soft: #e8fbf1;
--color-dark: #0f172a;
--color-text: #334155;
--color-muted: #64748b;
--color-bg: #f8fafc;
--color-surface: #ffffff;
--color-border: #e2e8f0;
```

Marketplace-specific neutrals:

```css
--market-black: #111111;
--market-ink: #222325;
--market-text: #404145;
--market-muted: #62646a;
--market-faint: #95979d;
--market-border: #dadbdd;
--market-soft: #f5f5f5;
```

Guidelines:

- Use `#ffffff` for main content surfaces.
- Use `#f5f5f5` for bottom discovery sections and background bands.
- Use `#222325` or `#111111` for primary CTAs.
- Use green sparingly for active states, online dots, success/trust signals, and brand accents.
- Avoid one-color themes and heavy gradients outside hero/CTA moments.

## Typography

- Font family: `Inter`, then system UI fonts.
- Keep letter spacing at `0`.
- Use compact, scannable type.

Recommended sizes:

```css
Hero headline: clamp(2.55rem, 10vw, 5.25rem)
Page title: 1.55rem to 1.9rem
Section heading: 1.2rem to 1.45rem
Card title: 0.88rem to 0.94rem
Body text: 0.86rem to 1rem
Metadata: 0.72rem to 0.86rem
```

Weight guidance:

- Headings: `800` or `900`
- Buttons and labels: `800`
- Card body/title text: `500` to `700`
- Metadata: `500` to `700`

## Layout System

- Use centered containers, usually `1120px` to `1200px`.
- Use full-width page bands for major sections.
- Avoid putting page sections inside large floating cards.
- Use cards only for repeated items, tools, modals, profile cards, package cards, service cards, and framed content.
- Do not nest cards inside cards unless it is a clear functional subcomponent.

Common layouts:

- Listing page: header, category/search heading, filter bar, gig grid, pagination, bottom discovery band.
- Gig details page: content column plus sticky package sidebar, then full-width bottom discovery area.
- Profile page: profile summary plus contact card, sticky profile nav, services, portfolio, work experience, CTA, reviews.
- Home page: hero search, category strip, service rows, promotional bands, guides, final CTA.

## Dashboard Pages

Dashboard screens should be cleaner and quieter than public marketplace pages. They are work surfaces, not discovery pages.

- Use one compact page header, one stats row, then the main working cards.
- Do not stack a hero, quick-action cards, promotional panels, and stat cards all at the top. That becomes noisy fast.
- Keep dashboard CTAs in the page header. Use at most two primary actions.
- Use white cards, thin borders, compact spacing, and very light shadows or no shadows.
- Prefer operational content: orders, messages, earnings, delivery progress, saved services, active gigs.
- Avoid large image-led promotional sections unless the dashboard is specifically a marketplace discovery dashboard.
- If images are used in dashboard cards, keep them inside service/gig cards only.
- Keep the stats row small and scannable. Do not turn every stat into a large feature card.
- Make stats feel like a single metrics strip with subtle dividers instead of four separate boxes.
- Let dashboard sections breathe as open panels with headings and bottom dividers. Avoid wrapping every section in a framed card.
- Reserve obvious card boxes for content that needs a frame: gig thumbnails, profile summaries, package cards, modals, or forms.
- If a page starts looking boxy, remove one layer first: border, background, shadow, or radius. Usually only one or two are needed.
- Links inside dashboard card headers can be plain text links with underline instead of bordered buttons.
- Dashboard card headings should be simple: small kicker, short title, one small action link.
- Use muted dividers and table rows. Avoid heavy backgrounds and decorative gradients.
- A clean dashboard page should usually follow this order:

```text
Page header with 1-2 CTAs
Compact stats row
Main content grid
Secondary service cards or activity lists
```

## Card Anatomy

Use this pattern for service/gig cards:

1. Fixed-ratio image/media area, usually `aspect-ratio: 1.64 / 1`.
2. Favorite heart button at top-right.
3. Optional play button for video-style previews.
4. Seller row: avatar, seller name, level or badge.
5. Title clamped to 2 lines.
6. Rating row with star, rating, review count.
7. Price row: `From $X`.
8. Optional trust label, such as video consultation.

Card behavior:

- Slight image zoom on hover: `scale(1.025)`.
- Keep card dimensions stable.
- Clamp long text.
- Use small border radius, usually `4px` to `6px`.
- Avoid heavy shadow on normal cards.

## Buttons

Primary CTA:

```css
background: #222325;
color: #ffffff;
border-radius: 4px to 6px;
font-weight: 800;
```

Secondary CTA:

```css
background: #ffffff;
color: #111111;
border: 1px solid #222325 or #dadbdd;
border-radius: 4px to 6px;
font-weight: 800;
```

Icon buttons:

- Use lucide-style or existing project icons.
- Use familiar symbols instead of text where possible.
- Circle buttons work well for carousel arrows and favorite actions.
- Square buttons work well for menus, share, report, and search submit.

## Forms, Search, And Filters

- Search bars should be prominent and wide.
- Filter buttons should be compact, bordered, and aligned horizontally on desktop.
- Dropdown filter panels should use white background, subtle shadow, and scrollable content if long.
- Apply/Clear actions should sit at the bottom of filter panels.
- Use checkboxes, switches, and segmented controls where appropriate.

## Sticky Navigation

- Sticky elements should appear only when useful.
- On profile pages, the profile sticky nav should replace the normal site header after the profile summary scrolls away.
- On gig details, package/sidebar panels can stay sticky on desktop but should become normal stacked content on mobile.
- Avoid sticky UI sitting awkwardly in the middle of the page.

## Sections To Reuse

Use these section types across templates:

- Hero with search and category chips.
- Category icon strip.
- Recently viewed service row.
- Freelancer/service card grid.
- Filter toolbar.
- Package comparison table.
- Seller profile summary.
- Portfolio preview.
- Review summary and review card.
- Related tags.
- Browsing history row.
- "Find freelance talent - your way" three-card section.
- Full-width CTA band.
- Large footer link columns.

## Reviews Pattern

Review sections should include:

- Total review count.
- Star distribution bars.
- Rating breakdown.
- Review search field.
- Sort selector.
- Review card with buyer info, rating, date, text, price, duration, and related service.
- Seller response toggle.
- Helpful controls.
- Show more reviews button.

## Profile Pattern

Public seller profiles should include:

- Large circular avatar with online dot.
- Name, handle, rating, level, title, location, languages.
- About me copy.
- Skill chips.
- Contact card with response time.
- Sticky profile nav that appears after the summary scrolls away.
- Services card.
- Portfolio card with side thumbnails.
- Work experience list.
- Sourcing CTA.
- Reviews.
- Bottom talent cards.

## Responsive Rules

- Use grids on desktop and stack on mobile.
- Preserve image aspect ratios.
- Keep buttons readable and avoid text overflow.
- Horizontal rows can scroll on mobile.
- Sticky sidebars should become normal content below `1120px` or similar.
- Hide floating message bubbles on small screens if they compete with content.

## Motion And Interaction

Use motion lightly:

```css
--transition: 180ms ease;
```

Good interactions:

- Image hover zoom.
- Button hover background flip.
- Border darkening on hover.
- Dropdown fade/slide.
- Sticky nav slide-in from top.

Avoid:

- Large animations.
- Decorative floating blobs.
- UI that shifts layout on hover.

## Template Checklist

Before finishing any design:

- The first screen makes the main purpose obvious.
- Search, navigation, or primary action is easy to find.
- Cards have consistent image ratios and stable heights.
- Text does not overflow or overlap.
- Metadata is easy to scan.
- Real images or realistic demo images are used.
- CTAs are clear and visually consistent.
- Mobile layout stacks cleanly.
- The design uses restrained colors and avoids visual clutter.
- Build passes.

## Prompt To Reuse With Codex

Use this prompt when asking Codex to build or modify another template:

```text
Please use the design style from FRONTEND_DESIGN_STYLE_GUIDE.md.
Make this page/component feel like the existing bdgigs marketplace UI:
clean Fiverr-inspired marketplace, compact cards, real images, thin borders,
black CTAs, subtle green trust accents, responsive layout, and reusable sections.
Keep the design functional and close to the existing frontend patterns.
```
