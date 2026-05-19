import React, { useState } from 'react';
import { apiFetch } from '../../utils/api';
import { useI18n } from '../../i18n';
import { InputField, ToggleSwitch, SaveButton } from './index';

export default function WhatsAppSettings({ dir, message, setMessage, saving, setSaving }) {
  const { t } = useI18n();

  const [whatsappSettings, setWhatsappSettings] = useState({
    api_url: '',
    api_token: '',
    phone_number_id: '',
    business_account_id: '',
    enabled: false,
  });

  const handleSaveWhatsApp = async () => {
    setSaving(true);
    setMessage(null);
    try {
      const res = await apiFetch('/api/admin/settings/whatsapp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(whatsappSettings),
      });
      if (res.ok) {
        setMessage({ type: 'success', text: t('settings.saved') });
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
    <div className="bg-white rounded-[2rem] p-8 shadow-sm border border-[#eeeee9]">
      <div className="flex items-center gap-3 mb-6">
        <div className="w-12 h-12 bg-[#25D366] rounded-xl flex items-center justify-center shadow-sm">
          <svg className="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.296-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
          </svg>
        </div>
        <div>
          <h3 className="text-xl font-bold text-[#002819]">{t('settings.whatsappSettings')}</h3>
          <p className="text-sm text-[#717973]">{t('settings.whatsappDescription')}</p>
        </div>
      </div>

      <ToggleSwitch
        checked={whatsappSettings.enabled}
        onChange={(e) => setWhatsappSettings({ ...whatsappSettings, enabled: e.target.checked })}
        label={t('settings.enableWhatsapp')}
      />

      <div className="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="lg:col-span-2">
          <InputField
            label={t('settings.whatsappApiUrl')}
            value={whatsappSettings.api_url}
            onChange={(e) => setWhatsappSettings({ ...whatsappSettings, api_url: e.target.value })}
            placeholder="https://graph.facebook.com/v18.0"
          />
        </div>
        <div className="lg:col-span-2">
          <InputField
            label={t('settings.whatsappApiToken')}
            type="password"
            value={whatsappSettings.api_token}
            onChange={(e) => setWhatsappSettings({ ...whatsappSettings, api_token: e.target.value })}
            placeholder="EAAxxxx..."
          />
        </div>
        <InputField
          label={t('settings.whatsappPhoneId')}
          value={whatsappSettings.phone_number_id}
          onChange={(e) => setWhatsappSettings({ ...whatsappSettings, phone_number_id: e.target.value })}
          placeholder="Phone Number ID"
        />
        <InputField
          label={t('settings.whatsappBusinessId')}
          value={whatsappSettings.business_account_id}
          onChange={(e) => setWhatsappSettings({ ...whatsappSettings, business_account_id: e.target.value })}
          placeholder="Business Account ID"
        />
      </div>

      <div className="mt-6">
        <SaveButton onClick={handleSaveWhatsApp} saving={saving} color="#25D366" />
      </div>
    </div>
  );
}
