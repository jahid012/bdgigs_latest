# BDGigs React/Laravel Agent Guide

## Project Snapshot

BDGigs is a Laravel application that serves a Vite-powered React SPA. Laravel currently provides the PHP shell, catch-all web routing, configuration, and tests; the user-facing product UI is in `resources/js` and `resources/css`.

Core stack:

- PHP `^8.3` with Laravel `^13.7`
- React `^19.2.5`, React DOM `^19.2.5`
- React Router DOM `^7.15.0`
- Vite `^8.0.0` with `laravel-vite-plugin`
- Zustand is installed but is not currently used in `resources/js`

## Important Commands

- `npm run dev` starts the Vite dev server.
- `npm run build` builds the frontend assets.
- `npm run preview` previews the built Vite app.
- `composer dev` starts Laravel, queue listening, logs, and Vite together through `concurrently`.
- `composer test` clears config and runs the Laravel test suite.
- `php artisan test` runs Laravel tests directly.

There is no dedicated JavaScript lint or test script at the moment. For frontend changes, run `npm run build` as the main verification step. For PHP or routing changes, run `composer test` or `php artisan test`.

## Repository Structure

- `app/` contains the minimal Laravel backend: base controller, `User` model, and service provider.
- `routes/web.php` maps `/` and every other path to `resources/views/app.blade.php` so React Router can handle navigation.
- `resources/views/app.blade.php` defines the SPA root `<div id="root"></div>`, loads Inter from Google Fonts, and includes `resources/css/app.css` plus `resources/js/main.jsx` through Vite.
- `resources/js/` contains the React app.
- `resources/css/` contains global, home, and dashboard styles imported by `resources/css/app.css`.
- `public/assets/img/gig_images/` contains service/gig images referenced by frontend data.
- `public/build/`, `public/hot`, `node_modules/`, `vendor/`, logs, and generated caches are build/runtime artifacts. Do not edit them for source changes.
- `tests/` contains the default Laravel Feature and Unit tests.

## React Entry And Routing

- `resources/js/main.jsx` mounts `<App />` into `#root` inside React `StrictMode`.
- `resources/js/App.jsx` only wraps `AppRoutes` in `BrowserRouter`.
- `resources/js/routes/AppRoutes.jsx` renders all client routes from `routeConfig.js`.
- `resources/js/routes/routeConfig.js` is the source of truth for pages, paths, document titles, dashboard metadata, and route keys.
- Laravel intentionally falls back to the Blade app view for all routes. Add client pages in `routeConfig.js`; only change Laravel routing when the server needs a real backend endpoint.

When adding a page:

- Create a PascalCase page component in `resources/js/pages/*.jsx`.
- Import it in `resources/js/routes/routeConfig.js`.
- Add a route object with `key`, `path`, `documentTitle`, `title` if dashboard-scoped, `searchPlaceholder` if dashboard-scoped, and `Component`.
- Set `withNavigation: true` when the page or its children need the `onNavigate(pageKey, hash)` helper.
- Use `pageProps` for page-specific props such as `{ variant: "buyer" }` or `{ variant: "seller" }`.
- Use route keys from `PAGE_PATHS`/`routeConfig.js` when navigating by page key.

## `resources/js` Folder Conventions

- `components/common/` holds shared presentational helpers. `Icons.jsx` exports `Icon`, `BrandMark`, and `Rating`.
- `components/layout/` holds site-wide layout pieces such as `Header` and `Footer`.
- `components/home/` holds homepage sections.
- `components/dashboard/` holds dashboard layout and reusable dashboard workspaces.
- `components/dashboard/settings/` and `components/dashboard/earnings/` contain feature-specific dashboard components.
- `pages/` contains route-level components. Thin buyer/seller wrapper pages are normal when they pass a `variant` to a shared workspace.
- `data/` contains static mock data and copy as named exports. Prefer extending these data files instead of hard-coding large arrays in components.
- `hooks/` contains reusable React hooks as named exports.
- `routes/` contains router config and route side-effect hooks.
- `utils/` contains framework-independent helpers such as chart geometry.

## JavaScript Style

- Use ES modules with explicit relative file extensions in imports, matching the existing code: `../pages/HomePage.jsx`, `./routeConfig.js`.
- Use double quotes, semicolons, trailing commas where already used, and 2-space indentation in JS/JSX files.
- Prefer function declarations for React components: `function Header(...) { ... }`.
- Default-export route pages and most single components from their files.
- Use named exports for shared helpers, hooks, data, and grouped control components.
- Keep component filenames PascalCase for JSX components and camelCase for hooks/utilities/data files.
- Keep React state local with `useState`, `useMemo`, `useCallback`, `useRef`, and `useEffect` unless shared state becomes necessary. Zustand is available but not currently part of the app pattern.
- Use optional callbacks (`onNavigate?.()`) only when a component can safely operate without the callback.
- Keep buyer/seller behavior unified through `variant = "buyer"` and `isSeller = variant === "seller"` when the UI is mostly shared.

## Navigation Patterns

- Use `Link` and `NavLink` from `react-router-dom` for normal in-app links inside the SPA.
- Use the `onNavigate(pageKey, hash = "")` helper for components that need to navigate by route key or jump to homepage hash sections.
- When intercepting an `<a>` click to call `onNavigate`, call `event.preventDefault()` first.
- `useRouteEffects()` owns document titles, body classes (`home-page`, `dashboard-page`, `seller-dashboard-page`), scroll-to-top behavior, and hash scrolling. Keep those route-wide side effects centralized there.
- Dashboard shell pages are wrapped by `DashboardPage` in `AppRoutes.jsx`; dashboard route metadata controls title, search placeholder, messages state, and seller/buyer variant.

## UI And Accessibility Patterns

- Reuse existing class names and CSS utilities such as `container`, `section`, `card`, `btn`, `tag`, `avatar`, `status-badge`, `sr-only`, and `skip-link`.
- Use `Icon` from `components/common/Icons.jsx` for existing icons. Add icons to `iconPaths` only when needed by multiple UI pieces or when matching the current icon system.
- Preserve accessibility details already used in the app: `aria-label`, `aria-expanded`, `aria-pressed`, `aria-controls`, semantic `main`/`section`/`nav`, and hidden labels for search inputs.
- Keep dashboard pages dense, practical, and scan-friendly. Home sections can be more promotional, but should still use the existing visual system.
- Static image paths in data currently point to public assets like `/assets/img/gig_images/1.png`.

## CSS Conventions

- `resources/css/app.css` only imports the actual style files.
- Put global tokens, resets, header/footer, buttons, cards, and shared utilities in `global.css`.
- Put homepage-specific styles in `home.css`.
- Put dashboard, settings, finance, order, message, and seller/buyer workspace styles in `dashboard.css`.
- The design system uses CSS variables in `:root` for colors, shadows, radii, container sizing, and transitions. Prefer those variables over one-off values.
- Class naming is plain descriptive CSS, not CSS modules or Tailwind.

## Backend And Testing Notes

- Backend code is still close to a fresh Laravel install. Avoid adding backend complexity unless the requested feature needs server persistence, APIs, auth, queues, or database behavior.
- The default feature test verifies `/` returns HTTP 200. If server routes change, update or add Feature tests.
- If adding database-backed behavior, use Laravel migrations, models, factories, and tests following standard Laravel conventions.

## Working Rules For Future Agents

- Read `resources/js/routes/routeConfig.js` before changing navigation or page availability.
- Search `resources/js/data/` before adding new copy, cards, orders, services, settings, or dashboard records.
- Keep source edits scoped to `resources/js`, `resources/css`, Laravel app files, routes, tests, or public source assets as appropriate.
- Do not edit dependency or generated directories (`vendor/`, `node_modules/`, `public/build/`) for application changes.
- Prefer `npm run build` after React/CSS edits and `php artisan test` after Laravel edits.
