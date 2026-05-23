import { useEffect, useState } from "react";
import { Icon } from "./Icons.jsx";

function FavoriteButton({
    active = false,
    className = "",
    label,
    onToggle,
    stopPropagation = false,
}) {
    const [isSaving, setIsSaving] = useState(false);
    const [isPulsing, setIsPulsing] = useState(false);

    useEffect(() => {
        if (!isPulsing) return undefined;

        const pulseTimer = window.setTimeout(() => setIsPulsing(false), 520);

        return () => window.clearTimeout(pulseTimer);
    }, [isPulsing]);

    const toggleFavorite = async (event) => {
        if (stopPropagation) {
            event.stopPropagation();
        }

        if (isSaving) return;

        setIsSaving(true);

        try {
            const nextActive = await onToggle?.();

            if (nextActive === true) {
                setIsPulsing(false);
                window.requestAnimationFrame(() => setIsPulsing(true));
            }
        } catch {
            // Store actions already surface the error in shared state.
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <button
            className={[
                className,
                active ? "is-favorite" : "",
                isPulsing ? "is-pulsing" : "",
                isSaving ? "is-saving" : "",
            ]
                .filter(Boolean)
                .join(" ")}
            type="button"
            aria-label={label}
            aria-pressed={Boolean(active)}
            disabled={isSaving}
            onClick={toggleFavorite}
        >
            <Icon name="heart" />
        </button>
    );
}

export default FavoriteButton;
