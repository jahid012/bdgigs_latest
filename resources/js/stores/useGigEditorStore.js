import { create } from "zustand";
import { gigEditorDraft } from "../data/gigEditorData.js";
import { useDashboardStore } from "./useDashboardStore.js";

const deepClone = (value) => JSON.parse(JSON.stringify(value));

export const useGigEditorStore = create((set) => ({
    draft: createInitialDraft(),
    editingServiceId: null,

    createGigDraft: (serviceId = null) => {
        const service = serviceId
            ? useDashboardStore.getState().getSellerServiceById(serviceId)
            : null;
        const draft = createInitialDraft(service);

        set({
            draft,
            editingServiceId: service?.id || serviceId,
        });

        return draft;
    },

    updateGigDraft: (field, value) =>
        set((state) => ({
            draft: {
                ...state.draft,
                [field]: value,
            },
        })),

    setGigDraft: (nextDraft) =>
        set((state) => ({
            draft:
                typeof nextDraft === "function"
                    ? nextDraft(state.draft)
                    : nextDraft,
        })),

    resetGigDraft: () =>
        set({
            draft: createInitialDraft(),
            editingServiceId: null,
        }),
}));

function createInitialDraft(service = null) {
    const draft = deepClone(gigEditorDraft);

    if (!service) return draft;

    const startingPrice =
        String(service.price || "").replace(/[^0-9.]/g, "") || "5";

    return {
        ...draft,
        title: service.title,
        category: service.category,
        tags: [
            ...new Set([
                service.category,
                service.tag,
                "Laravel",
                "Customization",
                "Website",
            ].filter(Boolean)),
        ].slice(0, 5),
        packages: service.packages?.length
            ? service.packages
            : draft.packages.map((item, index) =>
                  index === 0 ? { ...item, price: startingPrice } : item,
              ),
        extras: service.extras?.length ? service.extras : draft.extras,
        requirements: service.requirements?.length
            ? service.requirements
            : draft.requirements,
        galleryImages: [
            ...(service.galleryImages?.length
                ? service.galleryImages
                : [service.image]),
            ...draft.galleryImages.filter((image) => image !== service.image),
        ].slice(0, 3),
    };
}
