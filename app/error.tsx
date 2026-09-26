"use client";

import { useEffect } from "react";

export default function Error({ reset }: { error: Error & { digest?: string }; reset: () => void }) {
  useEffect(() => {
    // Keep unexpected rendering failures inside the page shell instead of exposing
    // the default Next.js error screen.
  }, []);

  return (
    <main className="mx-auto flex min-h-[60vh] max-w-2xl items-center justify-center px-4 py-16">
      <section className="w-full rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-zinc-200 sm:p-10">
        <h1 className="text-2xl font-bold text-zinc-950">এই অংশটি এখন লোড করা যাচ্ছে না</h1>
        <p className="mt-3 leading-7 text-zinc-500">সাময়িক সমস্যার কারণে কিছু তথ্য পাওয়া যাচ্ছে না। প্রয়োজন হলে আবার চেষ্টা করুন।</p>
        <button type="button" onClick={() => reset()} className="mt-6 rounded-xl bg-emerald-600 px-5 py-3 font-semibold text-white transition hover:bg-emerald-700">
          আবার চেষ্টা করুন
        </button>
      </section>
    </main>
  );
}
