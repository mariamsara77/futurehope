"use client";

import { FormEvent, useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { ApiError } from "@/lib/api";
import { useAuth } from "@/components/AuthProvider";

export default function AuthDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { login } = useAuth();
  const [mounted, setMounted] = useState(false);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => setMounted(true), []);

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
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    const handler = (event: KeyboardEvent) => event.key === "Escape" && !busy && onClose();
    window.addEventListener("keydown", handler);
    return () => {
      document.body.style.overflow = previousOverflow;
      window.removeEventListener("keydown", handler);
    };
  }, [open, busy, onClose]);

  if (!open || !mounted) return null;

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

  return createPortal(
    <div className="fixed inset-0 z-[9999] flex min-h-screen items-center justify-center overflow-y-auto bg-zinc-950/65 p-4 backdrop-blur-sm sm:p-6" role="dialog" aria-modal="true" aria-labelledby="login-title">
      <button aria-label="লগইন বন্ধ করুন" className="absolute inset-0 h-full w-full cursor-default" onClick={() => !busy && onClose()} />
      <div className="relative z-10 my-auto w-full max-w-lg rounded-3xl border border-zinc-200 bg-white p-6 shadow-2xl sm:p-8">
        <div className="mb-7 flex items-start justify-between gap-5">
          <div>
            <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-600 text-xl font-bold text-white">FH</div>
            <h2 id="login-title" className="text-2xl font-bold text-zinc-950 sm:text-3xl">আপনার অ্যাকাউন্টে লগইন</h2>
            <p className="mt-2 text-sm leading-6 text-zinc-500">সদস্য হিসেবে আপনার ব্যক্তিগত profile ও সংগঠনের তথ্য পরিচালনা করুন।</p>
          </div>
          <button type="button" disabled={busy} onClick={onClose} aria-label="বন্ধ করুন" className="rounded-xl p-2 text-2xl leading-none text-zinc-400 hover:bg-zinc-100 hover:text-zinc-900 disabled:opacity-50">×</button>
        </div>
        <form onSubmit={submit} className="space-y-5">
          <label className="block">
            <span className="mb-2 block text-sm font-semibold text-zinc-700">ইমেইল</span>
            <input value={email} onChange={(e) => setEmail(e.target.value)} type="email" autoComplete="email" required className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3.5 text-base outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="name@example.com" />
          </label>
          <label className="block">
            <span className="mb-2 block text-sm font-semibold text-zinc-700">পাসওয়ার্ড</span>
            <input value={password} onChange={(e) => setPassword(e.target.value)} type="password" autoComplete="current-password" required className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3.5 text-base outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="আপনার পাসওয়ার্ড" />
          </label>
          {error && <div role="alert" className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium leading-6 text-red-700">{error}</div>}
          <button disabled={busy} className="w-full rounded-2xl bg-emerald-600 px-4 py-3.5 font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
            {busy ? "লগইন হচ্ছে..." : "লগইন করুন"}
          </button>
        </form>
        <p className="mt-5 text-center text-xs leading-5 text-zinc-400">Backend-এ registration endpoint না থাকায় এখানে শুধু বর্তমান login API ব্যবহার করা হচ্ছে।</p>
      </div>
    </div>,
    document.body
  );
}
