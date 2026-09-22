"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { ApiError, getWorks, type Work } from "@/lib/api";

export default function ActivitiesPage() {
  const [works, setWorks] = useState<Work[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    getWorks().then(setWorks).catch(e => setError(e instanceof ApiError ? e.message : "কার্যক্রম আনা যায়নি।")).finally(() => setLoading(false));
  }, []);

  return <main className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 sm:py-20">
    <div className="max-w-3xl">
      <p className="text-sm font-bold text-emerald-700">কার্যক্রম</p>
      <h1 className="mt-2 text-4xl font-bold sm:text-5xl">অনুমোদিত কাজ ও উদ্যোগ</h1>
      <p className="mt-4 text-lg leading-8 text-zinc-500">Member vote-এর মাধ্যমে অনুমোদিত কাজগুলো এখানেই প্রকাশিত হয়। নতুন প্রস্তাব দিতে বা পুরো workflow দেখতে নিচের link ব্যবহার করুন।</p>
      <div className="mt-6 flex flex-wrap gap-3">
        <Link href="/works" className="rounded-xl bg-emerald-600 px-5 py-3 font-bold text-white hover:bg-emerald-700">কাজের প্রস্তাব দিন</Link>
        <Link href="/how-it-works" className="rounded-xl border border-zinc-200 bg-white px-5 py-3 font-bold text-zinc-700 hover:bg-zinc-50">কীভাবে কাজ করে</Link>
      </div>
    </div>

    {loading && <div className="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">{[1,2,3].map(i=><div key={i} className="h-72 animate-pulse rounded-3xl bg-zinc-200"/>)}</div>}
    {error && <div className="mt-10 rounded-2xl bg-red-50 px-4 py-3 text-red-700">{error}</div>}
    {!loading && !error && works.length === 0 && <div className="mt-12 rounded-3xl border border-dashed border-zinc-300 p-10 text-center text-zinc-500">এখনও কোনো অনুমোদিত কাজ প্রকাশিত হয়নি।</div>}

    {!loading && !error && works.length > 0 && <section className="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
      {works.map(work => <article key={work.id} className="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-zinc-200">
        {work.cover_url && <img src={work.cover_url} alt="" className="h-48 w-full object-cover" />}
        <div className="p-6">
          <div className="flex items-center justify-between gap-3"><span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{work.category || "সাধারণ"}</span><span className="text-xs font-bold text-zinc-400">{work.status}</span></div>
          <h2 className="mt-4 text-xl font-bold">{work.title}</h2>
          <p className="mt-2 line-clamp-4 leading-7 text-zinc-500">{work.description}</p>
        </div>
      </article>)}
    </section>}
  </main>;
}
