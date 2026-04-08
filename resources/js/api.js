import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/api',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

const TOKEN_KEY = 'auth_token';

export function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token) {
    if (token) {
        localStorage.setItem(TOKEN_KEY, token);
        api.defaults.headers.common.Authorization = `Bearer ${token}`;
    } else {
        localStorage.removeItem(TOKEN_KEY);
        delete api.defaults.headers.common.Authorization;
    }
}

export function initApiAuth() {
    const t = getToken();
    if (t) {
        api.defaults.headers.common.Authorization = `Bearer ${t}`;
    }
}

export function setApiLocale(locale) {
    api.defaults.headers.common['X-Locale'] = locale;
}

export default api;
