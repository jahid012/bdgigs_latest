import { create } from "zustand";
import { apiRequest } from "../api/apiClient.js";

export const useSessionStore = create((set) => ({
    currentUser: null,
    error: null,
    hasHydrated: false,
    isLoading: false,

    setCurrentUser: (currentUser) => set({ currentUser }),

    hydrateSession: async () => {
        set({ isLoading: true, error: null });

        try {
            const user = await apiRequest("/api/me");

            if (user.authenticated) {
                set({
                    currentUser: user,
                    hasHydrated: true,
                    isLoading: false,
                });
                return user;
            }

            set({ currentUser: null, hasHydrated: true, isLoading: false });
            return null;
        } catch (error) {
            set({
                currentUser: null,
                error: error.message,
                hasHydrated: true,
                isLoading: false,
            });
            return null;
        }
    },

    login: async (credentials) => {
        set({ isLoading: true, error: null });

        try {
            const user = await apiRequest("/api/auth/login", {
                body: credentials,
            });

            set({ currentUser: user, hasHydrated: true, isLoading: false });
            return user;
        } catch (error) {
            set({ error: error.message, isLoading: false });
            throw error;
        }
    },

    register: async (credentials) => {
        set({ isLoading: true, error: null });

        try {
            const user = await apiRequest("/api/auth/register", {
                body: credentials,
            });

            set({ currentUser: user, hasHydrated: true, isLoading: false });
            return user;
        } catch (error) {
            set({ error: error.message, isLoading: false });
            throw error;
        }
    },

    logout: async () => {
        await apiRequest("/api/auth/logout", { method: "POST" });
        set({
            currentUser: null,
            hasHydrated: true,
        });
    },
}));
