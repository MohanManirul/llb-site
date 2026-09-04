import axios from 'axios';

const api = axios.create({
    baseURL: '/v1',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
    },
});

export const tokenStore = {
    get: (): string | null => localStorage.getItem('token'),
    set: (token: string): void => localStorage.setItem('token', token),
    clear: (): void => localStorage.removeItem('token'),
};

api.interceptors.request.use((config) => {
    const token = tokenStore.get();
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default api;
