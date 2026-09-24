"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { ApiError, register as registerApi } from "@/lib/api";
import { useAuth } from "@/components/AuthProvider";

export default function AuthDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { login, googleLogin } = useAuth();
  const [mode, setMode] = useState<"login" | "register">("login");
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  const closeAndReset = useCallback(() => {
    setMode("login");
    setName("");
    setEmail("");
    setPassword("");
    setConfirmation("");
    setError("");
    setBusy(false);
    onClose();
  }, [onClose]);

  useEffect(() => {
    if (!open) return;
    const handler = (event: KeyboardEvent) => event.key === "Escape" && !busy && closeAndReset();
    window.addEventListener("keydown", handler);
    return () => window.removeEventListener("keydown", handler);
  }, [open, busy, closeAndReset]);

  if (!open || typeof document === "undefined") return null;

  async function submit(event: FormEvent) {
    event.preventDefault();
    setError("");

    if (mode === "register" && password !== confirmation) {
      setError("পাসওয়ার্ড এবং নিশ্চিত পাসওয়ার্ড একই হতে হবে।");
      return;
    }

    setBusy(true);
    try {
      if (mode === "register") {
        const result = await registerApi(name.trim(), email.trim(), password, confirmation);
        if (!result.user) throw new ApiError("রেজিস্ট্রেশন সফল হলেও user data পাওয়া যায়নি।");
      } else {
        await login(email.trim(), password);
      }
      closeAndReset();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "অনুরোধটি সম্পন্ন করা যায়নি। আবার চেষ্টা করুন।");
    } finally {
      setBusy(false);
    }
  }

  async function googleSubmit() {
    setError("");
    setBusy(true);
    try {
      await googleLogin();
      closeAndReset();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Google লগইন করা যায়নি। আবার চেষ্টা করুন।");
    } finally {
      setBusy(false);
    }
  }

  const dialog = (
    <div
      className="fixed inset-0 z-[9999] isolate flex items-center justify-center overflow-y-auto bg-zinc-950/50 p-4 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      aria-labelledby="auth-title"
    >
      <button
        aria-label="Close authentication"
        className="absolute inset-0 cursor-default"
        onClick={() => !busy && closeAndReset()}
      />
      <div className="relative z-10 max-h-[92vh] w-full max-w-md overflow-y-auto rounded-3xl border border-zinc-200 bg-white p-6 shadow-2xl sm:p-8">
        <div className="mb-6">
          <div className="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-xl text-white">↗</div>
          <h2 id="auth-title" className="text-2xl font-bold text-zinc-950">{mode === "login" ? "লগইন করুন" : "অ্যাকাউন্ট তৈরি করুন"}</h2>
          <p className="mt-1 text-sm text-zinc-500">{mode === "login" ? "আপনার Future Hope অ্যাকাউন্টে প্রবেশ করুন।" : "কয়েকটি তথ্য দিয়ে আপনার Future Hope অ্যাকাউন্ট তৈরি করুন।"}</p>
        </div>

        <button type="button" disabled={busy} onClick={() => void googleSubmit()} className="flex w-full items-center justify-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3.5 font-semibold text-zinc-800 transition hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-60">
          <span className="text-lg font-bold">G</span>
          Google দিয়ে {mode === "login" ? "লগইন" : "সাইন আপ"}
        </button>

        <div className="my-5 flex items-center gap-3 text-xs text-zinc-400">
          <span className="h-px flex-1 bg-zinc-200" />
          অথবা
          <span className="h-px flex-1 bg-zinc-200" />
        </div>

        <form onSubmit={submit} className="space-y-4">
          {mode === "register" && (
            <label className="block">
              <span className="mb-1.5 block text-sm font-semibold text-zinc-700">নাম</span>
              <input value={name} onChange={(e) => setName(e.target.value)} type="text" autoComplete="name" required maxLength={100} className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="আপনার নাম" />
            </label>
          )}
          <label className="block">
            <span className="mb-1.5 block text-sm font-semibold text-zinc-700">ইমেইল</span>
            <input value={email} onChange={(e) => setEmail(e.target.value)} type="email" autoComplete="email" required className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="আপনার ইমেইল" />
          </label>
          <label className="block">
            <span className="mb-1.5 block text-sm font-semibold text-zinc-700">পাসওয়ার্ড</span>
            <input value={password} onChange={(e) => setPassword(e.target.value)} type="password" autoComplete={mode === "login" ? "current-password" : "new-password"} required minLength={8} className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="কমপক্ষে ৮ অক্ষর" />
          </label>
          {mode === "register" && (
            <label className="block">
              <span className="mb-1.5 block text-sm font-semibold text-zinc-700">পাসওয়ার্ড নিশ্চিত করুন</span>
              <input value={confirmation} onChange={(e) => setConfirmation(e.target.value)} type="password" autoComplete="new-password" required minLength={8} className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="পাসওয়ার্ড আবার লিখুন" />
            </label>
          )}
          {error && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{error}</div>}
          <button disabled={busy} className="w-full rounded-xl bg-emerald-600 px-4 py-3.5 font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
            {busy ? (mode === "login" ? "লগইন হচ্ছে..." : "অ্যাকাউন্ট তৈরি হচ্ছে...") : mode === "login" ? "লগইন" : "রেজিস্টার"}
          </button>
        </form>

        <button type="button" disabled={busy} onClick={() => { setMode(mode === "login" ? "register" : "login"); setError(""); }} className="mt-5 w-full text-sm font-semibold text-emerald-700 hover:text-emerald-800 disabled:opacity-60">
          {mode === "login" ? "নতুন অ্যাকাউন্ট তৈরি করুন" : "আগের অ্যাকাউন্টে লগইন করুন"}
        </button>
        <button type="button" onClick={() => !busy && closeAndReset()} className="mt-3 w-full text-sm font-medium text-zinc-500 hover:text-zinc-900">বন্ধ করুন</button>
      </div>
    </div>
  );

  return createPortal(dialog, document.body);
}
