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

    fetchConversations: async (filter = "all") => {
        try {
            const query = filter && filter !== "all" ? `?filter=${filter}` : "";
            const threads = normalizeConversations(
                await apiRequest(`/api/conversations${query}`),
            );
            const previews = summarizeThreads(threads);

            set({
                buyerMessageThreads: threads,
                sellerMessageThreads: threads,
                messages: previews,
                sellerMessages: previews,
            });

            return threads;
        } catch (error) {
            set({ error: error.message });
            return get().buyerMessageThreads;
        }
    },

    fetchConversation: async (conversationId) => {
        const conversation = normalizeConversation(
            await apiRequest(`/api/conversations/${conversationId}`),
        );

        set((state) => updateConversationState(state, conversation));

        return conversation;
    },

    startConversation: async ({
        targetUserId,
        targetName,
        targetSlug,
        contextType,
        contextId,
        message,
    }) => {
        const conversation = normalizeConversation(
            await apiRequest("/api/conversations", {
                body: {
                    targetUserId,
                    targetName,
                    targetSlug,
                    contextType,
                    contextId,
                    message,
                    clientId: createClientId(),
                },
            }),
        );

        set((state) => updateConversationState(state, conversation));

        return conversation;
    },

    sendMessage: async (conversationId, text) => {
        const clientId = createClientId();
        const message = normalizeMessage(await apiRequest(
            `/api/conversations/${conversationId}/messages`,
            {
                body: { text, clientId },
            },
        ));

        set((state) => {
            const threads = state.buyerMessageThreads.map((thread) =>
                thread.id === conversationId
                    ? {
                          ...thread,
                          preview: message.text,
                          time: message.time,
                          messages: upsertById(thread.messages || [], message),
                      }
                    : thread,
            );
            const previews = summarizeThreads(threads);

            return {
                buyerMessageThreads: threads,
                sellerMessageThreads: threads,
                messages: previews,
                sellerMessages: previews,
            };
        });

        return message;
    },

    markConversationRead: async (conversationId) => {
        const conversation = normalizeConversation(
            await apiRequest(`/api/conversations/${conversationId}/read`, {
                method: "PATCH",
            }),
        );

        set((state) => updateConversationState(state, conversation));

        return conversation;
    },

    sendTyping: async (conversationId) => {
        try {
            await apiRequest(`/api/conversations/${conversationId}/typing`, {
                body: {},
            });
        } catch (error) {
            set({ error: error.message });
        }
    },

    applyRealtimeMessage: (payload) => {
        const conversation = payload.conversation
            ? normalizeConversation(payload.conversation)
            : null;
        const message = payload.message
            ? normalizeMessage(payload.message)
            : null;

        set((state) => {
            if (conversation) {
                return updateConversationState(state, conversation);
            }

            if (!message || !payload.conversationId) {
                return {};
            }

            const threads = state.buyerMessageThreads.map((thread) =>
                thread.id === payload.conversationId
                    ? {
                          ...thread,
                          preview: message.text,
                          time: message.time,
                          messages: upsertById(thread.messages || [], message),
                      }
                    : thread,
            );
            const previews = summarizeThreads(threads);

            return {
                buyerMessageThreads: threads,
                sellerMessageThreads: threads,
                messages: previews,
                sellerMessages: previews,
            };
        });
    },

    applyRealtimeConversation: (conversation) => {
        const normalized = normalizeConversation(conversation);

        set((state) => updateConversationState(state, normalized));
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

function normalizeConversations(conversations) {
    return (conversations || []).map(normalizeConversation);
}

function normalizeConversation(conversation) {
    const messages = (conversation?.messages || []).map(normalizeMessage);
    const fallbackName =
        conversation?.counterpart?.name ||
        conversation?.name ||
        "Conversation";

    return {
        id: conversation?.id,
        initials:
            conversation?.initials ||
            conversation?.counterpart?.initials ||
            fallbackName
                .split(" ")
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part[0])
                .join("")
                .toUpperCase(),
        name: fallbackName,
        role: conversation?.role || "Member",
        service: conversation?.service || "Conversation",
        status: conversation?.status || "Open",
        statusClass: conversation?.statusClass || "status-progress",
        time: conversation?.time || "",
        unread: Number(conversation?.unread || 0),
        priority: conversation?.priority || "",
        preview:
            conversation?.preview ||
            messages[messages.length - 1]?.text ||
            "",
        messages,
        context: conversation?.context || {},
        counterpart: conversation?.counterpart || null,
        viewerParticipant: conversation?.viewerParticipant || null,
        participants: conversation?.participants || [],
    };
}

function normalizeMessage(message) {
    return {
        id: message?.id || message?.clientId || createClientId(),
        conversationId: message?.conversationId,
        senderId: message?.senderId,
        recipientId: message?.recipientId,
        clientId: message?.clientId,
        from: message?.from || "User",
        text: message?.text || "",
        time: message?.time || "",
        sentAt: message?.sentAt,
        readAt: message?.readAt,
        own: Boolean(message?.own),
        attachments: message?.attachments || [],
    };
}

function updateConversationState(state, conversation) {
    const threads = upsertById(state.buyerMessageThreads, conversation);
    const previews = summarizeThreads(threads);

    return {
        buyerMessageThreads: threads,
        sellerMessageThreads: threads,
        messages: previews,
        sellerMessages: previews,
    };
}

function createClientId() {
    return `client-${Date.now()}-${Math.random().toString(36).slice(2)}`;
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
