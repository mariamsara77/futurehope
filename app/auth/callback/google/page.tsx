"use client";

import { useEffect, useState } from "react";
import { ApiError, completeGoogleLogin } from "@/lib/api";

export default function GoogleCallbackPage() {
  const [message, setMessage] = useState("Google লগইন সম্পন্ন করা হচ্ছে...");

  useEffect(() => {
    let active = true;

    async function finish() {
      const params = new URLSearchParams(window.location.hash.replace(/^#/, ""));
      const code = params.get("code");
      const error = params.get("error");
      const reason = params.get("reason");

      if (error) {
        throw new ApiError("Google লগইন সম্পন্ন হয়নি।" + (reason ? " (" + reason + ")" : ""), 400);
      }
      if (!code) {
        throw new ApiError("Google লগইনের নিরাপদ code পাওয়া যায়নি।", 400);
      }

      const result = await completeGoogleLogin(code);
      if (!active) return;

      if (window.opener && !window.opener.closed) {
        window.opener.postMessage(
          { type: "futurehope-google-auth", user: result.user },
          window.location.origin,
        );
        setMessage("লগইন সফল হয়েছে। এই উইন্ডোটি বন্ধ হচ্ছে...");
        window.setTimeout(() => window.close(), 400);
      } else {
        window.location.replace("/");
      }
    }

    finish().catch((error) => {
      if (!active) return;
      const text = error instanceof ApiError ? error.message : "Google লগইন সম্পন্ন করা যায়নি।";
      setMessage(text);
      if (window.opener && !window.opener.closed) {
        window.opener.postMessage(
          { type: "futurehope-google-auth-error", message: text },
          window.location.origin,
        );
      }
    });

    return () => {
      active = false;
    };
  }, []);

  return (
    <main className="flex min-h-screen items-center justify-center bg-zinc-50 px-4">
      <div className="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-8 text-center shadow-xl">
        <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-2xl">✓</div>
        <h1 className="mt-5 text-2xl font-bold text-zinc-950">Google লগইন</h1>
        <p className="mt-3 text-sm leading-7 text-zinc-500">{message}</p>
      </div>
    </main>
  );
}