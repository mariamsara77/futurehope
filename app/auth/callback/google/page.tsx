"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { ApiError } from "@/lib/api";
import { useAuth } from "@/components/AuthProvider";

export default function GoogleCallbackPage() {
  const router = useRouter();
  const { completeGoogleLogin } = useAuth();
  const [message, setMessage] = useState("Google account যাচাই করা হচ্ছে…");

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const code = params.get("code");
    const error = params.get("error");
    const reason = params.get("reason");

    if (error) {
      setMessage(reason ? "Google login সম্পন্ন হয়নি (" + reason + "). আবার চেষ্টা করুন।" : "Google login সম্পন্ন হয়নি। আবার চেষ্টা করুন।");
      return;
    }

    if (!code) {
      setMessage("Google login code পাওয়া যায়নি। আবার Google দিয়ে লগইন করুন।");
      return;
    }

    let cancelled = false;

    (async () => {
      try {
        await completeGoogleLogin(code);
        if (!cancelled) router.replace("/");
      } catch (err) {
        if (!cancelled) setMessage(err instanceof ApiError ? err.message : "Google login সম্পন্ন করা যায়নি। আবার চেষ্টা করুন।");
      }
    })();

    return () => { cancelled = true; };
  }, [router, completeGoogleLogin]);

  return (
    <main className="mx-auto flex min-h-[60vh] max-w-xl items-center justify-center px-4 py-16 text-center">
      <div className="w-full rounded-3xl bg-white p-8 shadow-sm ring-1 ring-zinc-200 sm:p-10">
        <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-xl font-black text-emerald-700">G</div>
        <h1 className="mt-6 text-2xl font-bold">Google Login</h1>
        <p className="mt-3 leading-7 text-zinc-500">{message}</p>
        {message !== "Google account যাচাই করা হচ্ছে…" && (
          <Link href="/" className="mt-6 inline-flex rounded-xl bg-emerald-600 px-5 py-3 font-bold text-white hover:bg-emerald-700">হোমে ফিরে যান</Link>
        )}
      </div>
    </main>
  );
}