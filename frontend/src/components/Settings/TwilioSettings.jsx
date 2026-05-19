import React, { useState } from 'react';
import { MaterialSymbol } from 'react-material-symbols';
import { apiFetch } from '../../utils/api';
import { useI18n } from '../../i18n';
import { InputField, ToggleSwitch, SaveButton } from './index';

export default function TwilioSettings({ dir, message, setMessage, saving, setSaving }) {
  const { t } = useI18n();

  const [twilioSettings, setTwilioSettings] = useState({
    account_sid: '',
    auth_token: '',
    phone_number: '',
    enabled: false,
  });

  const handleSaveTwilio = async () => {
    setSaving(true);
    setMessage(null);
    try {
      const res = await apiFetch('/api/admin/settings/twilio', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(twilioSettings),
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
        <div className="w-12 h-12 bg-[#F22F46] rounded-xl flex items-center justify-center shadow-sm">
          <MaterialSymbol icon="sms" size={24} className="text-white" />
        </div>
        <div>
          <h3 className="text-xl font-bold text-[#002819]">{t('settings.twilioSettings')}</h3>
          <p className="text-sm text-[#717973]">{t('settings.twilioDescription')}</p>
        </div>
      </div>

      <ToggleSwitch
        checked={twilioSettings.enabled}
        onChange={(e) => setTwilioSettings({ ...twilioSettings, enabled: e.target.checked })}
        label={t('settings.enableTwilio')}
      />

      <div className="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <InputField
          label={t('settings.twilioAccountSid')}
          value={twilioSettings.account_sid}
          onChange={(e) => setTwilioSettings({ ...twilioSettings, account_sid: e.target.value })}
          placeholder="ACxxxxxxxx..."
        />
        <InputField
          label={t('settings.twilioAuthToken')}
          type="password"
          value={twilioSettings.auth_token}
          onChange={(e) => setTwilioSettings({ ...twilioSettings, auth_token: e.target.value })}
          placeholder="Auth Token"
        />
        <div className="lg:col-span-2">
          <InputField
            label={t('settings.twilioPhoneNumber')}
            value={twilioSettings.phone_number}
            onChange={(e) => setTwilioSettings({ ...twilioSettings, phone_number: e.target.value })}
            placeholder="+1234567890"
          />
        </div>
      </div>

      <div className="mt-6">
        <SaveButton onClick={handleSaveTwilio} saving={saving} color="#F22F46" />
      </div>
    </div>
  );
}
