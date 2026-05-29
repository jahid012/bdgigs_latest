import Echo from "laravel-echo";
import Pusher from "pusher-js";

export function configureRealtime() {
    const key = import.meta.env.VITE_PUSHER_APP_KEY;

    if (!key || window.Echo) {
        return window.Echo || null;
    }

    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: "pusher",
        key,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || "ap2",
        wsHost: import.meta.env.VITE_PUSHER_HOST || undefined,
        wsPort: Number(import.meta.env.VITE_PUSHER_PORT || 80),
        wssPort: Number(import.meta.env.VITE_PUSHER_PORT || 443),
        forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || "https") === "https",
        enabledTransports: ["ws", "wss"],
        authEndpoint: "/broadcasting/auth",
        csrfToken: document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content"),
    });

    return window.Echo;
}
