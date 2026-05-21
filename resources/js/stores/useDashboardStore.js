import { create } from "zustand";
import { apiRequest } from "../api/apiClient.js";
import {
    buyerMessageThreads,
    buyerNotifications,
    chartData,
    dashboardHighlights,
    messages,
    orders,
    recommendedServices,
    sellerChartData,
    sellerDashboardHighlights,
    sellerMessageThreads,
    sellerMessages,
    sellerNotifications,
    sellerOrderInsights,
    sellerOrders,
    sellerPipeline,
    sellerServices as initialSellerServices,
    sellerStats,
    stats,
    buyerOrderInsights,
} from "../data/dashboardData.js";

const deepClone = (value) => JSON.parse(JSON.stringify(value));

export const useDashboardStore = create((set, get) => ({
    error: null,
    isLoading: false,
    stats: deepClone(stats),
    sellerStats: deepClone(sellerStats),
    dashboardHighlights: deepClone(dashboardHighlights),
    sellerDashboardHighlights: deepClone(sellerDashboardHighlights),
    orders: deepClone(orders),
    sellerOrders: deepClone(sellerOrders),
    buyerOrderInsights: deepClone(buyerOrderInsights),
    sellerOrderInsights: deepClone(sellerOrderInsights),
    messages: deepClone(messages),
    sellerMessages: deepClone(sellerMessages),
    buyerNotifications: deepClone(buyerNotifications),
    sellerNotifications: deepClone(sellerNotifications),
    buyerMessageThreads: deepClone(buyerMessageThreads),
    sellerMessageThreads: deepClone(sellerMessageThreads),
    recommendedServices: deepClone(recommendedServices),
    sellerServices: deepClone(initialSellerServices),
    chartData: deepClone(chartData),
    sellerChartData: deepClone(sellerChartData),
    sellerPipeline: deepClone(sellerPipeline),

    getSellerServiceById: (id) =>
        get().sellerServices.find((service) => service.id === id),

    fetchSellerServices: async () => {
        set({ isLoading: true, error: null });

        try {
            const services = await apiRequest("/api/seller/services");
            set({ sellerServices: services, isLoading: false });
            return services;
        } catch (error) {
            set({ error: error.message, isLoading: false });
            return get().sellerServices;
        }
    },

    fetchSellerService: async (id) => {
        try {
            const service = await apiRequest(`/api/seller/services/${id}`);

            set((state) => ({
                sellerServices: upsertById(state.sellerServices, service),
            }));

            return service;
        } catch (error) {
            set({ error: error.message });
            return get().getSellerServiceById(id);
        }
    },

    addSellerService: (draft) => {
        const service = normalizeSellerService(draft, get().sellerServices);

        set((state) => ({
            sellerServices: [service, ...state.sellerServices],
        }));

        return service;
    },

    createSellerService: async (draft) => {
        try {
            const service = await apiRequest("/api/seller/services", {
                body: draft,
            });

            set((state) => ({
                sellerServices: upsertById(state.sellerServices, service),
            }));

            return service;
        } catch (error) {
            set({ error: error.message });
            return get().addSellerService(draft);
        }
    },

    updateSellerService: (id, draft) => {
        let updatedService = null;

        set((state) => ({
            sellerServices: state.sellerServices.map((service) => {
                if (service.id !== id) return service;

                updatedService = {
                    ...service,
                    ...normalizeSellerService(
                        draft,
                        state.sellerServices,
                        service,
                    ),
                };

                return updatedService;
            }),
        }));

        return updatedService;
    },

    saveSellerService: async (id, draft) => {
        try {
            const service = await apiRequest(`/api/seller/services/${id}`, {
                method: "PATCH",
                body: draft,
            });

            set((state) => ({
                sellerServices: upsertById(state.sellerServices, service),
            }));

            return service;
        } catch (error) {
            set({ error: error.message });
            return get().updateSellerService(id, draft);
        }
    },

    fetchOrders: async (role = "buyer") => {
        try {
            const orders = await apiRequest(`/api/orders?role=${role}`);

            set(
                role === "seller"
                    ? { sellerOrders: orders }
                    : { orders },
            );

            return orders;
        } catch (error) {
            set({ error: error.message });
            return role === "seller" ? get().sellerOrders : get().orders;
        }
    },

    fetchConversations: async (role = "buyer") => {
        try {
            const threads = await apiRequest(`/api/conversations?role=${role}`);
            const previews = summarizeThreads(threads);

            set(
                role === "seller"
                    ? {
                          sellerMessageThreads: threads,
                          sellerMessages: previews,
                      }
                    : {
                          buyerMessageThreads: threads,
                          messages: previews,
                      },
            );

            return threads;
        } catch (error) {
            set({ error: error.message });
            return role === "seller"
                ? get().sellerMessageThreads
                : get().buyerMessageThreads;
        }
    },

    sendMessage: async (conversationId, text, role = "buyer") => {
        const message = await apiRequest(
            `/api/conversations/${conversationId}/messages`,
            {
                body: { text },
            },
        );

        set((state) => {
            const threadKey =
                role === "seller" ? "sellerMessageThreads" : "buyerMessageThreads";
            const previewKey = role === "seller" ? "sellerMessages" : "messages";
            const threads = state[threadKey].map((thread) =>
                thread.id === conversationId
                    ? {
                          ...thread,
                          preview: message.text,
                          time: message.time,
                          messages: [...thread.messages, message],
                      }
                    : thread,
            );

            return {
                [threadKey]: threads,
                [previewKey]: summarizeThreads(threads),
            };
        });

        return message;
    },

    fetchNotifications: async () => {
        try {
            const notifications = await apiRequest("/api/notifications");
            set({
                buyerNotifications: notifications,
                sellerNotifications: notifications,
            });
            return notifications;
        } catch (error) {
            set({ error: error.message });
            return get().buyerNotifications;
        }
    },

    markNotificationRead: async (id) => {
        const notification = await apiRequest(`/api/notifications/${id}/read`, {
            method: "PATCH",
        });

        set((state) => ({
            buyerNotifications: replaceNotification(
                state.buyerNotifications,
                notification,
            ),
            sellerNotifications: replaceNotification(
                state.sellerNotifications,
                notification,
            ),
        }));

        return notification;
    },

    markAllNotificationsRead: async () => {
        const notifications = await apiRequest("/api/notifications/read-all", {
            method: "PATCH",
        });

        set({
            buyerNotifications: notifications,
            sellerNotifications: notifications,
        });

        return notifications;
    },
}));

function upsertById(items, item) {
    const exists = items.some((current) => current.id === item.id);

    if (!exists) {
        return [item, ...items];
    }

    return items.map((current) => (current.id === item.id ? item : current));
}

function summarizeThreads(threads) {
    return threads.map((thread) => ({
        initials: thread.initials,
        name: thread.name,
        message:
            thread.preview ||
            thread.messages?.[thread.messages.length - 1]?.text ||
            "",
        time: thread.time,
    }));
}

function replaceNotification(notifications, notification) {
    return notifications.map((item) =>
        item.id === notification.id ? notification : item,
    );
}

function normalizeSellerService(draft, services, existingService = null) {
    const basicPackage = draft.packages?.[0] || {};
    const title = draft.title?.trim() || existingService?.title || "Untitled Gig";
    const category =
        draft.category?.trim() ||
        existingService?.category ||
        "Programming & Tech";

    return {
        id:
            existingService?.id ||
            createUniqueServiceId(title, services || []),
        title,
        category,
        rating: existingService?.rating || "0.0",
        price: normalizeMoney(basicPackage.price || existingService?.price),
        image:
            draft.galleryImages?.[0] ||
            existingService?.image ||
            "/assets/img/gig_images/1.png",
        tag:
            draft.tags?.find((tag) => tag?.trim()) ||
            existingService?.tag ||
            "New Gig",
        delivery: normalizeDelivery(
            basicPackage.delivery || existingService?.delivery,
        ),
        orders: existingService?.orders || "0 active",
        conversion: existingService?.conversion || "New listing",
        status: existingService?.status || "Live",
        statusClass: existingService?.statusClass || "status-completed",
    };
}

function normalizeMoney(value = "0") {
    const amount = String(value).replace(/[^0-9.]/g, "") || "0";
    return `$${amount}`;
}

function normalizeDelivery(value = "3 days") {
    const text = String(value);
    const match = text.match(/(\d+)\s*day/i);

    if (!match) return text;

    const amount = Number(match[1]);
    return `${amount} ${amount === 1 ? "day" : "days"}`;
}

function createUniqueServiceId(title, services) {
    const baseId = slugify(title) || `seller-gig-${Date.now()}`;

    if (!services.some((service) => service.id === baseId)) {
        return baseId;
    }

    return `${baseId}-${Date.now()}`;
}

function slugify(value) {
    return String(value)
        .toLowerCase()
        .replace(/&/g, "and")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");
}
