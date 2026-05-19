import React from 'react';
import { useState, useEffect } from 'react';
import { MaterialSymbol } from 'react-material-symbols';
import { apiFetch } from '../../utils/api';
import { useI18n } from '../../i18n';

export default function TransferCreateModal({ onClose, onCreated, preselectedAnimalIds = [], preselectedGroupId = '' }) {
  const { t, dir } = useI18n();
  const isRtl = dir === 'rtl';

  const [mode, setMode] = useState(preselectedGroupId ? 'group' : 'animals');
  const [animals, setAnimals] = useState([]);
  const [groups, setGroups] = useState([]);
  const [users, setUsers] = useState([]);
  const [loadingAnimals, setLoadingAnimals] = useState(true);
  const [loadingGroups, setLoadingGroups] = useState(false);
  const [loadingUsers, setLoadingUsers] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState(null);

  const [selectedAnimalIds, setSelectedAnimalIds] = useState(preselectedAnimalIds);
  const [selectedGroupId, setSelectedGroupId] = useState(preselectedGroupId);
  const [toUserId, setToUserId] = useState('');
  const [agreedPrice, setAgreedPrice] = useState('');
  const [notes, setNotes] = useState('');

  const [animalSearch, setAnimalSearch] = useState('');

  useEffect(() => {
    fetchAnimals();
    fetchUsers();
  }, []);

  useEffect(() => {
    if (mode === 'group') fetchGroups();
  }, [mode]);

  const fetchAnimals = async () => {
    setLoadingAnimals(true);
    try {
      const res = await apiFetch('/api/animals?per_page=200');
      if (res.ok) {
        const d = await res.json();
        setAnimals(d.data?.data || d.data || []);
      }
    } catch (e) {
    } finally {
      setLoadingAnimals(false);
    }
  };

  const fetchGroups = async () => {
    setLoadingGroups(true);
    try {
      const res = await apiFetch('/api/animal-groups?per_page=100');
      if (res.ok) {
        const d = await res.json();
        setGroups(d.data?.data || d.data || []);
      }
    } catch (e) {
    } finally {
      setLoadingGroups(false);
    }
  };

  const fetchUsers = async () => {
    setLoadingUsers(true);
    try {
      const res = await apiFetch('/api/users?per_page=100');
      if (res.ok) {
        const d = await res.json();
        setUsers((d.data?.data || d.data || []).filter(u => u.role === 'Owner' || u.role === 'Admin'));
      }
    } catch (e) {
    } finally {
      setLoadingUsers(false);
    }
  };

  const toggleAnimal = (id) => {
    setSelectedAnimalIds(prev =>
      prev.includes(id) ? prev.filter(a => a !== id) : [...prev, id]
    );
  };

  const selectAllFiltered = () => {
    const ids = filteredAnimals.map(a => a.id);
    const allSelected = ids.every(id => selectedAnimalIds.includes(id));
    if (allSelected) {
      setSelectedAnimalIds(prev => prev.filter(id => !ids.includes(id)));
    } else {
      setSelectedAnimalIds(prev => [...new Set([...prev, ...ids])]);
    }
  };

  const handleSubmit = async () => {
    if (mode === 'animals' && selectedAnimalIds.length === 0) return;
    if (mode === 'group' && !selectedGroupId) return;
    if (!toUserId) return;
    setSubmitting(true);
    setError(null);

    const body = {
      to_user_id: parseInt(toUserId),
      ...(mode === 'animals' ? { animal_ids: selectedAnimalIds } : { group_id: parseInt(selectedGroupId) }),
    };
    if (agreedPrice) body.agreed_price = parseFloat(agreedPrice);
    if (notes.trim()) body.notes = notes.trim();

    try {
      const res = await apiFetch('/api/transfers', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });
      if (res.ok) {
        onCreated?.();
        onClose();
      } else {
        const d = await res.json();
        setError(d.message || 'Failed to create transfer');
      }
    } catch (e) {
      setError('Network error');
    } finally {
      setSubmitting(false);
    }
  };

  const commissionPercent = 5;
  const commissionAmount = agreedPrice ? (parseFloat(agreedPrice) * commissionPercent / 100).toFixed(2) : null;

  const filteredAnimals = animals.filter(a => {
    if (!animalSearch) return true;
    const q = animalSearch.toLowerCase();
    return (a.name || '').toLowerCase().includes(q) || (a.animal_id || '').toLowerCase().includes(q);
  });

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm" onClick={onClose}>
      <div className="bg-white rounded-2xl w-[520px] max-w-[95vw] max-h-[90vh] overflow-y-auto shadow-xl" onClick={e => e.stopPropagation()} dir={dir}>
        <div className="px-6 py-4 border-b border-[#E3E3DE] flex items-center justify-between">
          <h3 className="text-lg font-bold text-[#002819]">{t('transfers.createNew') || 'New Transfer'}</h3>
          <button onClick={onClose} className="w-8 h-8 rounded-xl text-[#404943]/50 hover:text-[#404943] hover:bg-[#F4F4EF] flex items-center justify-center">
            <MaterialSymbol icon="close" size={18} />
          </button>
        </div>

        <div className="p-6 space-y-5">
          {error && (
            <div className="px-4 py-3 rounded-xl bg-red-50 text-red-700 border border-red-200 text-sm flex items-center gap-2">
              <MaterialSymbol icon="error" size={18} />
              {error}
            </div>
          )}

          {/* Mode toggle */}
          <div>
            <label className="block text-xs font-semibold text-[#404943]/70 mb-2">{t('transfers.selectionMode') || 'Selection Mode'}</label>
            <div className="flex gap-2 bg-[#F4F4EF] p-1 rounded-xl w-fit">
              <button
                onClick={() => setMode('animals')}
                className={`px-4 py-2 rounded-lg text-xs font-semibold transition-colors ${mode === 'animals' ? 'bg-white text-[#002819] shadow-sm' : 'text-[#404943] hover:text-[#002819]'}`}
              >
                {t('transfers.selectAnimals') || 'Select Animals'}
              </button>
              <button
                onClick={() => setMode('group')}
                className={`px-4 py-2 rounded-lg text-xs font-semibold transition-colors ${mode === 'group' ? 'bg-white text-[#002819] shadow-sm' : 'text-[#404943] hover:text-[#002819]'}`}
              >
                {t('transfers.selectGroup') || 'Select Group'}
              </button>
            </div>
          </div>

          {/* Animals mode */}
          {mode === 'animals' && (
            <div>
              <label className="block text-xs font-semibold text-[#404943]/70 mb-1.5">
                {t('transfers.animals') || 'Animals'} ({selectedAnimalIds.length} {t('transfers.selected') || 'selected'})
              </label>
              <div className="relative mb-2">
                <MaterialSymbol icon="search" size={16} className="absolute top-1/2 -translate-y-1/2 text-[#404943]/50" style={{ [isRtl ? 'right' : 'left']: '10px' }} />
                <input
                  type="text"
                  value={animalSearch}
                  onChange={e => setAnimalSearch(e.target.value)}
                  placeholder={t('common.search') || 'Search animals...'}
                  className={`w-full bg-[#FAF5F1] border border-[#E3E3DE] rounded-xl px-3 py-2 text-xs text-[#404943] placeholder:text-[#404943]/40 focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] ${isRtl ? 'pr-8 pl-3' : 'pl-8 pr-3'}`}
                />
              </div>
              {filteredAnimals.length > 0 && (
                <button
                  onClick={selectAllFiltered}
                  className="text-[11px] font-medium text-[#D4AF37] hover:underline mb-1.5 block"
                >
                  {filteredAnimals.every(a => selectedAnimalIds.includes(a.id))
                    ? (t('common.deselectAll') || 'Deselect all')
                    : ((t('common.selectAll') || 'Select all') + ` (${filteredAnimals.length})`)}
                </button>
              )}
              <div className="max-h-48 overflow-y-auto space-y-1 border border-[#E3E3DE] rounded-xl p-1">
                {loadingAnimals ? (
                  <div className="flex items-center justify-center py-6">
                    <MaterialSymbol icon="progress_activity" size={20} className="text-[#D4AF37] animate-spin" />
                  </div>
                ) : filteredAnimals.length === 0 ? (
                  <p className="text-xs text-[#404943]/40 text-center py-6">{t('common.noData') || 'No animals found'}</p>
                ) : (
                  filteredAnimals.map(animal => (
                    <label
                      key={animal.id}
                      className={`flex items-center gap-3 px-3 py-2 rounded-xl cursor-pointer transition-colors ${selectedAnimalIds.includes(animal.id) ? 'bg-[#002819]/10' : 'hover:bg-[#F4F4EF]'}`}
                    >
                      <input
                        type="checkbox"
                        checked={selectedAnimalIds.includes(animal.id)}
                        onChange={() => toggleAnimal(animal.id)}
                        className="w-4 h-4 rounded accent-[#002819]"
                      />
                      <span className="text-xs font-semibold text-[#002819] flex-1">{animal.name || `#${animal.animal_id}`}</span>
                      <span className="text-[11px] text-[#404943]/50">{animal.species || ''}</span>
                    </label>
                  ))
                )}
              </div>
            </div>
          )}

          {/* Group mode */}
          {mode === 'group' && (
            <div>
              <label className="block text-xs font-semibold text-[#404943]/70 mb-1.5">{t('transfers.selectGroup') || 'Select Group'}</label>
              <select
                value={selectedGroupId}
                onChange={e => setSelectedGroupId(e.target.value)}
                className="w-full bg-[#FAF5F1] border border-[#E3E3DE] rounded-xl px-4 py-2.5 text-sm text-[#404943] focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37]"
              >
                <option value="">{t('common.select') || 'Select...'}</option>
                {loadingGroups ? (
                  <option disabled>Loading...</option>
                ) : (
                  groups.map(g => (
                    <option key={g.id} value={g.id}>{g.name || `Group #${g.id}`}</option>
                  ))
                )}
              </select>
            </div>
          )}

          {/* Target user */}
          <div>
            <label className="block text-xs font-semibold text-[#404943]/70 mb-1.5">{t('transfers.transferTo') || 'Transfer To'}</label>
            <select
              value={toUserId}
              onChange={e => setToUserId(e.target.value)}
              className="w-full bg-[#FAF5F1] border border-[#E3E3DE] rounded-xl px-4 py-2.5 text-sm text-[#404943] focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37]"
            >
              <option value="">{t('transfers.selectUser') || 'Select user...'}</option>
              {loadingUsers ? (
                <option disabled>Loading...</option>
              ) : (
                users.map(u => (
                  <option key={u.id} value={u.id}>{u.name} ({u.role})</option>
                ))
              )}
            </select>
          </div>

          {/* Agreed price */}
          <div>
            <label className="block text-xs font-semibold text-[#404943]/70 mb-1.5">{t('transfers.agreedPrice') || 'Agreed Price (optional)'}</label>
            <input
              type="number"
              step="0.01"
              min="0"
              value={agreedPrice}
              onChange={e => setAgreedPrice(e.target.value)}
              placeholder="0.00"
              className="w-full bg-[#FAF5F1] border border-[#E3E3DE] rounded-xl px-4 py-2.5 text-sm text-[#404943] placeholder:text-[#404943]/40 focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37]"
            />
            {commissionAmount && (
              <p className="text-xs text-[#404943]/50 mt-1">
                {t('transfers.commissionPreview') || `Commission (${commissionPercent}%): SAR ${commissionAmount}`}
              </p>
            )}
          </div>

          {/* Notes */}
          <div>
            <label className="block text-xs font-semibold text-[#404943]/70 mb-1.5">{t('transfers.notes') || 'Notes (optional)'}</label>
            <textarea
              value={notes}
              onChange={e => setNotes(e.target.value)}
              rows={3}
              placeholder={t('transfers.notesPlaceholder') || 'Add any notes...'}
              className="w-full bg-[#FAF5F1] border border-[#E3E3DE] rounded-xl px-4 py-2.5 text-sm text-[#404943] placeholder:text-[#404943]/40 focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] resize-none"
            />
          </div>
        </div>

        <div className={`px-6 py-4 border-t border-[#E3E3DE] flex gap-3 ${isRtl ? 'flex-row-reverse' : 'justify-end'}`}>
          <button
            onClick={onClose}
            className="px-5 py-2.5 rounded-xl text-sm font-semibold text-[#404943] hover:bg-[#F4F4EF] transition-colors"
          >
            {t('common.cancel') || 'Cancel'}
          </button>
          <button
            onClick={handleSubmit}
            disabled={
              submitting ||
              (mode === 'animals' && selectedAnimalIds.length === 0) ||
              (mode === 'group' && !selectedGroupId) ||
              !toUserId
            }
            className={`px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors ${
              submitting || (mode === 'animals' && selectedAnimalIds.length === 0) || (mode === 'group' && !selectedGroupId) || !toUserId
                ? 'bg-[#E3E3DE] text-[#404943]/30 cursor-not-allowed'
                : 'bg-[#002819] text-white hover:bg-[#06402B]'
            }`}
          >
            {submitting ? (
              <span className="flex items-center gap-2">
                <MaterialSymbol icon="progress_activity" size={16} className="animate-spin" />
                {t('common.submit') || 'Submitting...'}
              </span>
            ) : (
              t('transfers.createTransfer') || 'Create Transfer'
            )}
          </button>
        </div>
      </div>
    </div>
  );
}
