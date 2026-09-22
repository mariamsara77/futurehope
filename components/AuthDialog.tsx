"use client";

import { FormEvent, useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { ApiError } from "@/lib/api";
import { useAuth } from "@/components/AuthProvider";

export default function AuthDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { login, register } = useAuth();
  const [mounted, setMounted] = useState(false);
  const [mode, setMode] = useState<"login" | "register">("login");
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => setMounted(true), []);

  useEffect(() => {
    if (!open) {
      setMode("login");
      setName("");
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
      if (mode === "login") await login(email.trim(), password);
      else await register(name.trim(), email.trim(), password);
      onClose();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "অনুরোধটি সম্পন্ন করা যায়নি।");
    } finally {
      setBusy(false);
    }
  }

  return createPortal(
    <div className="fixed inset-0 z-[9999] flex min-h-screen items-center justify-center bg-zinc-950/65 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
      <button aria-label="বন্ধ করুন" className="absolute inset-0 h-full w-full" onClick={() => !busy && onClose()} />
      <div className="relative z-10 w-full max-w-lg rounded-3xl border border-zinc-200 bg-white p-6 shadow-2xl sm:p-8">
        <div className="mb-7 flex items-start justify-between gap-5">
          <div>
            <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-600 text-xl font-bold text-white">FH</div>
            <h2 className="text-2xl font-bold text-zinc-950 sm:text-3xl">{mode === "login" ? "আপনার অ্যাকাউন্টে লগইন" : "নতুন অ্যাকাউন্ট তৈরি করুন"}</h2>
            <p className="mt-2 text-sm leading-6 text-zinc-500">
              {mode === "login" ? "সদস্য হিসেবে আপনার profile ও ভোটের সুবিধা ব্যবহার করুন।" : "অ্যাকাউন্ট তৈরি করে নিজের profile জমা দিন এবং Foundation member হলে কাজে ভোট দিন।"}
            </p>
          </div>
          <button type="button" disabled={busy} onClick={onClose} className="rounded-xl p-2 text-2xl text-zinc-400 hover:bg-zinc-100">×</button>
        </div>

        <form onSubmit={submit} className="space-y-5">
          {mode === "register" && (
            <label className="block">
              <span className="mb-2 block text-sm font-semibold text-zinc-700">নাম</span>
              <input value={name} onChange={(e) => setName(e.target.value)} required autoComplete="name" className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3.5 outline-none focus:border-emerald-500 focus:bg-white" placeholder="আপনার নাম" />
            </label>
          )}

          <label className="block">
            <span className="mb-2 block text-sm font-semibold text-zinc-700">ইমেইল</span>
            <input value={email} onChange={(e) => setEmail(e.target.value)} type="email" required autoComplete="email" className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3.5 outline-none focus:border-emerald-500 focus:bg-white" placeholder="name@example.com" />
          </label>

          <label className="block">
            <span className="mb-2 block text-sm font-semibold text-zinc-700">পাসওয়ার্ড</span>
            <input value={password} onChange={(e) => setPassword(e.target.value)} type="password" minLength={8} required autoComplete={mode === "login" ? "current-password" : "new-password"} className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3.5 outline-none focus:border-emerald-500 focus:bg-white" placeholder="কমপক্ষে ৮ অক্ষর" />
          </label>

          {error && <div role="alert" className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{error}</div>}

          <button disabled={busy} className="w-full rounded-2xl bg-emerald-600 px-4 py-3.5 font-bold text-white hover:bg-emerald-700 disabled:opacity-60">
            {busy ? "অপেক্ষা করুন..." : mode === "login" ? "লগইন করুন" : "অ্যাকাউন্ট তৈরি করুন"}
          </button>
        </form>

        <button
          type="button"
          onClick={() => { setMode(mode === "login" ? "register" : "login"); setError(""); }}
          className="mt-5 w-full text-center text-sm font-semibold text-emerald-700 hover:underline"
        >
          {mode === "login" ? "নতুন অ্যাকাউন্ট তৈরি করবেন?" : "আগেই অ্যাকাউন্ট আছে? লগইন করুন"}
        </button>
      </div>
    </div>,
    document.body,
  );
}
