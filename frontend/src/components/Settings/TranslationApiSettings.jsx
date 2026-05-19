import React, { useState } from 'react';
import { apiFetch } from '../../utils/api';
import { SettingsCard, SaveButton } from './index';

export default function TranslationApiSettings({ dir, message, setMessage, saving, setSaving }) {
  const [translationSettings, setTranslationSettings] = useState({
    deepl_api_key: '',
    google_api_key: '',
  });

  const handleSaveTranslationSettings = async () => {
    setSaving(true);
    setMessage(null);
    try {
      const res = await apiFetch('/api/admin/settings/translation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(translationSettings),
      });
      if (res.ok) {
        setMessage({ type: 'success', text: 'Translation settings saved' });
      } else {
        const data = await res.json();
        setMessage({ type: 'error', text: data.message || 'Failed to save' });
      }
    } catch (error) {
      setMessage({ type: 'error', text: 'Network error' });
    } finally {
      setSaving(false);
    }
  };

  return (
    <SettingsCard icon="translate" title="Translation Settings" description="Configure API keys for on-demand translation (DeepL + Google Translate)">
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
          <label className="block text-xs font-bold text-[#404943] uppercase tracking-wider mb-2">DeepL API Key</label>
          <input
            type="password"
            value={translationSettings.deepl_api_key}
            onChange={(e) => setTranslationSettings({ ...translationSettings, deepl_api_key: e.target.value })}
            className="w-full bg-[#F4F4EF] border-none rounded-xl p-4 text-[#002819] focus:ring-2 focus:ring-[#06402B]/20"
            placeholder="DeepL API Key (en/ar/ur)"
          />
          <p className="text-xs text-[#717973] mt-1">Used for English, Arabic, Urdu translations</p>
        </div>
        <div>
          <label className="block text-xs font-bold text-[#404943] uppercase tracking-wider mb-2">Google Translate API Key</label>
          <input
            type="password"
            value={translationSettings.google_api_key}
            onChange={(e) => setTranslationSettings({ ...translationSettings, google_api_key: e.target.value })}
            className="w-full bg-[#F4F4EF] border-none rounded-xl p-4 text-[#002819] focus:ring-2 focus:ring-[#06402B]/20"
            placeholder="Google Translate API Key (all langs)"
          />
          <p className="text-xs text-[#717973] mt-1">Fallback for Basque (eu) and all other languages</p>
        </div>
      </div>

      <div className="mt-6">
        <SaveButton onClick={handleSaveTranslationSettings} saving={saving}>
          Save Translation Settings
        </SaveButton>
      </div>
    </SettingsCard>
  );
}
