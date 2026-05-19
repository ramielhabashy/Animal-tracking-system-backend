import React from 'react';
import { MaterialSymbol } from 'react-material-symbols';

export default function SettingsTabBar({ tabs, activeTab, onTabChange }) {
  return (
    <div className="flex flex-wrap gap-2 bg-[#F4F4EF] p-1 rounded-xl w-fit">
      {tabs.map(tab => (
        <button
          key={tab.id}
          onClick={() => onTabChange(tab.id)}
          className={`flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-sm transition-all ${
            activeTab === tab.id
              ? 'bg-white text-[#002819] shadow-sm'
              : 'text-[#404943] hover:text-[#002819]'
          }`}
        >
          <MaterialSymbol icon={tab.icon} size={18} />
          {tab.label}
        </button>
      ))}
    </div>
  );
}
