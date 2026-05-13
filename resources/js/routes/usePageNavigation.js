import { useCallback } from "react";
import { useNavigate } from "react-router-dom";
import { PAGE_PATHS } from "./routeConfig.js";

export function usePageNavigation() {
  const routerNavigate = useNavigate();

  return useCallback(
    (nextPage, hash = "") => {
      if (nextPage.startsWith("/")) {
        routerNavigate(`${nextPage}${hash}`);
        return;
      }

      const nextPath = PAGE_PATHS[nextPage] || PAGE_PATHS.home;
      routerNavigate(`${nextPath}${hash}`);
    },
    [routerNavigate],
  );
}
