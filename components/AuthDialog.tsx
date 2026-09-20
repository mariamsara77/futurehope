"use client";

import { FormEvent, useEffect, useState } from "react";
import { ApiError } from "@/lib/api";
import { useAuth } from "@/components/AuthProvider";

export default function AuthDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { login } = useAuth();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (!open) {
      setEmail("");
      setPassword("");
      setError("");
      setBusy(false);
    }
  }, [open]);

  useEffect(() => {
    if (!open) return;
    const handler = (event: KeyboardEvent) => event.key === "Escape" && !busy && onClose();
    window.addEventListener("keydown", handler);
    return () => window.removeEventListener("keydown", handler);
  }, [open, busy, onClose]);

  if (!open) return null;

  async function submit(event: FormEvent) {
    event.preventDefault();
    setError("");
    setBusy(true);
    try {
      await login(email.trim(), password);
      onClose();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "লগইন করা যায়নি। আবার চেষ্টা করুন।");
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center bg-zinc-950/50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="login-title">
      <button aria-label="Close login" className="absolute inset-0 cursor-default" onClick={() => !busy && onClose()} />
      <div className="relative z-10 w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-6 shadow-2xl sm:p-8">
        <div className="mb-7">
          <div className="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-xl text-white">↗</div>
          <h2 id="login-title" className="text-2xl font-bold text-zinc-950">লগইন করুন</h2>
          <p className="mt-1 text-sm text-zinc-500">আপনার Future Hope অ্যাকাউন্টে প্রবেশ করুন।</p>
        </div>
        <form onSubmit={submit} className="space-y-4">
          <label className="block">
            <span className="mb-1.5 block text-sm font-semibold text-zinc-700">ইমেইল</span>
            <input value={email} onChange={(e) => setEmail(e.target.value)} type="email" autoComplete="email" required className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="আপনার ইমেইল" />
          </label>
          <label className="block">
            <span className="mb-1.5 block text-sm font-semibold text-zinc-700">পাসওয়ার্ড</span>
            <input value={password} onChange={(e) => setPassword(e.target.value)} type="password" autoComplete="current-password" required className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="আপনার পাসওয়ার্ড" />
          </label>
          {error && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{error}</div>}
          <button disabled={busy} className="w-full rounded-xl bg-emerald-600 px-4 py-3.5 font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
            {busy ? "লগইন হচ্ছে..." : "লগইন"}
          </button>
        </form>
        <p className="mt-5 text-center text-xs leading-5 text-zinc-400">নতুন অ্যাকাউন্ট তৈরি করতে বর্তমানে backend-এ registration endpoint প্রকাশিত নেই।</p>
        <button onClick={() => !busy && onClose()} className="mt-4 w-full text-sm font-medium text-zinc-500 hover:text-zinc-900">বন্ধ করুন</button>
      </div>
    </div>
  );
}
