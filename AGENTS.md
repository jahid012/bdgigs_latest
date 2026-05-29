# bdgigs React/Laravel Agent Guide

## Project Snapshot

bdgigs is a Laravel application that serves a Vite-powered React SPA for the marketplace frontend and a Blade-only admin panel for operations. The user-facing product UI is in `resources/js` and `resources/css`; admin, access control, settings, and notifications are handled by Laravel.

Core stack:

- PHP `^8.3` with Laravel `^13.7`
- React `^19.2.5`, React DOM `^19.2.5`
- React Router DOM `^7.15.0`
- Vite `^8.0.0` with `laravel-vite-plugin`
- Zustand powers the React marketplace state layer, seeded from mock data and hydrated from Laravel APIs
- Laravel Echo and `pusher-js` are installed for Pusher-compatible realtime hooks

## Important Commands

- `npm run dev` starts the Vite dev server.
- `npm run build` builds the frontend assets.
- `npm run preview` previews the built Vite app.
- `composer dev` starts Laravel, queue listening, logs, and Vite together through `concurrently`.
- `composer test` clears config and runs the Laravel test suite.
- `php artisan test` runs Laravel tests directly.

There is no dedicated JavaScript lint or test script at the moment. For frontend changes, run `npm run build` as the main verification step. For PHP or routing changes, run `composer test` or `php artisan test`.

## Repository Structure

- `app/` contains the Laravel backend, including admin controllers, the `User` model, platform settings helpers/services, and service providers.
- `routes/admin.php` contains the Blade admin panel routes and is loaded before `routes/web.php` from `bootstrap/app.php` so admin URLs are not swallowed by the SPA catch-all.
- `routes/api.php` contains session-authenticated marketplace JSON endpoints under `/api/*` and is loaded before the SPA catch-all.
- `routes/channels.php` contains private broadcast channel authorization such as `user.{id}`.
- `routes/web.php` maps `/` and every other non-admin path to `resources/views/app.blade.php` so React Router can handle navigation.
- `resources/views/app.blade.php` defines the SPA root, CSRF meta tag, Inter font loading, shared notifications, and Vite entries.
- `resources/js/` contains the React app.
- `resources/js/stores/` contains Zustand stores for session, dashboard, marketplace, and gig editor state.
- `resources/js/api/apiClient.js` contains the shared `fetch` wrapper with session credentials, CSRF headers, and JSON error handling.
- `resources/js/realtime/echo.js` initializes Laravel Echo only when Pusher env keys are present.
- `resources/css/` contains global, home, and dashboard styles imported by `resources/css/app.css`.
- `public/assets/img/gig_images/` contains service/gig images referenced by frontend data.
- `public/build/`, `public/hot`, `node_modules/`, `vendor/`, logs, and generated caches are build/runtime artifacts. Do not edit them for source changes.
- `tests/` contains the default Laravel Feature and Unit tests.

## Agent Quick Start
- Treat `AGENTS.md` as the primary AI guide for coding tasks in this repository.
- Prefer reading existing docs in `docs/` before adding new domain behavior.
- Keep edits scoped to source folders: `resources/js`, `resources/css`, `app`, `routes`, `config`, `tests`, `database`, and `docs`.
- Use `npm run build` after frontend/CSS changes and `php artisan test` after backend/API changes.
- If you add or change SPA pages, read `resources/js/routes/routeConfig.js` and `resources/js/routes/AppRoutes.jsx` first.

## Admin, Access Control, And Settings

- The admin panel is Blade-only and lives under the prefix from `config/admin.php` (`ADMIN_ROUTE_PREFIX`, default `/admin`). Keep admin routes in `routes/admin.php` and admin controllers in `app/Http/Controllers/Admin/`.
- Admin auth uses the normal `users` table. Do not create separate admin/staff tables unless the product direction changes.
- Spatie Laravel Permission is installed. `App\Models\User` uses `HasRoles`; admin access requires `admin.access`, with page permissions such as `users.view`, `gigs.view`, `settings.update`, and `roles.manage`.
- Seller levels are marketplace business logic, not Spatie roles. Roles answer what a user can access; seller levels answer what benefits a seller receives.
- Access control pages are separate from Settings: `/admin/roles`, `/admin/roles/{role}/permissions`, and `/admin/roles/users` manage roles, permissions, and assigning roles to users.
- Platform settings are stored in the `platform_settings` table, defined in `config/platform_settings.php`, cached by `App\Support\PlatformSettings`, and saved from `/admin/settings`.
- Use helpers for settings access: `appSetting()`, `platformSetting()`, `platformSettings()`, `setPlatformSetting()`, and `clearPlatformSettingsCache()`.
- Flash and JS notifications use the shared toast system in `public/assets/shared/notify.css`, `public/assets/shared/notify.js`, and `resources/views/partials/notifications.blade.php`. In PHP use `->withNotify()`, `redirectWithNotify()`, or `backWithNotify()`; in JS use `window.notify.success(...)`, `window.notify.error(...)`, or `window.notify({...})`.

## Dynamic Marketplace, Auth, And APIs

- Marketplace auth uses the normal `users` table and the Laravel `web` session guard. React calls `/api/me`, `/api/auth/login`, `/api/auth/register`, and `/api/auth/logout`.
- The homepage auth modal now supports email/password sign in and sign up. Dashboard routes are guarded in `AppRoutes.jsx`; unauthenticated users are redirected to `/?auth=login&redirect=...`.
- Marketplace data is database-backed through models for gigs, orders, conversations, messages, saved services, and user notifications. The demo seeder populates enough data for the current UI.
- Key API groups are seller services, marketplace gigs, saved services, orders, conversations/messages, and notifications. Keep response shapes close to the current React store shapes.
- Persisted notifications live in the `notifications` table through `App\Models\UserNotification` and are exposed through `/api/notifications`.
- Realtime is prepared, not fully required for normal operation. Events include `NotificationCreated`, `MessageSent`, and `OrderStatusUpdated`; API fetching remains the source of truth.

## React Entry And Routing

- `resources/js/main.jsx` mounts `<App />` into `#root` inside React `StrictMode`.
- `resources/js/main.jsx` also calls `configureRealtime()`; Echo is only enabled when Pusher Vite env keys exist.
- `resources/js/App.jsx` only wraps `AppRoutes` in `BrowserRouter`.
- `resources/js/routes/AppRoutes.jsx` renders all client routes from `routeConfig.js`.
- `resources/js/routes/routeConfig.js` is the source of truth for pages, paths, document titles, dashboard metadata, and route keys.
- Laravel intentionally falls back to the Blade app view for non-admin routes. Add client pages in `routeConfig.js`; only change Laravel routing when the server needs a real backend endpoint.

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
- `data/` now acts mostly as seed/fallback data and static copy. Dynamic dashboard, marketplace, and gig editor state should go through stores first.
- `stores/` contains Zustand domain stores. Add new shared dynamic state there instead of passing large mutable datasets through components.
- `api/` contains frontend API helpers. Use `apiClient` for Laravel JSON requests so CSRF/session handling stays consistent.
- `realtime/` contains Echo setup. Keep websocket usage optional and backed by normal API fetching.
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
- Keep temporary UI state local with `useState`, `useMemo`, `useCallback`, `useRef`, and `useEffect`. Use Zustand for shared domain data such as session, gigs, seller services, orders, messages, notifications, saved services, and gig drafts.
- Use optional callbacks (`onNavigate?.()`) only when a component can safely operate without the callback.
- Keep buyer/seller behavior unified through `variant = "buyer"` and `isSeller = variant === "seller"` when the UI is mostly shared.

## Navigation Patterns

- Use `Link` and `NavLink` from `react-router-dom` for normal in-app links inside the SPA.
- Use the `onNavigate(pageKey, hash = "")` helper for components that need to navigate by route key or jump to homepage hash sections.
- When intercepting an `<a>` click to call `onNavigate`, call `event.preventDefault()` first.
- `useRouteEffects()` owns document titles, body classes (`home-page`, `dashboard-page`, `seller-dashboard-page`), scroll-to-top behavior, and hash scrolling. Keep those route-wide side effects centralized there.
- Dashboard shell pages are wrapped by `DashboardPage` in `AppRoutes.jsx`; dashboard route metadata controls title, search placeholder, messages state, and seller/buyer variant.
- Dashboard routes must stay behind the `RequireAuth` guard in `AppRoutes.jsx`.

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

- The backend now includes a real Blade admin panel, Spatie access control, cached platform settings, marketplace APIs, persisted user notifications, and Pusher-compatible broadcast hooks. Keep backend additions aligned with those systems instead of adding duplicate helpers.
- The default feature test verifies `/` returns HTTP 200. If server routes change, update or add Feature tests.
- If adding database-backed behavior, use Laravel migrations, models, factories, and tests following standard Laravel conventions.
- `MarketplaceApiTest` covers the new marketplace APIs, but the local PHP install must include `pdo_sqlite` for the default in-memory PHPUnit database.

## Working Rules For Future Agents

- Read `resources/js/routes/routeConfig.js` before changing navigation or page availability.
- Search `resources/js/stores/`, `resources/js/api/`, and `resources/js/data/` before adding new copy, cards, orders, services, settings, or dashboard records.
- Keep source edits scoped to `resources/js`, `resources/css`, Laravel app files, routes, tests, or public source assets as appropriate.
- Do not edit dependency or generated directories (`vendor/`, `node_modules/`, `public/build/`) for application changes.
- Prefer `npm run build` after React/CSS edits and `php artisan test` after Laravel edits.
