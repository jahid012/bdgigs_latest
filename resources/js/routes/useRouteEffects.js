import { useEffect } from "react";
import { useLocation } from "react-router-dom";
import { getDocumentTitle, getPageKind } from "./routeConfig.js";

export function useRouteEffects() {
  const { hash, pathname } = useLocation();

  useEffect(() => {
    const pageKind = getPageKind(pathname);
    const isDashboard = pageKind === "dashboard" || pageKind === "seller-dashboard";

    document.body.classList.toggle("home-page", pageKind === "home");
    document.body.classList.toggle("dashboard-page", isDashboard);
    document.body.classList.toggle("seller-dashboard-page", pageKind === "seller-dashboard");
    document.title = getDocumentTitle(pathname);

    const frameId = window.requestAnimationFrame(() => {
      const target = getHashTarget(hash);

      if (target) {
        target.scrollIntoView({ behavior: "smooth" });
        return;
      }

      window.scrollTo({ top: 0, behavior: "smooth" });
    });

    return () => window.cancelAnimationFrame(frameId);
  }, [hash, pathname]);
}

function getHashTarget(hash) {
  if (!hash) return null;

  try {
    const id = decodeURIComponent(hash.slice(1));
    return document.getElementById(id) || document.querySelector(hash);
  } catch {
    return null;
  }
}
