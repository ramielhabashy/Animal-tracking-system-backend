import { getAuthUser, getAuthToken, getUserRole, getLocale } from './cookies';

export const getStoredLocale = getLocale;
export const setStoredLocale = (locale) => {
  if (typeof localStorage !== 'undefined') {
    localStorage.setItem('oasis_locale', locale);
  }
};

export const getAuthHeaders = () => {
  const user = getAuthUser();
  const token = getAuthToken();
  const userRole = getUserRole();
  const locale = getStoredLocale();
  
  const headers = {
    'Accept': 'application/json',
    'Accept-Language': locale,
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  if (user) {
    if (user && user.id) {
      headers['X-User-Id'] = String(user.id);
      headers['X-User-Role'] = userRole || user?.role || 'Owner';
    }
  }

  return headers;
};

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8050';

export const getApiBase = () => API_BASE;

export const apiFetch = async (url, options = {}) => {
  const headers = getAuthHeaders();
  const fullUrl = url.startsWith('http') ? url : `${API_BASE}${url}`;
  const isJsonBody = options.body && !(options.body instanceof FormData) && typeof options.body === 'string';
  
  const fetchOptions = {
    ...options,
    headers: {
      ...headers,
      ...(isJsonBody ? { 'Content-Type': 'application/json' } : {}),
      ...(options.headers || {}),
    },
  };
  
  if (options.body instanceof FormData) {
    delete fetchOptions.headers['Content-Type'];
  }
  
  return fetch(fullUrl, fetchOptions);
};

const api = {
  get: async (url, options = {}) => apiFetch(url, { method: 'GET', ...options }),
  post: async (url, body, options = {}) => apiFetch(url, { method: 'POST', body: JSON.stringify(body), ...options }),
  put: async (url, body, options = {}) => apiFetch(url, { method: 'PUT', body: JSON.stringify(body), ...options }),
  delete: async (url, options = {}) => apiFetch(url, { method: 'DELETE', ...options }),
};

export default api;
