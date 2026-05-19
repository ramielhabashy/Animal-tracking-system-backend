import React from 'react';
import { useState, useEffect } from 'react';
import { apiFetch } from '../../utils/api';
import { useI18n } from '../../i18n';
import SettingsCard from './SettingsCard';
import { ToggleSwitch, SaveButton } from './InputField';

export default function AuctionSettings({ dir, message, setMessage, saving, setSaving }) {
  const { t } = useI18n();
  const [settings, setSettings] = useState({
    auto_approve: false,
  });

  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    try {
      const res = await apiFetch('/api/admin/settings/auction');
      if (res.ok) {
        const d = await res.json();
        if (d.data) setSettings(d.data);
      }
    } catch (e) {
      console.error('Failed to load auction settings:', e);
    }
  };

  const handleSave = async () => {
    setSaving?.(true);
    try {
      const res = await apiFetch('/api/admin/settings/auction', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(settings),
      });
      if (res.ok) {
        setMessage?.({ type: 'success', text: 'Auction settings saved' });
      } else {
        const d = await res.json();
        setMessage?.({ type: 'error', text: d.message || 'Failed to save' });
      }
    } catch (e) {
      setMessage?.({ type: 'error', text: 'Failed to save settings' });
    } finally {
      setSaving?.(false);
    }
  };

  return (
    <SettingsCard
      icon="gavel"
      title="Auction Settings"
      description="Configure auction platform behavior"
    >
      <div className="space-y-6">
        <div className="space-y-2">
          <ToggleSwitch
            label="Auto-approve Auctions"
            checked={settings.auto_approve}
            onChange={(v) => setSettings({ ...settings, auto_approve: v })}
          />
          <p className="text-xs text-[#717973] mt-1">
            When enabled, auctions go live immediately without admin approval. When disabled, admin must approve each auction before it goes live.
          </p>
        </div>

        <SaveButton onClick={handleSave} saving={saving} />
      </div>
    </SettingsCard>
  );
}
