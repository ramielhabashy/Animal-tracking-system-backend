import React from 'react';
import { useState, useEffect } from 'react';
import { MaterialSymbol } from 'react-material-symbols';
import { apiFetch } from '../../utils/api';
import { useI18n } from '../../i18n';
import SettingsCard from './SettingsCard';
import { ToggleSwitch, InputField, SelectField, SaveButton } from './InputField';

export default function TransferCommissionSettings({ dir, message, setMessage, saving, setSaving }) {
  const { t } = useI18n();
  const isRtl = dir === 'rtl';
  const [settings, setSettings] = useState({
    enabled: false,
    type: 'percentage',
    percentage: 5,
    fixed: 0,
  });

  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    try {
      const res = await apiFetch('/api/admin/settings/transfer-commission');
      if (res.ok) {
        const d = await res.json();
        if (d.data) setSettings(d.data);
      }
    } catch (e) {
      console.error('Failed to load transfer commission settings:', e);
    }
  };

  const handleSave = async () => {
    setSaving?.(true);
    try {
      const res = await apiFetch('/api/admin/settings/transfer-commission', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(settings),
      });
      if (res.ok) {
        setMessage?.({ type: 'success', text: 'Transfer commission settings saved' });
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
      icon="swap_horiz"
      title="Transfer Commission"
      description="Configure platform commission on animal ownership transfers"
    >
      <div className="space-y-6">
        <ToggleSwitch
          label="Enable Transfer Commission"
          checked={settings.enabled}
          onChange={(v) => setSettings({ ...settings, enabled: v })}
        />

        {settings.enabled && (
          <>
            <SelectField
              label="Commission Type"
              value={settings.type}
              onChange={(v) => setSettings({ ...settings, type: v })}
              options={[
                { value: 'percentage', label: 'Percentage (%)' },
                { value: 'fixed', label: 'Fixed Amount (SAR)' },
              ]}
            />

            {settings.type === 'percentage' ? (
              <InputField
                label="Commission Percentage"
                type="number"
                value={settings.percentage}
                onChange={(v) => setSettings({ ...settings, percentage: parseFloat(v) || 0 })}
                min={0}
                max={100}
                step={0.5}
              />
            ) : (
              <InputField
                label="Fixed Commission Amount (SAR)"
                type="number"
                value={settings.fixed}
                onChange={(v) => setSettings({ ...settings, fixed: parseFloat(v) || 0 })}
                min={0}
                step={10}
              />
            )}
          </>
        )}

        <SaveButton onClick={handleSave} saving={saving} />
      </div>
    </SettingsCard>
  );
}
