"use client";

import { FormEvent, useEffect, useState } from "react";
import Link from "next/link";
import { ApiError, resetPassword } from "@/lib/api";

export default function ResetPasswordPage() {
  const [token, setToken] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    setToken(params.get("token") || "");
    setEmail(params.get("email") || "");
  }, []);

  async function submit(event: FormEvent) {
    event.preventDefault();
    setError("");
    setSuccess("");

    if (!token || !email) {
      setError("এই পাসওয়ার্ড রিসেট লিংকটি অসম্পূর্ণ। নতুন রিসেট লিংক নিন।");
      return;
    }

    if (password !== confirmation) {
      setError("পাসওয়ার্ড দুটো একই হতে হবে।");
      return;
    }

    setBusy(true);
    try {
      const result = await resetPassword(token, email.trim(), password, confirmation);
      setSuccess(result.message || "পাসওয়ার্ড সফলভাবে রিসেট হয়েছে। এখন নতুন পাসওয়ার্ড দিয়ে লগইন করুন।");
      setPassword("");
      setConfirmation("");
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "পাসওয়ার্ড রিসেট করা যায়নি। আবার চেষ্টা করুন।");
    } finally {
      setBusy(false);
    }
  }

  return (
    <main className="flex min-h-[70vh] items-center justify-center px-4 py-16">
      <div className="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
        <div className="mb-6">
          <div className="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-xl text-white">↗</div>
          <h1 className="text-2xl font-bold text-zinc-950">নতুন পাসওয়ার্ড সেট করুন</h1>
          <p className="mt-2 text-sm leading-6 text-zinc-500">আপনার অ্যাকাউন্টের জন্য একটি নতুন পাসওয়ার্ড দিন।</p>
        </div>

        <form onSubmit={submit} className="space-y-4">
          <label className="block">
            <span className="mb-1.5 block text-sm font-semibold text-zinc-700">ইমেইল</span>
            <input value={email} onChange={(e) => setEmail(e.target.value)} type="email" autoComplete="email" required className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="আপনার ইমেইল" />
          </label>

          <label className="block">
            <span className="mb-1.5 block text-sm font-semibold text-zinc-700">নতুন পাসওয়ার্ড</span>
            <input value={password} onChange={(e) => setPassword(e.target.value)} type="password" autoComplete="new-password" required minLength={8} className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="কমপক্ষে ৮ অক্ষর" />
          </label>

          <label className="block">
            <span className="mb-1.5 block text-sm font-semibold text-zinc-700">পাসওয়ার্ড নিশ্চিত করুন</span>
            <input value={confirmation} onChange={(e) => setConfirmation(e.target.value)} type="password" autoComplete="new-password" required minLength={8} className="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="পাসওয়ার্ড আবার লিখুন" />
          </label>

          {error && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{error}</div>}
          {success && <div role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{success}</div>}

          {!success && (
            <button disabled={busy} className="w-full rounded-xl bg-emerald-600 px-4 py-3.5 font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
              {busy ? "পাসওয়ার্ড পরিবর্তন হচ্ছে..." : "পাসওয়ার্ড পরিবর্তন করুন"}
            </button>
          )}
        </form>

        <Link href="/" className="mt-5 block text-center text-sm font-semibold text-emerald-700 hover:text-emerald-800">
          হোমপেজে ফিরে যান
        </Link>
      </div>
    </main>
  );
}
