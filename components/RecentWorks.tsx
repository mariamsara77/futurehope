"use client";

import { useEffect, useState } from "react";
import WorkCard from "@/components/WorkCard";
import { getWorks, type Work } from "@/lib/api";

export default function RecentWorks() {
  const [works, setWorks] = useState<Work[]>([]);

  useEffect(() => {
    let active = true;
    void getWorks({ perPage: 3 }).then((response) => {
      if (active) setWorks(response.works ?? []);
    }).catch(() => {
      if (active) setWorks([]);
    });
    return () => { active = false; };
  }, []);

  return (
    <section className="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
      <div className="max-w-2xl"><p className="text-sm font-bold text-emerald-700">সাম্প্রতিক কার্যক্রম</p><h2 className="mt-2 text-3xl font-bold sm:text-4xl">Backend থেকে প্রকাশিত উদ্যোগ</h2><p className="mt-4 leading-8 text-zinc-500">প্রকাশিত কাজগুলো এখানে স্বয়ংক্রিয়ভাবে দেখানো হয়। নতুন কাজ প্রকাশ করলেই আলাদা করে frontend code পরিবর্তনের প্রয়োজন নেই।</p></div>
      {works.length > 0 ? <div className="mt-10 grid gap-5 lg:grid-cols-3">{works.map(work => <WorkCard key={work.id} work={work} />)}</div> : <div className="mt-10 rounded-3xl border border-zinc-200 bg-white px-6 py-14 text-center shadow-sm"><h3 className="text-xl font-bold">এখনও কোনো প্রকাশিত কার্যক্রম নেই</h3><p className="mt-2 text-zinc-500">Backend থেকে কোনো কাজ প্রকাশিত হলে এই অংশটি স্বয়ংক্রিয়ভাবে পূরণ হবে।</p></div>}
    </section>
  );
}
