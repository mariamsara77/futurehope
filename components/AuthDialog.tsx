"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { ApiError, register as registerApi, sendPasswordResetLink } from "@/lib/api";
import { useAuth } from "@/components/AuthProvider";

export default function AuthDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { login, googleLogin } = useAuth();
  const [mode, setMode] = useState<"login" | "register" | "forgot">("login");
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  const [resetSent, setResetSent] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmation, setShowConfirmation] = useState(false);

  const closeAndReset = useCallback(() => {
    setMode("login");
    setName("");
    setEmail("");
    setPassword("");
    setConfirmation("");
    setError("");
    setResetSent(false);
    setShowPassword(false);
    setShowConfirmation(false);
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

    if (mode === "forgot") {
      setBusy(true);
      try {
        await sendPasswordResetLink(email.trim());
        setResetSent(true);
      } catch (err) {
        setError(err instanceof ApiError ? err.message : "অনুরোধটি সম্পন্ন করা যায়নি। আবার চেষ্টা করুন।");
      } finally {
        setBusy(false);
      }
      return;
    }

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
          <h2 id="auth-title" className="text-2xl font-bold text-zinc-950">
            {mode === "login" ? "লগইন করুন" : mode === "register" ? "অ্যাকাউন্ট তৈরি করুন" : "পাসওয়ার্ড রিসেট করুন"}
          </h2>
          <p className="mt-1 text-sm text-zinc-500">
            {mode === "login"
              ? "আপনার Future Hope অ্যাকাউন্টে প্রবেশ করুন।"
              : mode === "register"
                ? "কয়েকটি তথ্য দিয়ে আপনার Future Hope অ্যাকাউন্ট তৈরি করুন।"
                : "আপনার অ্যাকাউন্টের ইমেইল দিন। আমরা পাসওয়ার্ড রিসেট করার লিংক পাঠাব।"}
          </p>
        </div>

        {mode !== "forgot" && (
          <>
            <button type="button" disabled={busy} onClick={() => void googleSubmit()} className="flex w-full items-center justify-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3.5 font-semibold text-zinc-800 transition hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-60">
              <span className="text-lg font-bold">G</span>
              Google দিয়ে {mode === "login" ? "লগইন" : "সাইন আপ"}
            </button>

            <div className="my-5 flex items-center gap-3 text-xs text-zinc-400">
              <span className="h-px flex-1 bg-zinc-200" />
              অথবা
              <span className="h-px flex-1 bg-zinc-200" />
            </div>
          </>
        )}

        <form onSubmit={submit} className="space-y-4">
          {mode === "forgot" && resetSent && (
            <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
              যদি এই ইমেইল দিয়ে একটি অ্যাকাউন্ট থাকে, তাহলে পাসওয়ার্ড রিসেট করার লিংক আপনার ইমেইলে পাঠানো হয়েছে।
            </div>
          )}

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

          {mode !== "forgot" && (
            <label className="block">
              <span className="mb-1.5 block text-sm font-semibold text-zinc-700">পাসওয়ার্ড</span>
              <div className="relative">\n                <input value={password} onChange={(e) => setPassword(e.target.value)} type={showPassword ? "text" : "password"} autoComplete={mode === "login" ? "current-password" : "new-password"} required minLength={mode === "register" ? 8 : undefined} className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 pr-12 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder={mode === "login" ? "আপনার পাসওয়ার্ড" : "কমপক্ষে ৮ অক্ষর"} />\n                <button type="button" onClick={() => setShowPassword((visible) => !visible)} className="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-zinc-500 transition hover:text-zinc-800" aria-label={showPassword ? "পাসওয়ার্ড লুকান" : "পাসওয়ার্ড দেখান"} title={showPassword ? "পাসওয়ার্ড লুকান" : "পাসওয়ার্ড দেখান"}>\n                  {showPassword ? (\n                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="h-5 w-5" aria-hidden="true">\n                      <path d="M3 3l18 18" />\n                      <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83" />\n                      <path d="M9.88 5.09A10.94 10.94 0 0 1 12 4.89c5.2 0 8.81 4.42 9.88 7.11a11.36 11.36 0 0 1-3.05 4.32" />\n                      <path d="M6.61 6.61C4.74 7.91 3.35 9.72 2.12 12c1.07 2.69 4.68 7.11 9.88 7.11 1.58 0 3-.35 4.24-.9" />\n                    </svg>\n                  ) : (\n                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="h-5 w-5" aria-hidden="true">\n                      <path d="M2.12 12c1.07-2.69 4.68-7.11 9.88-7.11S20.81 9.31 21.88 12C20.81 14.69 17.2 19.11 12 19.11S3.19 14.69 2.12 12Z" />\n                      <circle cx="12" cy="12" r="2.7" />\n                    </svg>\n                  )}\n                </button>\n              </div>
            </label>
          )}

          {mode === "register" && (
            <label className="block">
              <span className="mb-1.5 block text-sm font-semibold text-zinc-700">পাসওয়ার্ড নিশ্চিত করুন</span>
              <div className="relative">\n                <input value={confirmation} onChange={(e) => setConfirmation(e.target.value)} type={showConfirmation ? "text" : "password"} autoComplete="new-password" required minLength={8} className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 pr-12 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="পাসওয়ার্ড আবার লিখুন" />\n                <button type="button" onClick={() => setShowConfirmation((visible) => !visible)} className="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-zinc-500 transition hover:text-zinc-800" aria-label={showConfirmation ? "নিশ্চিত পাসওয়ার্ড লুকান" : "নিশ্চিত পাসওয়ার্ড দেখান"} title={showConfirmation ? "নিশ্চিত পাসওয়ার্ড লুকান" : "নিশ্চিত পাসওয়ার্ড দেখান"}>\n                  {showConfirmation ? (\n                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="h-5 w-5" aria-hidden="true">\n                      <path d="M3 3l18 18" />\n                      <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83" />\n                      <path d="M9.88 5.09A10.94 10.94 0 0 1 12 4.89c5.2 0 8.81 4.42 9.88 7.11a11.36 11.36 0 0 1-3.05 4.32" />\n                      <path d="M6.61 6.61C4.74 7.91 3.35 9.72 2.12 12c1.07 2.69 4.68 7.11 9.88 7.11 1.58 0 3-.35 4.24-.9" />\n                    </svg>\n                  ) : (\n                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="h-5 w-5" aria-hidden="true">\n                      <path d="M2.12 12c1.07-2.69 4.68-7.11 9.88-7.11S20.81 9.31 21.88 12C20.81 14.69 17.2 19.11 12 19.11S3.19 14.69 2.12 12Z" />\n                      <circle cx="12" cy="12" r="2.7" />\n                    </svg>\n                  )}\n                </button>\n              </div>
            </label>
          )}

          {mode === "login" && (
            <button type="button" disabled={busy} onClick={() => { setMode("forgot"); setError(""); setResetSent(false); }} className="text-sm font-semibold text-emerald-700 hover:text-emerald-800 disabled:opacity-60">
              পাসওয়ার্ড ভুলে গেছেন?
            </button>
          )}

          {error && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{error}</div>}

          <button disabled={busy} className="w-full rounded-xl bg-emerald-600 px-4 py-3.5 font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
            {busy
              ? mode === "login" ? "লগইন হচ্ছে..." : mode === "forgot" ? "লিংক পাঠানো হচ্ছে..." : "অ্যাকাউন্ট তৈরি হচ্ছে..."
              : mode === "login" ? "লগইন" : mode === "forgot" ? "রিসেট লিংক পাঠান" : "রেজিস্টার"}
          </button>
        </form>

        <button type="button" disabled={busy} onClick={() => { setMode(mode === "forgot" ? "login" : mode === "login" ? "register" : "login"); setError(""); setResetSent(false); }} className="mt-5 w-full text-sm font-semibold text-emerald-700 hover:text-emerald-800 disabled:opacity-60">
          {mode === "forgot" ? "লগইনে ফিরে যান" : mode === "login" ? "নতুন অ্যাকাউন্ট তৈরি করুন" : "আগের অ্যাকাউন্টে লগইন করুন"}
        </button>
        <button type="button" onClick={() => !busy && closeAndReset()} className="mt-3 w-full text-sm font-medium text-zinc-500 hover:text-zinc-900">বন্ধ করুন</button>
      </div>
    </div>
  );

  return createPortal(dialog, document.body);
}
