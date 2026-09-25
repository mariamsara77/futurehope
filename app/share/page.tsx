"use client";

import { useSearchParams } from "next/navigation";
import { Suspense } from "react";

function ShareContent() {
  const params = useSearchParams();
  const title = params.get("title") || "শেয়ার করা কনটেন্ট";
  const text = params.get("text") || "";
  const url = params.get("url") || "";

  const copy = async () => {
    const value = [title, text, url].filter(Boolean).join("\n");
    await navigator.clipboard?.writeText(value);
  };

  return (
    <main className="flex min-h-[calc(100vh-8rem)] items-center justify-center px-5 py-12">
      <section className="w-full max-w-2xl rounded-3xl border border-zinc-200 bg-white p-7 shadow-sm sm:p-10">
        <p className="text-sm font-semibold text-emerald-600">Future Hope</p>
        <h1 className="mt-2 text-2xl font-bold text-zinc-950">শেয়ার করা কনটেন্ট</h1>
        <div className="mt-6 rounded-2xl bg-zinc-50 p-5">
          <h2 className="font-bold text-zinc-900">{title}</h2>
          {text && <p className="mt-2 whitespace-pre-wrap text-sm leading-7 text-zinc-600">{text}</p>}
          {url && (
            <a href={url} target="_blank" rel="noreferrer" className="mt-3 block break-all text-sm font-medium text-emerald-700 underline">
              {url}
            </a>
          )}
        </div>
        <div className="mt-6 flex flex-wrap gap-3">
          <button type="button" onClick={copy} className="rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">
            কপি করুন
          </button>
          <a href="/" className="rounded-full border border-zinc-200 px-5 py-2.5 text-sm font-bold text-zinc-700 hover:bg-zinc-50">
            হোমে যান
          </a>
        </div>
      </section>
    </main>
  );
}

export default function SharePage() {
  return <Suspense fallback={<main className="min-h-[calc(100vh-8rem)]" />}>
    <ShareContent />
  </Suspense>;
}
