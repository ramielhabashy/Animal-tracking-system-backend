import React from 'react';
import { useState, useEffect } from 'react';
import { useI18n } from '../i18n';
import api from '../utils/api';

export default function LanguageSettingsPage() {
  const { t, locale } = useI18n();
  const [languages, setLanguages] = useState([]);
  const [translations, setTranslations] = useState([]);
  const [activeTab, setActiveTab] = useState('languages');
  const [selectedLang, setSelectedLang] = useState('en');
  const [selectedGroup, setSelectedGroup] = useState('common');
  const [loading, setLoading] = useState(false);
  const [editingLang, setEditingLang] = useState(null);
  const [formData, setFormData] = useState({ code: '', name: '', native_name: '', direction: 'ltr' });

  const groups = ['common', 'dashboard', 'animals', 'devices', 'geofences', 'alerts', 'tasks', 'auctions', 'profile', 'settings'];

  useEffect(() => {
    loadLanguages();
  }, []);

  useEffect(() => {
    if (activeTab === 'translations') {
      loadTranslations();
    }
  }, [activeTab, selectedLang, selectedGroup]);

  const loadLanguages = async () => {
    try {
      const res = await api.get('/admin/languages');
      setLanguages(res.data);
    } catch (err) {
      console.error('Failed to load languages:', err);
    }
  };

  const loadTranslations = async () => {
    try {
      const res = await api.get('/translations', { params: { group: selectedGroup, lang: selectedLang } });
      setTranslations(res.data);
    } catch (err) {
      console.error('Failed to load translations:', err);
    }
  };

  const handleSaveLanguage = async () => {
    setLoading(true);
    try {
      if (editingLang) {
        await api.put(`/admin/languages/${editingLang}`, formData);
      } else {
        await api.post('/admin/languages', formData);
      }
      setEditingLang(null);
      setFormData({ code: '', name: '', native_name: '', direction: 'ltr' });
      loadLanguages();
    } catch (err) {
      alert(err.response?.data?.error || 'Failed to save language');
    }
    setLoading(false);
  };

  const handleDeleteLanguage = async (code) => {
    if (!confirm('Are you sure? This will also delete all translations.')) return;
    try {
      await api.delete(`/admin/languages/${code}`);
      loadLanguages();
    } catch (err) {
      alert(err.response?.data?.error || 'Failed to delete language');
    }
  };

  const handleSetDefault = async (code) => {
    try {
      await api.post(`/admin/languages/${code}/set-default`);
      loadLanguages();
    } catch (err) {
      alert(err.response?.data?.error || 'Failed to set default');
    }
  };

  const handleToggleActive = async (lang) => {
    try {
      await api.put(`/admin/languages/${lang.code}`, { is_active: !lang.is_active });
      loadLanguages();
    } catch (err) {
      alert(err.response?.data?.error || 'Failed to update');
    }
  };

  const handleSaveTranslation = async (id, value) => {
    try {
      await api.put(`/admin/translations/${id}`, { value });
      loadTranslations();
    } catch (err) {
      alert('Failed to save translation');
    }
  };

  const handleImportTranslations = async () => {
    setLoading(true);
    try {
      const imported = translations.map(t => ({
        language_code: selectedLang,
        group: selectedGroup,
        key: t.key,
        value: t.value
      }));
      await api.post('/admin/translations/import', { translations: imported });
      loadTranslations();
    } catch (err) {
      alert('Failed to import');
    }
    setLoading(false);
  };

  return (
    <div className="p-6 max-w-6xl mx-auto">
      <h1 className="text-2xl font-bold mb-6">{t('settings.languageManagement') || 'Language Management'}</h1>

      <div className="flex gap-4 mb-6 border-b">
        <button
          className={`pb-2 px-4 ${activeTab === 'languages' ? 'border-b-2 border-green-600 text-green-600' : 'text-gray-500'}`}
          onClick={() => setActiveTab('languages')}
        >
          {t('languages') || 'Languages'}
        </button>
        <button
          className={`pb-2 px-4 ${activeTab === 'translations' ? 'border-b-2 border-green-600 text-green-600' : 'text-gray-500'}`}
          onClick={() => setActiveTab('translations')}
        >
          {t('translations') || 'Translations'}
        </button>
      </div>

      {activeTab === 'languages' && (
        <div>
          <div className="bg-white p-4 rounded-lg shadow mb-6">
            <h2 className="font-semibold mb-4">{editingLang ? t('edit') : t('add')} {t('language') || 'Language'}</h2>
            <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
              <input
                type="text"
                placeholder={t('code') || 'Code (e.g. en)'}
                value={formData.code}
                onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                disabled={!!editingLang}
                className="border rounded px-3 py-2"
                maxLength={3}
              />
              <input
                type="text"
                placeholder={t('name') || 'Name'}
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                className="border rounded px-3 py-2"
              />
              <input
                type="text"
                placeholder={t('nativeName') || 'Native Name'}
                value={formData.native_name}
                onChange={(e) => setFormData({ ...formData, native_name: e.target.value })}
                className="border rounded px-3 py-2"
              />
              <select
                value={formData.direction}
                onChange={(e) => setFormData({ ...formData, direction: e.target.value })}
                className="border rounded px-3 py-2"
              >
                <option value="ltr">LTR</option>
                <option value="rtl">RTL</option>
              </select>
              <div className="flex gap-2">
                <button
                  onClick={handleSaveLanguage}
                  disabled={loading}
                  className="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 disabled:opacity-50"
                >
                  {t('save')}
                </button>
                {editingLang && (
                  <button
                    onClick={() => { setEditingLang(null); setFormData({ code: '', name: '', native_name: '', direction: 'ltr' }); }}
                    className="bg-gray-500 text-white px-4 py-2 rounded"
                  >
                    {t('cancel')}
                  </button>
                )}
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-left">{t('code')}</th>
                  <th className="px-4 py-3 text-left">{t('name')}</th>
                  <th className="px-4 py-3 text-left">{t('nativeName')}</th>
                  <th className="px-4 py-3 text-left">Direction</th>
                  <th className="px-4 py-3 text-left">{t('status')}</th>
                  <th className="px-4 py-3 text-right">{t('actions')}</th>
                </tr>
              </thead>
              <tbody>
                {languages.map((lang) => (
                  <tr key={lang.id} className="border-t">
                    <td className="px-4 py-3 font-mono">{lang.code}</td>
                    <td className="px-4 py-3">{lang.name}</td>
                    <td className="px-4 py-3">{lang.native_name}</td>
                    <td className="px-4 py-3">{lang.direction.toUpperCase()}</td>
                    <td className="px-4 py-3">
                      <span className={`px-2 py-1 rounded text-xs ${lang.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                        {lang.is_active ? 'Active' : 'Inactive'}
                      </span>
                      {lang.is_default && (
                        <span className="ml-2 px-2 py-1 rounded text-xs bg-blue-100 text-blue-700">
                          Default
                        </span>
                      )}
                    </td>
                    <td className="px-4 py-3 text-right">
                      <button
                        onClick={() => { setEditingLang(lang.code); setFormData(lang); }}
                        className="text-blue-600 hover:underline mr-3"
                      >
                        {t('edit')}
                      </button>
                      <button
                        onClick={() => handleToggleActive(lang)}
                        className="text-orange-600 hover:underline mr-3"
                      >
                        {lang.is_active ? t('disable') || 'Disable' : t('enable') || 'Enable'}
                      </button>
                      {!lang.is_default && (
                        <>
                          <button
                            onClick={() => handleSetDefault(lang.code)}
                            className="text-purple-600 hover:underline mr-3"
                          >
                            {t('setDefault') || 'Set Default'}
                          </button>
                          <button
                            onClick={() => handleDeleteLanguage(lang.code)}
                            className="text-red-600 hover:underline"
                          >
                            {t('delete')}
                          </button>
                        </>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {activeTab === 'translations' && (
        <div>
          <div className="flex gap-4 mb-4">
            <select
              value={selectedLang}
              onChange={(e) => setSelectedLang(e.target.value)}
              className="border rounded px-3 py-2"
            >
              {languages.map(lang => (
                <option key={lang.code} value={lang.code}>{lang.name}</option>
              ))}
            </select>
            <select
              value={selectedGroup}
              onChange={(e) => setSelectedGroup(e.target.value)}
              className="border rounded px-3 py-2"
            >
              {groups.map(group => (
                <option key={group} value={group}>{group}</option>
              ))}
            </select>
          </div>

          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-left w-1/3">Key</th>
                  <th className="px-4 py-3 text-left">Value</th>
                </tr>
              </thead>
              <tbody>
                {translations.map((trans) => (
                  <tr key={trans.id} className="border-t">
                    <td className="px-4 py-3 font-mono text-sm">{trans.key}</td>
                    <td className="px-4 py-3">
                      <input
                        type="text"
                        defaultValue={trans.value}
                        onBlur={(e) => handleSaveTranslation(trans.id, e.target.value)}
                        className="w-full border rounded px-3 py-1"
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}