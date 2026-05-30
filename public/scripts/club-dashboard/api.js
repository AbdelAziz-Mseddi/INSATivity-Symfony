import { API } from '../api.js';

export async function fetchClubById(clubId) {
    return API.getClubById(clubId);
}

export async function fetchAllEvents() {
    return API.getEvents();
}

export async function uploadCoverImage(file, prefix) {
    const data = await API.uploadMedia(file, prefix);
    return data.path;
}

export async function createEvent(payload) {
    return API.createEvent(payload);
}

export async function approveEvent(eventId) {
    return API.request(`/api/events/${eventId}/approve`, {
        method: 'PATCH',
        headers: API.getHeaders()
    });
}

export async function reviewEvent(eventId, payload) {
    return API.request(`/api/events/${eventId}/review`, {
        method: 'POST',
        headers: API.getHeaders(),
        body: JSON.stringify(payload)
    });
}

export async function submitEventFeedback(eventId, payload) {
    return API.request(`/api/events/${eventId}/feedback`, {
        method: 'POST',
        headers: API.getHeaders(),
        body: JSON.stringify(payload)
    });
}

export async function deleteEvent(eventId) {
    return API.deleteEvent(eventId);
}
