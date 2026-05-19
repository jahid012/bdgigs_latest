import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";

const DEFAULT_DURATION = 4500;
const DEFAULT_TITLES = {
    success: "Success",
    error: "Something went wrong",
    warning: "Heads up",
    info: "Notice",
};
const TOAST_TYPES = ["success", "error", "warning", "info"];
const queuedToasts = [];
let activeDispatcher = null;

function normalizeToast(input, options = {}) {
    const source =
        typeof input === "string"
            ? { message: input }
            : {
                  ...(input || {}),
              };
    const type = TOAST_TYPES.includes(options.type || source.type)
        ? options.type || source.type
        : "info";

    return {
        id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
        type,
        title: options.title ?? source.title ?? DEFAULT_TITLES[type],
        message: source.message || "",
        duration: options.duration ?? source.duration ?? DEFAULT_DURATION,
        action: options.action ?? source.action,
    };
}

function dispatchToast(input, options) {
    const payload = normalizeToast(input, options);

    if (activeDispatcher) {
        activeDispatcher(payload);
        return payload.id;
    }

    queuedToasts.push(payload);
    return payload.id;
}

function createToastApi(dispatch) {
    const api = (input, options) => dispatch(input, options);

    api.success = (message, options) =>
        dispatch(message, { ...options, type: "success" });
    api.error = (message, options) =>
        dispatch(message, { ...options, type: "error" });
    api.warning = (message, options) =>
        dispatch(message, { ...options, type: "warning" });
    api.info = (message, options) =>
        dispatch(message, { ...options, type: "info" });

    return api;
}

export const toast = createToastApi(dispatchToast);
const ToastContext = createContext(toast);

function ToastItem({ item, onDismiss }) {
    useEffect(() => {
        if (item.duration <= 0) return undefined;

        const timerId = window.setTimeout(() => {
            onDismiss(item.id);
        }, item.duration);

        return () => window.clearTimeout(timerId);
    }, [item.duration, item.id, onDismiss]);

    return (
        <section
            className="bdgigs-toast"
            data-type={item.type}
            role={item.type === "error" ? "alert" : "status"}
            style={{ "--toast-duration": `${item.duration}ms` }}
        >
            <span className="bdgigs-toast-icon" aria-hidden="true"></span>
            <div className="bdgigs-toast-body">
                <p className="bdgigs-toast-title">{item.title}</p>
                {item.message ? (
                    <p className="bdgigs-toast-message">{item.message}</p>
                ) : null}
                {item.action?.label && item.action?.url ? (
                    <a className="bdgigs-toast-action" href={item.action.url}>
                        {item.action.label}
                    </a>
                ) : null}
            </div>
            <button
                className="bdgigs-toast-close"
                type="button"
                aria-label="Dismiss notification"
                onClick={() => onDismiss(item.id)}
            >
                x
            </button>
            {item.duration > 0 ? (
                <span
                    className="bdgigs-toast-progress"
                    aria-hidden="true"
                ></span>
            ) : null}
        </section>
    );
}

export function ToastProvider({ children }) {
    const [items, setItems] = useState([]);

    const dismissToast = useCallback((id) => {
        setItems((currentItems) =>
            currentItems.filter((item) => item.id !== id),
        );
    }, []);

    const pushToast = useCallback((payload) => {
        setItems((currentItems) => [...currentItems.slice(-3), payload]);
        return payload.id;
    }, []);

    const toastApi = useMemo(() => createToastApi(dispatchToast), []);

    useEffect(() => {
        activeDispatcher = pushToast;

        while (queuedToasts.length) {
            pushToast(queuedToasts.shift());
        }

        const previousNotify = window.notify;
        const previousToast = window.toast;
        window.notify = toastApi;
        window.toast = toastApi;

        return () => {
            activeDispatcher = null;

            if (window.notify === toastApi) {
                window.notify = previousNotify;
            }

            if (window.toast === toastApi) {
                window.toast = previousToast;
            }
        };
    }, [pushToast, toastApi]);

    return (
        <ToastContext.Provider value={toastApi}>
            {children}
            <div
                className="bdgigs-toast-root react-toast-root"
                aria-live="polite"
                aria-atomic="false"
            >
                {items.map((item) => (
                    <ToastItem
                        item={item}
                        key={item.id}
                        onDismiss={dismissToast}
                    />
                ))}
            </div>
        </ToastContext.Provider>
    );
}

export function useToast() {
    return useContext(ToastContext);
}
