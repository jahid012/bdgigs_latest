import { useEffect } from "react";

export function useDismissOnInteractOutside(
    ref,
    isActive,
    onDismiss,
    options = {},
) {
    const { includeEscape = true } = options;

    useEffect(() => {
        if (!isActive) return undefined;

        const handleDocumentClick = (event) => {
            if (ref.current?.contains(event.target)) return;
            onDismiss();
        };

        const handleKeyDown = (event) => {
            if (event.key === "Escape") {
                onDismiss();
            }
        };

        document.addEventListener("click", handleDocumentClick);

        if (includeEscape) {
            document.addEventListener("keydown", handleKeyDown);
        }

        return () => {
            document.removeEventListener("click", handleDocumentClick);
            document.removeEventListener("keydown", handleKeyDown);
        };
    }, [includeEscape, isActive, onDismiss, ref]);
}
