import React from 'react';
import { MaterialSymbol } from 'react-material-symbols';

export default function SubTabBar({ tabs, activeTab, onTabChange }) {
  return (
    <div className="flex gap-2 bg-[#F4F4EF] p-1 rounded-xl w-fit mb-6">
      {tabs.map(tab => (
        <button
          key={tab.id}
          onClick={() => onTabChange(tab.id)}
          className={`flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all ${
            activeTab === tab.id ? 'bg-white text-[#002819] shadow-sm' : 'text-[#717973] hover:text-[#002819]'
          }`}
        >
          <MaterialSymbol icon={tab.icon} size={16} />
          {tab.label}
        </button>
      ))}
    </div>
  );
}
