import { create } from "zustand";
import { apiRequest } from "../api/apiClient.js";

export const useSessionStore = create((set, get) => ({
    currentUser: null,
    error: null,
    hasHydrated: false,
    isLoading: false,
    sessionHydrating: false,

    setCurrentUser: (currentUser) => set({ currentUser }),
    hydrateFromBootstrap: (session) => {
        const currentUser = session?.authenticated ? session : null;

        set({
            currentUser,
            error: null,
            hasHydrated: true,
            isLoading: false,
            sessionHydrating: false,
        });

        return currentUser;
    },

    hydrateSession: async () => {
        const state = get();

        if (state.sessionHydrating || state.hasHydrated) {
            return state.currentUser;
        }

        set({ sessionHydrating: true, isLoading: true, error: null });

        try {
            const user = await apiRequest("/api/me");

            if (user.authenticated) {
                set({
                    currentUser: user,
                    hasHydrated: true,
                    isLoading: false,
                    sessionHydrating: false,
                });
                return user;
            }

            set({
                currentUser: null,
                hasHydrated: true,
                isLoading: false,
                sessionHydrating: false,
            });
            return null;
        } catch (error) {
            set({
                currentUser: null,
                error: error.message,
                hasHydrated: true,
                isLoading: false,
                sessionHydrating: false,
            });
            return null;
        }
    },

    login: async (credentials) => {
        set({ isLoading: true, error: null });

        try {
            const response = await apiRequest("/login", {
                body: credentials,
            });

            if (response?.two_factor || response?.twoFactor) {
                set({ isLoading: false });
                return { requiresTwoFactor: true };
            }

            const user = await apiRequest("/api/me");
            set({ currentUser: user, hasHydrated: true, isLoading: false });
            return user;
        } catch (error) {
            set({ error: error.message, isLoading: false });
            throw error;
        }
    },

    completeTwoFactor: async ({ code, recoveryCode }) => {
        set({ isLoading: true, error: null });

        try {
            await apiRequest("/two-factor-challenge", {
                body: {
                    code: code || undefined,
                    recovery_code: recoveryCode || undefined,
                },
            });
            const user = await apiRequest("/api/me");
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
