import { apiRequest } from "./apiClient.js";

export async function uploadProfileAvatar(file) {
    const payload = new FormData();
    payload.append("avatar", file);

    return apiRequest("/api/user/avatar", {
        method: "POST",
        body: payload,
    });
}
