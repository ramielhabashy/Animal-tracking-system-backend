import React from 'react';
import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { MaterialSymbol } from 'react-material-symbols';
import { apiFetch } from '../utils/api';
import { useI18n } from '../i18n';
import { useAuth } from '../context/AuthContext';
import { TransferStatusBadge } from '../components/Transfers';

const AVATAR_COLORS = ['#002819', '#06402B', '#D4AF37', '#8B4513', '#2E5090', '#7B2D8B', '#B8860B', '#4A6741'];

function getInitials(name) {
  return (name || '?').split(' ').map(s => s[0]).join('').toUpperCase().slice(0, 2);
}

function getAvatarColor(id) {
  return AVATAR_COLORS[(id || 0) % AVATAR_COLORS.length];
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

export default function TransferDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { t, dir } = useI18n();
  const { user } = useAuth();
  const isRtl = dir === 'rtl';

  const [transfer, setTransfer] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [actionLoading, setActionLoading] = useState(null);
  const [rejectionReason, setRejectionReason] = useState('');
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [message, setMessage] = useState(null);

  const fetchTransfer = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await apiFetch(`/api/transfers/${id}`);
      if (res.ok) {
        const d = await res.json();
        setTransfer(d.data || d);
      } else {
        setError(t('errors.not_found') || 'Transfer not found');
      }
    } catch (e) {
      setError(t('errors.networkError') || 'Network error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTransfer();
  }, [id]);

  const handleAction = async (action) => {
    setActionLoading(action);
    try {
      const body = {};
      if (action === 'reject' && rejectionReason.trim()) body.rejection_reason = rejectionReason.trim();
      const res = await apiFetch(`/api/transfers/${id}/${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });
      if (res.ok) {
        setMessage({ type: 'success', text: t(`transfers.${action}Success`) || `Transfer ${action}ed successfully` });
        setShowRejectModal(false);
        setRejectionReason('');
        fetchTransfer();
      } else {
        const d = await res.json();
        setMessage({ type: 'error', text: d.message || `Failed to ${action} transfer` });
      }
    } catch (e) {
      setMessage({ type: 'error', text: t('errors.networkError') || 'Network error' });
    } finally {
      setActionLoading(null);
    }
  };

  const canCancel = transfer?.status === 'pending' && String(transfer.from_user?.id || transfer.sender?.id) === String(user?.id);
  const canAcceptReject = transfer?.status === 'pending' && String(transfer.to_user?.id || transfer.receiver?.id) === String(user?.id);

  if (loading) {
    return (
      <div className="flex items-center justify-center h-[calc(100vh-8rem)] bg-white rounded-3xl shadow-sm border border-[#E3E3DE]" dir={dir}>
        <div className="animate-spin w-8 h-8 border-4 border-[#002819] border-t-transparent rounded-full" />
      </div>
    );
  }

  if (error || !transfer) {
    return (
      <div className="flex flex-col items-center justify-center h-[calc(100vh-8rem)] bg-white rounded-3xl shadow-sm border border-[#E3E3DE]" dir={dir}>
        <MaterialSymbol icon="error" size={48} className="text-[#404943]/20 mb-3" />
        <p className="text-sm text-[#404943]/50 font-medium">{error || t('common.noData') || 'Not found'}</p>
        <button onClick={() => navigate('/transfers')} className="mt-4 px-5 py-2.5 rounded-xl bg-[#002819] text-white text-sm font-semibold hover:bg-[#06402B] transition-colors">
          {t('common.back') || 'Back to Transfers'}
        </button>
      </div>
    );
  }

  const fromUser = transfer.from_user || transfer.sender || {};
  const toUser = transfer.to_user || transfer.receiver || {};
  const animals = transfer.animals || [];
  const timeline = transfer.timeline || [];
  const commission = transfer.commission || {};

  return (
    <div className="space-y-6" dir={dir}>
      <div id="transfer-toast" className={`fixed top-4 end-4 z-[100] px-4 py-2.5 rounded-xl shadow-lg text-sm font-medium transition-all duration-300 opacity-0 translate-y-2 pointer-events-none ${
        message?.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'
      }`}>
        {message?.text || ''}
      </div>

      {message && (
        <div className={`p-4 rounded-xl flex items-center gap-3 ${
          message.type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200'
        }`}>
          <MaterialSymbol icon={message.type === 'success' ? 'check_circle' : 'error'} size={20} />
          <span className="flex-1">{message.text}</span>
          <button onClick={() => setMessage(null)} className="ml-auto">
            <MaterialSymbol icon="close" size={18} />
          </button>
        </div>
      )}

      {/* Header */}
      <div className="bg-white rounded-2xl shadow-sm border border-[#E3E3DE] p-6">
        <div className={`flex items-start gap-4 flex-wrap ${isRtl ? 'flex-row-reverse' : ''}`}>
          <button
            onClick={() => navigate('/transfers')}
            className="w-9 h-9 rounded-xl text-[#404943]/50 hover:text-[#404943] hover:bg-[#F4F4EF] flex items-center justify-center transition-colors flex-shrink-0"
          >
            <MaterialSymbol icon="arrow_back" size={20} />
          </button>
          <div className="flex-1 min-w-0">
            <div className={`flex items-center gap-3 flex-wrap ${isRtl ? 'flex-row-reverse' : ''}`}>
              <h1 className="text-xl font-bold text-[#002819]">{t('transfers.transfer') || 'Transfer'} #{transfer.id}</h1>
              <TransferStatusBadge status={transfer.status} t={t} />
            </div>
            <p className="text-sm text-[#717973] mt-1">{formatDate(transfer.created_at)}</p>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left column - Users & Animals */}
        <div className="lg:col-span-2 space-y-6">
          {/* From / To cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="bg-white rounded-2xl shadow-sm border border-[#E3E3DE] p-5">
              <h4 className="text-xs font-semibold text-[#404943]/60 uppercase tracking-wider mb-3">{t('transfers.from') || 'From'}</h4>
              <div className={`flex items-center gap-3 ${isRtl ? 'flex-row-reverse' : ''}`}>
                <div
                  className="w-10 h-10 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                  style={{ backgroundColor: getAvatarColor(fromUser.id) }}
                >
                  {getInitials(fromUser.name || fromUser.email || '?')}
                </div>
                <div className="min-w-0">
                  <p className="text-sm font-semibold text-[#002819] truncate">{fromUser.name || '—'}</p>
                  <p className="text-xs text-[#404943]/50 truncate">{fromUser.email || ''}</p>
                </div>
              </div>
            </div>
            <div className="bg-white rounded-2xl shadow-sm border border-[#E3E3DE] p-5">
              <h4 className="text-xs font-semibold text-[#404943]/60 uppercase tracking-wider mb-3">{t('transfers.to') || 'To'}</h4>
              <div className={`flex items-center gap-3 ${isRtl ? 'flex-row-reverse' : ''}`}>
                <div
                  className="w-10 h-10 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                  style={{ backgroundColor: getAvatarColor(toUser.id) }}
                >
                  {getInitials(toUser.name || toUser.email || '?')}
                </div>
                <div className="min-w-0">
                  <p className="text-sm font-semibold text-[#002819] truncate">{toUser.name || '—'}</p>
                  <p className="text-xs text-[#404943]/50 truncate">{toUser.email || ''}</p>
                </div>
              </div>
            </div>
          </div>

          {/* Animals list */}
          <div className="bg-white rounded-2xl shadow-sm border border-[#E3E3DE] p-5">
            <h4 className="text-xs font-semibold text-[#404943]/60 uppercase tracking-wider mb-3">
              {t('transfers.animals') || 'Animals'} ({animals.length})
            </h4>
            {animals.length === 0 ? (
              <p className="text-sm text-[#404943]/40 italic">{t('common.none') || 'None'}</p>
            ) : (
              <div className="space-y-2">
                {animals.map(animal => (
                  <div key={animal.id} className={`flex items-center gap-3 px-3 py-2 rounded-xl bg-[#FAF5F1] ${isRtl ? 'flex-row-reverse' : ''}`}>
                    <MaterialSymbol icon="pets" size={18} className="text-[#D4AF37]" />
                    <span className="text-sm font-medium text-[#002819] flex-1">{animal.name || `#${animal.animal_id}`}</span>
                    <span className="text-xs text-[#404943]/50">{animal.species || ''}</span>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Notes */}
          {transfer.notes && (
            <div className="bg-white rounded-2xl shadow-sm border border-[#E3E3DE] p-5">
              <h4 className="text-xs font-semibold text-[#404943]/60 uppercase tracking-wider mb-2">{t('transfers.notes') || 'Notes'}</h4>
              <p className="text-sm text-[#404943] whitespace-pre-wrap">{transfer.notes}</p>
            </div>
          )}

          {/* Timeline */}
          <div className="bg-white rounded-2xl shadow-sm border border-[#E3E3DE] p-5">
            <h4 className="text-xs font-semibold text-[#404943]/60 uppercase tracking-wider mb-4">{t('transfers.timeline') || 'Timeline'}</h4>
            <div className="space-y-4">
              {[
                { label: t('transfers.statusCreated') || 'Created', key: 'created_at', icon: 'add_circle' },
                { label: t('transfers.statusAccepted') || 'Accepted', key: 'accepted_at', icon: 'check_circle' },
                { label: t('transfers.statusCompleted') || 'Completed', key: 'completed_at', icon: 'task_alt' },
              ].map((step, idx) => {
                const dateVal = transfer[step.key];
                const isPast = !!dateVal;
                return (
                  <div key={step.key} className={`flex items-start gap-3 ${isRtl ? 'flex-row-reverse' : ''}`}>
                    <div className={`flex flex-col items-center ${isRtl ? 'ml-3' : 'mr-3'}`}>
                      <div className={`w-8 h-8 rounded-full flex items-center justify-center ${
                        isPast ? 'bg-[#002819] text-white' : 'bg-gray-100 text-gray-400'
                      }`}>
                        <MaterialSymbol icon={step.icon} size={16} />
                      </div>
                      {idx < 2 && <div className={`w-0.5 h-6 ${isPast ? 'bg-[#002819]/30' : 'bg-gray-200'}`} />}
                    </div>
                    <div className="flex-1 min-w-0 pt-1">
                      <p className={`text-sm font-medium ${isPast ? 'text-[#002819]' : 'text-[#404943]/40'}`}>{step.label}</p>
                      {dateVal && <p className="text-xs text-[#717973] mt-0.5">{formatDate(dateVal)}</p>}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </div>

        {/* Right column - Details & Actions */}
        <div className="space-y-6">
          {/* Price */}
          {transfer.agreed_price && (
            <div className="bg-white rounded-2xl shadow-sm border border-[#E3E3DE] p-5">
              <h4 className="text-xs font-semibold text-[#404943]/60 uppercase tracking-wider mb-2">{t('transfers.agreedPrice') || 'Agreed Price'}</h4>
              <p className="text-2xl font-bold text-[#002819]">SAR {parseFloat(transfer.agreed_price).toFixed(2)}</p>
            </div>
          )}

          {/* Commission */}
          {(commission.amount || transfer.commission_amount) && (
            <div className="bg-white rounded-2xl shadow-sm border border-[#E3E3DE] p-5">
              <h4 className="text-xs font-semibold text-[#404943]/60 uppercase tracking-wider mb-2">{t('transfers.commission') || 'Commission'}</h4>
              <div className="space-y-2">
                <div className={`flex items-center justify-between text-sm ${isRtl ? 'flex-row-reverse' : ''}`}>
                  <span className="text-[#404943]/70">{t('transfers.commissionPercentage') || 'Percentage'}</span>
                  <span className="font-semibold text-[#002819]">{commission.percentage || 5}%</span>
                </div>
                <div className={`flex items-center justify-between text-sm ${isRtl ? 'flex-row-reverse' : ''}`}>
                  <span className="text-[#404943]/70">{t('transfers.commissionAmount') || 'Amount'}</span>
                  <span className="font-semibold text-[#002819]">SAR {parseFloat(commission.amount || transfer.commission_amount || 0).toFixed(2)}</span>
                </div>
                <div className={`flex items-center justify-between text-sm ${isRtl ? 'flex-row-reverse' : ''}`}>
                  <span className="text-[#404943]/70">{t('transfers.commissionPaid') || 'Paid'}</span>
                  <span className={`font-semibold ${commission.paid || transfer.commission_paid ? 'text-emerald-600' : 'text-amber-600'}`}>
                    {commission.paid || transfer.commission_paid ? (t('common.yes') || 'Yes') : (t('common.no') || 'No')}
                  </span>
                </div>
              </div>
            </div>
          )}

          {/* Actions */}
          {(canCancel || canAcceptReject) && (
            <div className="bg-white rounded-2xl shadow-sm border border-[#E3E3DE] p-5 space-y-3">
              <h4 className="text-xs font-semibold text-[#404943]/60 uppercase tracking-wider mb-1">{t('common.actions') || 'Actions'}</h4>
              {canAcceptReject && (
                <>
                  <button
                    onClick={() => handleAction('accept')}
                    disabled={actionLoading === 'accept'}
                    className="w-full py-3 bg-emerald-500 text-white rounded-xl font-semibold text-sm hover:bg-emerald-600 transition disabled:opacity-50 flex items-center justify-center gap-2"
                  >
                    {actionLoading === 'accept' ? (
                      <MaterialSymbol icon="progress_activity" size={18} className="animate-spin" />
                    ) : (
                      <MaterialSymbol icon="check_circle" size={18} />
                    )}
                    {t('transfers.accept') || 'Accept Transfer'}
                  </button>
                  <button
                    onClick={() => setShowRejectModal(true)}
                    disabled={actionLoading === 'reject'}
                    className="w-full py-3 bg-red-500 text-white rounded-xl font-semibold text-sm hover:bg-red-600 transition disabled:opacity-50 flex items-center justify-center gap-2"
                  >
                    <MaterialSymbol icon="cancel" size={18} />
                    {t('transfers.reject') || 'Reject Transfer'}
                  </button>
                </>
              )}
              {canCancel && (
                <button
                  onClick={() => handleAction('cancel')}
                  disabled={actionLoading === 'cancel'}
                  className="w-full py-3 bg-gray-100 text-gray-700 rounded-xl font-semibold text-sm hover:bg-gray-200 transition disabled:opacity-50 flex items-center justify-center gap-2"
                >
                  {actionLoading === 'cancel' ? (
                    <MaterialSymbol icon="progress_activity" size={18} className="animate-spin" />
                  ) : (
                    <MaterialSymbol icon="close" size={18} />
                  )}
                  {t('transfers.cancel') || 'Cancel Transfer'}
                </button>
              )}
              {transfer.status === 'accepted' && !commission.paid && !transfer.commission_paid && (
                <div className="px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-medium flex items-center gap-2">
                  <MaterialSymbol icon="info" size={16} />
                  {t('transfers.commissionPending') || 'Commission payment is pending'}
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Reject reason modal */}
      {showRejectModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm" onClick={() => setShowRejectModal(false)}>
          <div className="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-xl" onClick={e => e.stopPropagation()}>
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-bold text-[#002819]">{t('transfers.rejectTransfer') || 'Reject Transfer'}</h3>
              <button onClick={() => setShowRejectModal(false)} className="p-2 hover:bg-gray-100 rounded-lg">
                <MaterialSymbol icon="close" size={20} />
              </button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-[#404943] mb-1">{t('transfers.rejectionReason') || 'Reason (optional)'}</label>
                <textarea
                  value={rejectionReason}
                  onChange={e => setRejectionReason(e.target.value)}
                  rows={3}
                  placeholder={t('transfers.rejectionReasonPlaceholder') || 'Enter reason...'}
                  className="w-full bg-[#FAF5F1] border border-[#E3E3DE] rounded-xl px-4 py-2.5 text-sm text-[#404943] placeholder:text-[#404943]/40 focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] resize-none"
                />
              </div>
              <div className="flex gap-3">
                <button
                  onClick={() => setShowRejectModal(false)}
                  className="flex-1 py-3 bg-[#F4F4EF] text-[#002819] rounded-xl font-semibold text-sm hover:bg-[#E3E3DE] transition"
                >
                  {t('common.cancel') || 'Cancel'}
                </button>
                <button
                  onClick={() => handleAction('reject')}
                  disabled={actionLoading === 'reject'}
                  className="flex-1 py-3 bg-red-500 text-white rounded-xl font-semibold text-sm hover:bg-red-600 transition disabled:opacity-50 flex items-center justify-center gap-2"
                >
                  {actionLoading === 'reject' ? (
                    <MaterialSymbol icon="progress_activity" size={18} className="animate-spin" />
                  ) : null}
                  {t('transfers.reject') || 'Reject'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
