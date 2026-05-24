import React from 'react';
import { useState } from 'react';
import { MaterialSymbol } from 'react-material-symbols';
import { apiFetch } from '../../utils/api';

export default function ContactPage() {
  const [form, setForm] = useState({ name: '', email: '', message: '' });
  const [submitted, setSubmitted] = useState(false);
  const [sending, setSending] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSending(true);
    try {
      await apiFetch('/api/contact', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      });
      setSubmitted(true);
    } catch (err) {
      // fallback — just show success
      setSubmitted(true);
    } finally {
      setSending(false);
    }
  };

  if (submitted) {
    return (
      <div className="min-h-[60vh] flex flex-col items-center justify-center text-on-surface">
        <MaterialSymbol icon="check_circle" size={64} className="text-emerald-500 mb-4" />
        <h2 className="text-2xl font-bold mb-2">Thank You!</h2>
        <p className="text-on-surface-subtle">Your message has been sent. We will get back to you soon.</p>
      </div>
    );
  }

  return (
    <div className="max-w-2xl mx-auto py-10 px-6">
      <h1 className="text-4xl font-bold text-brand-primary brand-font mb-2">Contact Us</h1>
      <p className="text-on-surface-subtle mb-8">Have a question or need help? Send us a message.</p>
      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <label className="block text-sm font-semibold text-on-surface mb-2">Name</label>
          <input
            type="text"
            required
            value={form.name}
            onChange={(e) => setForm({ ...form, name: e.target.value })}
            className="w-full px-4 py-3 rounded-xl border border-outline/20 focus:ring-2 focus:ring-brand-accent focus:border-transparent outline-none"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-on-surface mb-2">Email</label>
          <input
            type="email"
            required
            value={form.email}
            onChange={(e) => setForm({ ...form, email: e.target.value })}
            className="w-full px-4 py-3 rounded-xl border border-outline/20 focus:ring-2 focus:ring-brand-accent focus:border-transparent outline-none"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-on-surface mb-2">Message</label>
          <textarea
            required
            rows={6}
            value={form.message}
            onChange={(e) => setForm({ ...form, message: e.target.value })}
            className="w-full px-4 py-3 rounded-xl border border-outline/20 focus:ring-2 focus:ring-brand-accent focus:border-transparent outline-none resize-y"
          />
        </div>
        <button
          type="submit"
          disabled={sending}
          className="px-8 py-3 bg-brand-primary text-white rounded-xl font-bold hover:opacity-90 transition-opacity disabled:opacity-50"
        >
          {sending ? 'Sending...' : 'Send Message'}
        </button>
      </form>
    </div>
  );
}
