import { createContext, useContext, useState, useEffect } from 'react';
import { apiFetch } from '../utils/api';
import { getAuthUser, setAuthUser, setAuthToken, setUserId, setUserRole, clearAuth } from '../utils/storage';

const AuthContext = createContext({ user: null, isAuthenticated: false, login: async () => false, logout: () => {} });

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => getAuthUser());
  const [isAuthenticated, setIsAuthenticated] = useState(() => getAuthUser() !== null);

  useEffect(() => {
    if (user) {
      setAuthUser(user);
    } else {
      clearAuth();
    }
  }, [user]);

  const login = async (email, password) => {
    try {
      const response = await apiFetch('/api/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
      });

      if (response.ok) {
        const data = await response.json();
        if (data.user) {
          setUser(data.user);
          setAuthUser(data.user);
          setUserId(data.user.id);
          setUserRole(data.user.role);
        } else {
          const defaultUser = { id: 0, email, name: 'User', role: 'Admin', phone: null };
          setUser(defaultUser);
          setAuthUser(defaultUser);
          setUserId(0);
          setUserRole('Admin');
        }
        if (data.token) {
          setAuthToken(data.token);
        }
        setIsAuthenticated(true);
        return true;
      }
    } catch (error) {
      console.error('Login error:', error);
    }
    return false;
  };

  const logout = async () => {
    try {
      await apiFetch('/api/auth/logout', { method: 'POST' });
    } catch (error) {
      console.error('Logout error:', error);
    }
    setUser(null);
    setIsAuthenticated(false);
    clearAuth();
  };

  return (
    <AuthContext.Provider value={{ user, isAuthenticated, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);