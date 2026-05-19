import React from 'react';
import { MaterialSymbol } from 'react-material-symbols';

export default function SettingsCard({ icon, title, description, children, className = '' }) {
  return (
    <div className={`bg-white rounded-[2rem] p-8 shadow-sm border border-[#eeeee9] ${className}`}>
      <div className="flex items-center gap-3 mb-6">
        <div className="w-12 h-12 bg-[#002819] rounded-xl flex items-center justify-center shadow-sm">
          <MaterialSymbol icon={icon} size={24} className="text-white" />
        </div>
        <div>
          <h3 className="text-xl font-bold text-[#002819]">{title}</h3>
          {description && <p className="text-sm text-[#717973]">{description}</p>}
        </div>
      </div>
      {children}
    </div>
  );
}
