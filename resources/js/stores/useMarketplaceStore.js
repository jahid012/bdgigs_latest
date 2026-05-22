import { create } from "zustand";
import { apiRequest } from "../api/apiClient.js";
import {
    deliveryOptions,
    listingFilterGroups,
    listingSortOptions,
    websiteCategoryPage,
} from "../data/gigListingData.js";

const deepClone = (value) => JSON.parse(JSON.stringify(value));

export const useMarketplaceStore = create((set, get) => ({
    error: null,
    isLoading: false,
    deliveryOptions: deepClone(deliveryOptions),
    listingFilterGroups: deepClone(listingFilterGroups),
    listingGigs: [],
    gigsById: {},
    listingSortOptions: deepClone(listingSortOptions),
    savedServices: [],
    websiteCategoryPage: deepClone(websiteCategoryPage),

    fetchGigs: async () => {
        set({ isLoading: true, error: null });

        try {
            const gigs = await apiRequest("/api/gigs");
            set({
                listingGigs: gigs,
                gigsById: indexById(gigs),
                isLoading: false,
            });
            return gigs;
        } catch (error) {
            set({ error: error.message, isLoading: false });
            return get().listingGigs;
        }
    },

    fetchGig: async (id) => {
        try {
            const gig = await apiRequest(`/api/gigs/${id}`);
            set((state) => ({
                listingGigs: upsertById(state.listingGigs, gig),
                gigsById: {
                    ...state.gigsById,
                    [gig.id]: gig,
                },
            }));
            return gig;
        } catch (error) {
            set({ error: error.message });
            return (
                get().gigsById[id] ||
                get().listingGigs.find((gig) => gig.id === id) ||
                null
            );
        }
    },

    fetchSavedServices: async () => {
        try {
            const services = await apiRequest("/api/saved-services");
            set((state) => ({
                savedServices: services,
                ...updateSavedGigState(
                    state,
                    services.map((service) => service.id),
                    true,
                ),
            }));
            return services;
        } catch (error) {
            set({ error: error.message });
            return get().savedServices;
        }
    },

    saveService: async (gigId) => {
        const service = await apiRequest(`/api/saved-services/${gigId}`, {
            method: "POST",
        });
        set((state) => ({
            savedServices: upsertById(state.savedServices, service),
            ...updateSavedGigState(state, [gigId], true),
        }));
        return service;
    },

    removeSavedService: async (gigId) => {
        await apiRequest(`/api/saved-services/${gigId}`, {
            method: "DELETE",
        });
        set((state) => ({
            savedServices: state.savedServices.filter(
                (service) => service.id !== gigId,
            ),
            ...updateSavedGigState(state, [gigId], false),
        }));
    },

    toggleSavedService: async (gig) => {
        if (gig.saved) {
            await get().removeSavedService(gig.id);
            return false;
        }

        await get().saveService(gig.id);
        return true;
    },
}));

function indexById(items) {
    return items.reduce((indexed, item) => {
        indexed[item.id] = item;
        return indexed;
    }, {});
}

function upsertById(items, item) {
    const exists = items.some((current) => current.id === item.id);

    if (!exists) return [item, ...items];

    return items.map((current) => (current.id === item.id ? item : current));
}

function updateSavedGigState(state, gigIds, saved) {
    const ids = new Set(gigIds);
    const listingGigs = state.listingGigs.map((gig) =>
        ids.has(gig.id) ? { ...gig, saved } : gig,
    );
    const gigsById = Object.fromEntries(
        Object.entries(state.gigsById).map(([id, gig]) => [
            id,
            ids.has(id) ? { ...gig, saved } : gig,
        ]),
    );

    return { listingGigs, gigsById };
}
