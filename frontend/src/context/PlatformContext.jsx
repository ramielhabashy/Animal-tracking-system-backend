import { createContext, useContext, useState, useEffect } from 'react';

const PlatformContext = createContext();

export function PlatformProvider({ children }) {
  const [platformName, setPlatformName] = useState('The Oasis');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8050';
    const fetchPlatformName = async () => {
      try {
        const res = await fetch(`${API_BASE}/api/admin/settings/general`);
        if (res.ok) {
          const data = await res.json();
          if (data.data?.platform_name) {
            setPlatformName(data.data.platform_name);
            document.title = data.data.platform_name;
          }
        }
      } catch (error) {
        console.error('Failed to fetch platform name:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchPlatformName();
  }, []);

  const refreshPlatformName = async () => {
    const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8050';
    try {
      const res = await fetch(`${API_BASE}/api/admin/settings/general`);
      if (res.ok) {
        const data = await res.json();
        if (data.data?.platform_name) {
          setPlatformName(data.data.platform_name);
          document.title = data.data.platform_name;
        }
      }
    } catch (error) {
      console.error('Failed to refresh platform name:', error);
    }
  };

  return (
    <PlatformContext.Provider value={{ platformName, loading, refreshPlatformName }}>
      {children}
    </PlatformContext.Provider>
  );
}

export function usePlatform() {
  const context = useContext(PlatformContext);
  if (!context) {
    throw new Error('usePlatform must be used within a PlatformProvider');
  }
  return context;
}
