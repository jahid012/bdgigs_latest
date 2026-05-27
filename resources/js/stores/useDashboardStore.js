import { create } from "zustand";
import { apiRequest } from "../api/apiClient.js";
export const useDashboardStore = create((set, get) => ({
    error: null,
    isLoading: false,
    isConversationsLoading: false,
    isSellerServicesLoading: false,
    stats: [],
    sellerStats: [],
    dashboardHighlights: [],
    sellerDashboardHighlights: [],
    orders: [],
    sellerOrders: [],
    buyerOrderInsights: [],
    sellerOrderInsights: [],
    messages: [],
    sellerMessages: [],
    buyerNotifications: [],
    sellerNotifications: [],
    buyerMessageThreads: [],
    sellerMessageThreads: [],
    recommendedServices: [],
    sellerServices: [],
    chartData: [],
    sellerChartData: [],
    sellerPipeline: [],
    sellerFinance: null,

    getSellerServiceById: (id) =>
        get().sellerServices.find((service) => service.id === id),

    fetchSellerServices: async () => {
        set({ isLoading: true, isSellerServicesLoading: true, error: null });

        try {
            const services = await apiRequest("/api/seller/services");
            set({
                sellerServices: services,
                isLoading: false,
                isSellerServicesLoading: false,
            });
            return services;
        } catch (error) {
            set({
                error: error.message,
                isLoading: false,
                isSellerServicesLoading: false,
            });
            return get().sellerServices;
        }
    },

    fetchDashboardSummary: async (variant = "buyer") => {
        set({ isLoading: true, error: null });

        try {
            const summary = await apiRequest(
                `/api/user/dashboard?variant=${variant}`,
            );

            set({
                ...(variant === "seller"
                    ? {
                          sellerStats: summary.stats || [],
                          sellerDashboardHighlights: summary.highlights || [],
                          sellerOrders: summary.orders || [],
                          sellerMessages: summary.messages || [],
                          sellerChartData: summary.chartData || [],
                          sellerPipeline: summary.pipeline || [],
                          sellerServices: summary.sellerServices || [],
                      }
                    : {
                          stats: summary.stats || [],
                          dashboardHighlights: summary.highlights || [],
                          orders: summary.orders || [],
                          messages: summary.messages || [],
                          chartData: summary.chartData || [],
                          recommendedServices:
                              summary.recommendedServices || [],
                      }),
                isLoading: false,
            });

            return summary;
        } catch (error) {
            set({ error: error.message, isLoading: false });
            return null;
        }
    },

    fetchSellerEarnings: async () => {
        try {
            const finance = await apiRequest("/api/seller/earnings");
            set({
                sellerFinance: finance,
                sellerChartData: finance.chartData || [],
            });
            return finance;
        } catch (error) {
            set({ error: error.message });
            return get().sellerFinance;
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
            throw error;
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
            throw error;
        }
    },

    changeSellerServiceStatus: async (id, action) => {
        const service = await apiRequest(`/api/seller/services/${id}/status`, {
            method: "PATCH",
            body: { action },
        });

        set((state) => ({
            sellerServices: upsertById(state.sellerServices, service),
        }));

        return service;
    },

    deleteSellerService: async (id) => {
        await apiRequest(`/api/seller/services/${id}`, {
            method: "DELETE",
        });

        set((state) => ({
            sellerServices: state.sellerServices.filter(
                (service) => service.id !== id,
            ),
        }));
    },

    fetchOrders: async (role = "buyer") => {
        try {
            const orders = await apiRequest(`/api/orders?role=${role}`);

            set(role === "seller" ? { sellerOrders: orders } : { orders });

            return orders;
        } catch (error) {
            set({ error: error.message });
            return role === "seller" ? get().sellerOrders : get().orders;
        }
    },

    fetchConversations: async (filter = "all") => {
        set({ isConversationsLoading: true, error: null });

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
                isConversationsLoading: false,
            });

            return threads;
        } catch (error) {
            set({ error: error.message, isConversationsLoading: false });
            return get().buyerMessageThreads;
        }
    },

    fetchSavedMessages: async (conversationId) => {
        if (!conversationId) return [];

        return (
            await apiRequest(
                `/api/conversations/${conversationId}/saved-messages`,
            )
        ).map(normalizeMessage);
    },

    saveMessage: async (messageId) => {
        const message = normalizeMessage(
            await apiRequest(`/api/messages/${messageId}/save`, {
                method: "POST",
                body: {},
            }),
        );

        set((state) => replaceThreadMessageState(state, message));
        return message;
    },

    unsaveMessage: async (messageId) => {
        await apiRequest(`/api/messages/${messageId}/save`, {
            method: "DELETE",
        });

        set((state) =>
            replaceThreadMessageState(state, {
                id: messageId,
                saved: false,
            }),
        );
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
        const message = normalizeMessage(
            await apiRequest(`/api/conversations/${conversationId}/messages`, {
                body: { text, clientId },
            }),
        );

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

    fetchCustomOfferOptions: async (conversationId) => {
        if (!conversationId) return [];

        return apiRequest(
            `/api/conversations/${conversationId}/custom-offers/options`,
        );
    },

    createCustomOffer: async (conversationId, payload) => {
        const response = await apiRequest(
            `/api/conversations/${conversationId}/custom-offers`,
            { body: payload },
        );

        if (response?.conversation) {
            set((state) =>
                updateConversationState(
                    state,
                    normalizeConversation(response.conversation),
                ),
            );
        }

        return response;
    },

    acceptCustomOffer: async (offerId) =>
        get().updateCustomOfferAction(offerId, "accept"),

    declineCustomOffer: async (offerId) =>
        get().updateCustomOfferAction(offerId, "decline"),

    cancelCustomOffer: async (offerId) =>
        get().updateCustomOfferAction(offerId, "cancel"),

    payCustomOffer: async (offerId) =>
        get().updateCustomOfferAction(offerId, "pay"),

    updateCustomOfferAction: async (offerId, action) => {
        const response = await apiRequest(`/api/custom-offers/${offerId}/${action}`, {
            body: {},
        });

        if (response?.conversation) {
            set((state) =>
                updateConversationState(
                    state,
                    normalizeConversation(response.conversation),
                ),
            );
        }

        return response;
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

    applyRealtimeNotification: (notification) => {
        if (!notification?.id) return;

        set((state) => ({
            buyerNotifications: upsertById(
                state.buyerNotifications,
                notification,
            ),
            sellerNotifications: upsertById(
                state.sellerNotifications,
                notification,
            ),
        }));
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
        conversation?.counterpart?.name || conversation?.name || "Conversation";

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
            conversation?.preview || messages[messages.length - 1]?.text || "",
        messages,
        context: conversation?.context || {},
        counterpart: conversation?.counterpart || null,
        viewerParticipant: conversation?.viewerParticipant || null,
        participants: conversation?.participants || [],
        attachments: conversation?.attachments || [],
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
        customOffer: message?.customOffer || null,
        saved: Boolean(message?.saved),
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
    if (!notifications.some((item) => item.id === notification.id)) {
        return [notification, ...notifications];
    }

    return notifications.map((item) =>
        item.id === notification.id ? notification : item,
    );
}

function replaceThreadMessageState(state, message) {
    const replaceMessage = (thread) => ({
        ...thread,
        messages: (thread.messages || []).map((item) =>
            item.id === message.id ? { ...item, ...message } : item,
        ),
    });
    const buyerMessageThreads = state.buyerMessageThreads.map(replaceMessage);
    const sellerMessageThreads = state.sellerMessageThreads.map(replaceMessage);

    return {
        buyerMessageThreads,
        sellerMessageThreads,
        messages: summarizeThreads(buyerMessageThreads),
        sellerMessages: summarizeThreads(sellerMessageThreads),
    };
}

function normalizeSellerService(draft, services, existingService = null) {
    const basicPackage = draft.packages?.[0] || {};
    const title =
        draft.title?.trim() || existingService?.title || "Untitled Gig";
    const category =
        draft.category?.trim() ||
        existingService?.category ||
        "Programming & Tech";

    return {
        id: existingService?.id || createUniqueServiceId(title, services || []),
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
        statusKey: existingService?.statusKey || "live",
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
