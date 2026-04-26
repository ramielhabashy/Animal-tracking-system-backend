import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { getLocale, setLocale as setStoredLocale } from '../utils/cookies';
import en from './en';
import ar from './ar';
import ur from './ur';
import eu from './eu';

const localTranslations = { en, ar, ur, eu };

const I18nContext = createContext({
  locale: 'en',
  dir: 'ltr',
  t: (key) => key,
});

function getNestedValue(obj, path) {
  const keys = path.split('.');
  let value = obj;
  for (const k of keys) {
    if (value && typeof value === 'object' && k in value) {
      value = value[k];
    } else {
      return null;
    }
  }
  return value;
}

function transformFlatTranslations(data) {
  const nested = {};
  if (Array.isArray(data)) {
    data.forEach(item => {
      if (!nested[item.language_code]) nested[item.language_code] = {};
      nested[item.language_code][item.key] = item.value;
    });
  }
  return nested;
}

export function I18nProvider({ children }) {
  const [locale, setLocaleState] = useState(() => getLocale() || 'en');
  const [dir, setDir] = useState('ltr');
  const [apiTranslations, setApiTranslations] = useState({});
  const [languages, setLanguages] = useState([
    { code: 'en', name: 'English', native_name: 'English', direction: 'ltr', is_active: true },
    { code: 'ar', name: 'Arabic', native_name: 'العربية', direction: 'rtl', is_active: true },
    { code: 'ur', name: 'Urdu', native_name: 'اردو', direction: 'rtl', is_active: true },
    { code: 'eu', name: 'Basque', native_name: 'Euskara', direction: 'ltr', is_active: true },
  ]);

  useEffect(() => {
    setDir(locale === 'ar' || locale === 'ur' ? 'rtl' : 'ltr');
  }, [locale]);

  useEffect(() => {
    const loadLanguages = async () => {
      try {
        const res = await fetch('http://localhost:8050/api/languages');
        if (res.ok) {
          const data = await res.json();
          setLanguages(data.filter(lang => lang.is_active));
        }
      } catch (e) {
        console.warn('Failed to load languages:', e.message);
      }
    };
    loadLanguages();
  }, []);

  useEffect(() => {
    const loadTranslations = async () => {
      try {
        const res = await fetch('http://localhost:8050/api/translations');
        const data = await res.json();
        const nested = transformFlatTranslations(data);
        setApiTranslations(nested);
      } catch (e) {
        console.warn('Failed to load translations:', e.message);
      }
    };
    loadTranslations();
  }, []);

  useEffect(() => {
    document.documentElement.dir = dir;
    document.documentElement.lang = locale;
  }, [locale, dir]);

  const setLocale = useCallback((newLocale) => {
    setStoredLocale(newLocale);
    setLocaleState(newLocale);
  }, []);

  const t = useCallback((key, params = {}) => {
    // First try API translations
    let value = getNestedValue(apiTranslations[locale], key);
    // Then try local translations
    if (!value) value = getNestedValue(localTranslations[locale], key);
    // Fallback to English
    if (!value) value = getNestedValue(localTranslations['en'], key);
    // Fallback to key
    if (typeof value !== 'string') return key;
    // Replace params
    if (Object.keys(params).length > 0) {
      Object.entries(params).forEach(([param, val]) => {
        value = value.replace(new RegExp(`\\{${param}\\}`, 'g'), val);
      });
    }
    return value;
  }, [locale, apiTranslations]);

  return (
    <I18nContext.Provider value={{ locale, dir, t, setLocale, languages }}>
      {children}
    </I18nContext.Provider>
  );
}

export const useI18n = () => useContext(I18nContext);
export default I18nContext;