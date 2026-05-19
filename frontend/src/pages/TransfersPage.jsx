import React from 'react';
import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { MaterialSymbol } from 'react-material-symbols';
import { apiFetch } from '../utils/api';
import { useI18n } from '../i18n';
import { useAuth } from '../context/AuthContext';
import { TransferStatusBadge, TransferCreateModal } from '../components/Transfers';

const AVATAR_COLORS = ['#002819', '#06402B', '#D4AF37', '#8B4513', '#2E5090', '#7B2D8B', '#B8860B', '#4A6741'];

function getInitials(name) {
  return (name || '?').split(' ').map(s => s[0]).join('').toUpperCase().slice(0, 2);
}

function getAvatarColor(id) {
  return AVATAR_COLORS[(id || 0) % AVATAR_COLORS.length];
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

export default function TransfersPage() {
  const navigate = useNavigate();
  const { t, dir } = useI18n();
  const { user } = useAuth();
  const isRtl = dir === 'rtl';
  const isAdmin = user?.role === 'Admin';

  const [tab, setTab] = useState('sent');
  const [transfers, setTransfers] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [showCreate, setShowCreate] = useState(false);
  const [actionLoading, setActionLoading] = useState(null);

  const tabs = [
    { id: 'sent', label: t('transfers.sent') || 'Sent', type: 'sent' },
    { id: 'received', label: t('transfers.received') || 'Received', type: 'received' },
    { id: 'history', label: t('transfers.history') || 'History', type: null },
  ];

  const fetchTransfers = async (p = 1) => {
    setLoading(true);
    try {
      let url = `/api/transfers?page=${p}`;
      if (tab === 'sent') url += '&type=sent';
      else if (tab === 'received') url += '&type=received';
      else url += '&status=completed,rejected,cancelled,expired';
      const res = await apiFetch(url);
      if (res.ok) {
        const d = await res.json();
        const payload = d.data || {};
        setTransfers(payload.data || []);
        setMeta(payload.meta || null);
      }
    } catch (e) {
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    setPage(1);
    fetchTransfers(1);
  }, [tab]);

  const handleAction = async (transferId, action) => {
    setActionLoading(`${action}-${transferId}`);
    try {
      const res = await apiFetch(`/api/transfers/${transferId}/${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({}),
      });
      if (res.ok) {
        fetchTransfers(page);
      }
    } catch (e) {
    } finally {
      setActionLoading(null);
    }
  };

  const totalPages = meta?.last_page || 1;

  return (
    <div className="space-y-6" dir={dir}>
      <div>
        <nav className={`flex text-xs text-[#4f6357] mb-2 uppercase tracking-widest font-bold ${isRtl ? 'flex-row-reverse' : ''}`}>
          <span>{t('nav.transfers') || 'Transfers'}</span>
        </nav>
        <h2 className="text-3xl font-bold text-[#002819]">{t('transfers.title') || 'Ownership Transfers'}</h2>
        <p className="text-[#404943] mt-1">{t('transfers.subtitle') || 'Manage animal ownership transfers'}</p>
      </div>

      {/* Tabs */}
      <div className="flex flex-wrap gap-2 bg-[#F4F4EF] p-1 rounded-xl w-fit">
        {tabs.map(tabItem => (
          <button
            key={tabItem.id}
            onClick={() => setTab(tabItem.id)}
            className={`px-4 py-2 rounded-lg font-medium text-sm transition ${tab === tabItem.id ? 'bg-white text-[#002819] shadow-sm' : 'text-[#404943] hover:text-[#002819]'}`}
          >
            {tabItem.label}
          </button>
        ))}
      </div>

      {/* Create button */}
      <button
        onClick={() => setShowCreate(true)}
        className="inline-flex items-center gap-2 px-5 py-2.5 bg-[#002819] text-white rounded-xl font-semibold text-sm hover:bg-[#06402B] transition-colors"
      >
        <MaterialSymbol icon="add" size={18} />
        {t('transfers.createNew') || 'New Transfer'}
      </button>

      {/* Loading */}
      {loading ? (
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin w-8 h-8 border-4 border-[#002819] border-t-transparent rounded-full" />
        </div>
      ) : transfers.length === 0 ? (
        /* Empty state */
        <div className="bg-white rounded-2xl shadow-sm border border-[#E3E3DE] flex flex-col items-center justify-center py-16">
          <MaterialSymbol icon="swap_horiz" size={48} className="text-[#717973] mb-3" />
          <p className="text-[#404943] font-medium">{t('transfers.noTransfers') || 'No transfers found'}</p>
          <p className="text-sm text-[#717973] mt-1">{t('transfers.noTransfersHint') || 'Create a new transfer to get started'}</p>
        </div>
      ) : (
        /* Table */
        <div className="bg-white rounded-2xl shadow-sm border border-[#E3E3DE] overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-[#E3E3DE]">
                  <th className="text-left p-4 text-xs font-bold text-[#717973] uppercase tracking-wider">ID</th>
                  <th className="text-left p-4 text-xs font-bold text-[#717973] uppercase tracking-wider">{t('transfers.otherParty') || 'Party'}</th>
                  <th className="text-left p-4 text-xs font-bold text-[#717973] uppercase tracking-wider">{t('transfers.animals') || 'Animals'}</th>
                  <th className="text-left p-4 text-xs font-bold text-[#717973] uppercase tracking-wider">{t('common.status') || 'Status'}</th>
                  {isAdmin && tab === 'history' && (
                    <th className="text-left p-4 text-xs font-bold text-[#717973] uppercase tracking-wider">{t('transfers.commission') || 'Commission'}</th>
                  )}
                  <th className="text-left p-4 text-xs font-bold text-[#717973] uppercase tracking-wider">{t('common.date') || 'Date'}</th>
                  <th className="text-left p-4 text-xs font-bold text-[#717973] uppercase tracking-wider">{t('common.actions') || 'Actions'}</th>
                </tr>
              </thead>
              <tbody>
                {transfers.map(transfer => {
                  const fromUser = transfer.from_user || transfer.sender || {};
                  const toUser = transfer.to_user || transfer.receiver || {};
                  const otherParty = tab === 'sent' ? toUser : fromUser;
                  const animalCount = transfer.animals?.length || transfer.animal_count || 0;
                  return (
                    <tr
                      key={transfer.id}
                      onClick={() => navigate(`/transfers/${transfer.id}`)}
                      className="border-b border-[#E3E3DE] last:border-0 hover:bg-[#FAF1F5]/50 cursor-pointer"
                    >
                      <td className="p-4 font-bold text-[#002819] text-sm">#{transfer.id}</td>
                      <td className="p-4">
                        <div className="flex items-center gap-2.5">
                          <div
                            className="w-8 h-8 rounded-full flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0"
                            style={{ backgroundColor: getAvatarColor(otherParty?.id || 0) }}
                          >
                            {getInitials(otherParty?.name || '?')}
                          </div>
                          <div className="min-w-0">
                            <p className="text-sm font-medium text-[#002819] truncate">{otherParty?.name || '—'}</p>
                            <p className="text-[11px] text-[#404943]/50 truncate">{otherParty?.email || ''}</p>
                          </div>
                        </div>
                      </td>
                      <td className="p-4 text-sm text-[#404943]">
                        {animalCount > 0
                          ? `${animalCount} ${t('transfers.animals') || 'animals'}`
                          : (t('common.none') || '—')}
                      </td>
                      <td className="p-4">
                        <TransferStatusBadge status={transfer.status} t={t} />
                      </td>
                      {isAdmin && tab === 'history' && (
                        <td className="p-4 text-sm text-[#404943]">
                          {transfer.commission_amount
                            ? `SAR ${parseFloat(transfer.commission_amount).toFixed(2)}`
                            : '—'}
                        </td>
                      )}
                      <td className="p-4 text-sm text-[#717973]">{formatDate(transfer.created_at)}</td>
                      <td className="p-4" onClick={e => e.stopPropagation()}>
                        <div className="flex gap-2">
                          {tab === 'sent' && transfer.status === 'pending' && (
                            <button
                              onClick={() => handleAction(transfer.id, 'cancel')}
                              disabled={actionLoading === `cancel-${transfer.id}`}
                              className="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-200 transition disabled:opacity-50"
                            >
                              {actionLoading === `cancel-${transfer.id}` ? '...' : (t('transfers.cancel') || 'Cancel')}
                            </button>
                          )}
                          {tab === 'received' && transfer.status === 'pending' && (
                            <>
                              <button
                                onClick={() => handleAction(transfer.id, 'accept')}
                                disabled={actionLoading === `accept-${transfer.id}`}
                                className="px-3 py-1.5 bg-emerald-500 text-white rounded-lg text-xs font-bold hover:bg-emerald-600 transition disabled:opacity-50"
                              >
                                {actionLoading === `accept-${transfer.id}` ? '...' : (t('transfers.accept') || 'Accept')}
                              </button>
                              <button
                                onClick={() => handleAction(transfer.id, 'reject')}
                                disabled={actionLoading === `reject-${transfer.id}`}
                                className="px-3 py-1.5 bg-red-500 text-white rounded-lg text-xs font-bold hover:bg-red-600 transition disabled:opacity-50"
                              >
                                {actionLoading === `reject-${transfer.id}` ? '...' : (t('transfers.reject') || 'Reject')}
                              </button>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          {totalPages > 1 && (
            <div className={`flex items-center justify-between px-4 py-3 border-t border-[#E3E3DE] ${isRtl ? 'flex-row-reverse' : ''}`}>
              <p className="text-xs text-[#717973]">
                {t('common.page') || 'Page'} {page} {t('common.of') || 'of'} {totalPages}
              </p>
              <div className="flex gap-2">
                <button
                  disabled={page <= 1}
                  onClick={() => { setPage(p => p - 1); fetchTransfers(page - 1); }}
                  className="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#F4F4EF] text-[#404943] hover:bg-[#E3E3DE] transition disabled:opacity-40"
                >
                  {t('common.previous') || 'Previous'}
                </button>
                <button
                  disabled={page >= totalPages}
                  onClick={() => { setPage(p => p + 1); fetchTransfers(page + 1); }}
                  className="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#F4F4EF] text-[#404943] hover:bg-[#E3E3DE] transition disabled:opacity-40"
                >
                  {t('common.next') || 'Next'}
                </button>
              </div>
            </div>
          )}
        </div>
      )}

      {showCreate && (
        <TransferCreateModal
          onClose={() => setShowCreate(false)}
          onCreated={() => fetchTransfers(page)}
        />
      )}
    </div>
  );
}
