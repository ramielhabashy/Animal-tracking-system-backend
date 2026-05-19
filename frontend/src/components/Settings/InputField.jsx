import React from 'react';

export function InputField({ label, value, onChange, placeholder, type = 'text', disabled, className = '', inputClassName = '', children }) {
  return (
    <div className={className}>
      {label && <label className="block text-xs font-bold text-[#404943] uppercase tracking-wider mb-2">{label}</label>}
      {children || (
        <input
          type={type}
          value={value}
          onChange={onChange}
          placeholder={placeholder}
          disabled={disabled}
          className={`w-full bg-[#F4F4EF] border-none rounded-xl p-4 text-[#002819] focus:ring-2 focus:ring-[#06402B]/20 disabled:opacity-50 ${inputClassName}`}
        />
      )}
    </div>
  );
}

export function SelectField({ label, value, onChange, options, placeholder, disabled, className = '' }) {
  return (
    <div className={className}>
      {label && <label className="block text-xs font-bold text-[#404943] uppercase tracking-wider mb-2">{label}</label>}
      <select
        value={value}
        onChange={onChange}
        disabled={disabled}
        className="w-full bg-[#F4F4EF] border-none rounded-xl p-4 text-[#002819] focus:ring-2 focus:ring-[#06402B]/20 disabled:opacity-50"
      >
        {placeholder && <option value="">{placeholder}</option>}
        {options.map(opt => (
          <option key={opt.value} value={opt.value}>{opt.label}</option>
        ))}
      </select>
    </div>
  );
}

export function CheckboxField({ label, checked, onChange, description }) {
  return (
    <label className="flex items-start gap-3 p-4 rounded-xl bg-[#F4F4EF] cursor-pointer hover:bg-[#eeeee9] transition-colors">
      <input
        type="checkbox"
        checked={checked}
        onChange={onChange}
        className="w-5 h-5 rounded-lg border-2 border-[#D4AF37] text-[#D4AF37] focus:ring-[#D4AF37] cursor-pointer mt-0.5"
      />
      <div>
        <span className="font-bold text-[#002819]">{label}</span>
        {description && <p className="text-sm text-[#717973] mt-0.5">{description}</p>}
      </div>
    </label>
  );
}

export function ToggleSwitch({ checked, onChange, label }) {
  return (
    <label className="flex items-center gap-3 cursor-pointer">
      <div className="relative">
        <input type="checkbox" checked={checked} onChange={onChange} className="sr-only peer" />
        <div className="w-9 h-5 bg-[#E3E3DE] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#002819]" />
      </div>
      {label && <span className="text-sm font-semibold text-[#002819]">{label}</span>}
    </label>
  );
}

export function SaveButton({ onClick, saving, children, color = '#002819', className = '' }) {
  return (
    <button
      onClick={onClick}
      disabled={saving}
      className={`w-full py-3 text-white rounded-xl font-bold hover:opacity-90 transition disabled:opacity-50 flex items-center justify-center gap-2 ${className}`}
      style={{ backgroundColor: color }}
    >
      {saving && <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />}
      {saving ? 'Saving...' : (children || 'Save')}
    </button>
  );
}
