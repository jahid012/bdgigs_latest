export class ApiError extends Error {
    constructor(message, response, payload) {
        super(message);
        this.name = "ApiError";
        this.response = response;
        this.payload = payload;
        this.status = response?.status;
    }
}

let csrfToken = null;
const pendingRequests = new Map();

function requestKey(path, method, body) {
    if (method === "GET") {
        return `${method}:${path}`;
    }

    return `${method}:${path}:${JSON.stringify(body ?? null)}`;
}

export async function apiRequest(path, options = {}) {
    const {
        body,
        headers = {},
        method = body ? "POST" : "GET",
        raw = false,
    } = options;
    const isFormData = body instanceof FormData;
    const requestId = requestKey(path, method, body);

    if (method === "GET" && pendingRequests.has(requestId)) {
        return pendingRequests.get(requestId);
    }

    const requestPromise = (async () => {
        const response = await fetch(path, {
            method,
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                ...csrfHeader(),
                ...(body && !isFormData ? { "Content-Type": "application/json" } : {}),
                ...headers,
            },
            body: body ? (isFormData ? body : JSON.stringify(body)) : undefined,
        });

        if (response.status === 204) {
            return null;
        }

        const payload = await parseJson(response);

        rememberCsrfToken(payload?.csrfToken || payload?.data?.csrfToken);

        if (!response.ok) {
            throw new ApiError(
                payload?.message || "Something went wrong.",
                response,
                payload,
            );
        }

        return raw ? payload : payload?.data ?? payload;
    })();

    if (method === "GET") {
        pendingRequests.set(requestId, requestPromise);
    }

    try {
        return await requestPromise;
    } finally {
        if (method === "GET") {
            pendingRequests.delete(requestId);
        }
    }
}

function csrfHeader() {
    const token =
        csrfToken ||
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

    return token ? { "X-CSRF-TOKEN": token } : {};
}

function rememberCsrfToken(token) {
    if (!token) return;

    csrfToken = token;
    document
        .querySelector('meta[name="csrf-token"]')
        ?.setAttribute("content", token);
}

async function parseJson(response) {
    const text = await response.text();

    if (!text) return null;

    try {
        return JSON.parse(text);
    } catch {
        return { message: text };
    }
}
