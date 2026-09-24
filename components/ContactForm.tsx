"use client";

import { FormEvent, useState } from "react";
import { ApiError, sendContactMessage } from "@/lib/api";

export default function ContactForm({ compact = false }: { compact?: boolean }) {
  const [form, setForm] = useState({ name: "", email: "", phone: "", subject: "", message: "" });
  const [busy, setBusy] = useState(false);
  const [feedback, setFeedback] = useState<{ type: "success" | "error"; text: string } | null>(null);

  function update(key: keyof typeof form, value: string) {
    setForm((current) => ({ ...current, [key]: value }));
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFeedback(null);
    setBusy(true);
    try {
      await sendContactMessage(form);
      setForm({ name: "", email: "", phone: "", subject: "", message: "" });
      setFeedback({ type: "success", text: "আপনার বার্তা সফলভাবে পাঠানো হয়েছে। আমরা প্রয়োজন অনুযায়ী আপনার সঙ্গে যোগাযোগ করব।" });
    } catch (error) {
      setFeedback({
        type: "error",
        text: error instanceof ApiError ? error.message : "বার্তা পাঠানো যায়নি। কিছুক্ষণ পরে আবার চেষ্টা করুন।",
      });
    } finally {
      setBusy(false);
    }
  }

  return (
    <form onSubmit={submit} className={compact ? "space-y-4" : "space-y-5"}>
      <div className="grid gap-4 sm:grid-cols-2">
        <label className="block">
          <span className="mb-1.5 block text-sm font-semibold text-zinc-700">নাম *</span>
          <input required maxLength={100} value={form.name} onChange={(e) => update("name", e.target.value)} className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="আপনার নাম" />
        </label>
        <label className="block">
          <span className="mb-1.5 block text-sm font-semibold text-zinc-700">ইমেইল *</span>
          <input required type="email" maxLength={255} value={form.email} onChange={(e) => update("email", e.target.value)} className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="name@example.com" />
        </label>
      </div>
      <div className="grid gap-4 sm:grid-cols-2">
        <label className="block">
          <span className="mb-1.5 block text-sm font-semibold text-zinc-700">ফোন</span>
          <input maxLength={30} value={form.phone} onChange={(e) => update("phone", e.target.value)} className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="০১XXXXXXXXX" />
        </label>
        <label className="block">
          <span className="mb-1.5 block text-sm font-semibold text-zinc-700">বিষয় *</span>
          <input required maxLength={200} value={form.subject} onChange={(e) => update("subject", e.target.value)} className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="কী বিষয়ে যোগাযোগ করছেন?" />
        </label>
      </div>
      <label className="block">
        <span className="mb-1.5 block text-sm font-semibold text-zinc-700">বার্তা *</span>
        <textarea required minLength={10} maxLength={5000} rows={compact ? 5 : 7} value={form.message} onChange={(e) => update("message", e.target.value)} className="w-full resize-y rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 leading-7 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="আপনার বার্তা লিখুন..." />
      </label>
      {feedback && (
        <div role="alert" className={feedback.type === "success" ? "rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" : "rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"}>
          {feedback.text}
        </div>
      )}
      <button type="submit" disabled={busy} className="w-full rounded-xl bg-emerald-600 px-5 py-3.5 font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
        {busy ? "বার্তা পাঠানো হচ্ছে..." : "বার্তা পাঠান"}
      </button>
    </form>
  );
}