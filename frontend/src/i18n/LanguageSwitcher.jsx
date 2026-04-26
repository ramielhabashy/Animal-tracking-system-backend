import React from 'react';
import { MaterialSymbol } from 'react-material-symbols';
import { useI18n } from './index';
import { setStoredLocale } from '../utils/api';

export default function LanguageSwitcher() {
  const { locale, setLocale, languages } = useI18n();

  const handleChange = (e) => {
    const newLocale = e.target.value;
    setStoredLocale(newLocale);
    setLocale(newLocale);
  };

  const currentLang = languages.find(l => l.code === locale);

  return (
    <div className="flex items-center gap-2">
      <select
        value={locale}
        onChange={handleChange}
        className="px-3 py-2 rounded-xl bg-[#F4F4EF] hover:bg-[#E3E3DE] transition-all text-[#002819] font-semibold text-sm border-none cursor-pointer"
        title={locale === 'en' ? 'Switch language' : 'تغيير اللغة'}
      >
        {languages.map(lang => (
          <option key={lang.code} value={lang.code}>
            {lang.native_name || lang.name}
          </option>
        ))}
      </select>
      {currentLang?.direction === 'rtl' && (
        <span className="text-xs font-bold text-[#06402B]">RTL</span>
      )}
    </div>
  );
}
