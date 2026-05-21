self.addEventListener("push", (event) => {
    const payload = event.data?.json() || {};
    const notification = payload.notification || {};
    const data = payload.data || {};
    const title = notification.title || data.title || "bdgigs";
    const options = {
        body: notification.body || data.body || "",
        icon: notification.icon || "/favicon.ico",
        badge: notification.badge || "/favicon.ico",
        data: {
            url: data.url || "/dashboard/messages",
        },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener("notificationclick", (event) => {
    event.notification.close();

    const url = event.notification.data?.url || "/dashboard/messages";

    event.waitUntil(
        self.clients
            .matchAll({
                type: "window",
                includeUncontrolled: true,
            })
            .then((clients) => {
                const openClient = clients.find((client) =>
                    client.url.includes(self.location.origin),
                );

                if (openClient) {
                    openClient.focus();
                    openClient.navigate(url);
                    return;
                }

                return self.clients.openWindow(url);
            }),
    );
});
