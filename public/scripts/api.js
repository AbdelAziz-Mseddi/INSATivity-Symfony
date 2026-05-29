import { CONFIG } from './config.js';

class ApiClient {
    constructor() {
        this.baseURL = CONFIG.API_BASE_URL;
    }

    // Helper for Authorization Header
    getHeaders(isFormData = false) {
        const headers = {
            'X-Requested-With': 'XMLHttpRequest'  // CSRF protection header
        };
        const token = localStorage.getItem('jwt_token');
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        if (!isFormData) {
            headers['Content-Type'] = 'application/json';
        }
        return headers;
    }

    // Core Fetch Method
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        
        try {
            const response = await fetch(url, options);
            const data = await response.json();

            if (!response.ok) {
                // If token is expired or invalid, auto logout
                if (response.status === 401 && endpoint !== '/api/auth/login') {
                    this.logout();
                }
                throw new Error(data.error || 'API Request Failed');
            }

            return data.data; // Response wraps in {success: true, data: ...}
        } catch (error) {
            console.error(`API Error (${endpoint}):`, error);
            throw error;
        }
    }

    // ---- AUTHENTICATION ----
    async login(username, password) {
        const data = await this.request('/api/auth/login', {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify({ username, password })
        });
        if (data && data.token) {
            localStorage.setItem('jwt_token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
        }
        return data;
    }

    async register(userData) {
        const data = await this.request('/api/auth/register', {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify(userData)
        });
        if (data && data.token) {
            localStorage.setItem('jwt_token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
        }
        return data;
    }

    logout() {
        localStorage.removeItem('jwt_token');
        localStorage.removeItem('user');
        window.location.href = '/login'; // Redirect to login page
    }

    async getMe() {
        return this.request('/api/auth/me', {
            headers: this.getHeaders()
        });
    }

    // ---- EVENTS ----
    async getEvents() {
        return this.request('/api/events', {
            headers: this.getHeaders()
        });
    }

    async getEventById(id) {
        return this.request(`/api/events/${id}`, {
            headers: this.getHeaders()
        });
    }

    async getEventsByClub(club) {
        return this.request(`/api/events?club=${encodeURIComponent(club)}`, {
            headers: this.getHeaders()
        });
    }

    async createEvent(eventData) {
        return this.request('/api/events', {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify(eventData)
        });
    }

    async updateEvent(id, eventData) {
        return this.request(`/api/events/${id}`, {
            method: 'PUT',
            headers: this.getHeaders(),
            body: JSON.stringify(eventData)
        });
    }

    async deleteEvent(id) {
        return this.request(`/api/events/${id}`, {
            method: 'DELETE',
            headers: this.getHeaders()
        });
    }

    // ---- CLUBS ----
    async getClubs() {
        return this.request('/api/clubs', {
            headers: this.getHeaders()
        });
    }

    async getClubById(id) {
        return this.request(`/api/clubs/${id}`, {
            headers: this.getHeaders()
        });
    }

    // ---- MEDIA ----
    async uploadMedia(file, prefix = '') {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('prefix', prefix);

        return this.request('/api/media/upload', {
            method: 'POST',
            headers: this.getHeaders(true), // Don't set Content-Type, let browser handle FormData boundaries
            body: formData
        });
    }
}

export const API = new ApiClient();

// Globally bind logout links
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.logout-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                API.logout();
            });
        });
    });
}
