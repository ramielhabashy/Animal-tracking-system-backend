import { getAuthUser, getAuthToken } from './storage';

export const getAuthHeaders = () => {
  const user = getAuthUser();
  const token = getAuthToken();
  const headers = {
    'Accept': 'application/json',
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
    console.log('Token found, adding auth header:', token.substring(0, 20) + '...');
  } else {
    console.log('No token found in storage');
  }

  if (user) {
    if (user && user.id) {
      headers['X-User-Id'] = String(user.id);
      headers['X-User-Role'] = user.role || 'Owner';
    }
  }

  return headers;
};

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8050';

export const getApiBase = () => API_BASE;

export const apiFetch = async (url, options = {}) => {
  const headers = getAuthHeaders();
  const fullUrl = url.startsWith('http') ? url : `${API_BASE}${url}`;
  
  const fetchOptions = {
    ...options,
    headers: {
      ...headers,
    },
  };
  
  if (options.body instanceof FormData) {
    delete fetchOptions.headers['Content-Type'];
  } else if (options.headers) {
    fetchOptions.headers = {
      ...fetchOptions.headers,
      ...options.headers,
    };
  }
  
  return fetch(fullUrl, fetchOptions);
};
