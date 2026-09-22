"use client";

import { useEffect, useState } from "react";
import { ApiError, getMembers, type Member } from "@/lib/api";

export default function MembersPage() {
  const [members, setMembers] = useState<Member[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    getMembers()
      .then(setMembers)
      .catch(e => setError(e instanceof ApiError ? e.message : "সদস্যদের তথ্য আনা যায়নি।"))
      .finally(() => setLoading(false));
  }, []);

  const primary = members.filter(m => m.priority === 1);
  const secondary = members.filter(m => m.priority >= 2 && m.priority <= 4);
  const others = members.filter(m => m.priority > 4);

  return <main className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 sm:py-20">
    <div className="max-w-3xl">
      <p className="text-sm font-bold text-emerald-700">সদস্যবৃন্দ</p>
      <h1 className="mt-2 text-4xl font-bold sm:text-5xl">ফাউন্ডেশনের দায়িত্বশীল সদস্যরা</h1>
      <p className="mt-4 text-lg leading-8 text-zinc-500">Designation ও priority backend থেকে নিয়ন্ত্রিত। তাই নতুন সদস্য যোগ বা দায়িত্ব বদলালেও layout নিজে থেকে বদলে যাবে।</p>
    </div>

    {loading && <div className="mt-10 grid gap-5 md:grid-cols-3"><div className="h-72 animate-pulse rounded-3xl bg-zinc-200"/><div className="h-56 animate-pulse rounded-3xl bg-zinc-200"/><div className="h-56 animate-pulse rounded-3xl bg-zinc-200"/></div>}
    {error && <div className="mt-10 rounded-2xl bg-red-50 px-4 py-3 text-red-700">{error}</div>}

    {!loading && !error && members.length === 0 && <div className="mt-10 rounded-3xl border border-dashed border-zinc-300 p-10 text-center text-zinc-500">এখনও কোনো approved member প্রকাশিত হয়নি।</div>}

    {!loading && !error && members.length > 0 && <div className="mt-12 space-y-8">
      {primary.length > 0 && <section className="grid justify-center">{primary.map(m => <MemberCard key={m.id} member={m} featured />)}</section>}
      {secondary.length > 0 && <section className="mx-auto grid max-w-5xl gap-4 sm:grid-cols-3">{secondary.map(m => <MemberCard key={m.id} member={m} compact />)}</section>}
      {others.length > 0 && <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">{others.map(m => <MemberCard key={m.id} member={m} />)}</section>}
    </div>}
  </main>;
}

function MemberCard({ member, featured = false, compact = false }: { member: Member; featured?: boolean; compact?: boolean }) {
  return <article className={`rounded-3xl bg-white text-center shadow-sm ring-1 ring-zinc-200 ${featured ? "mx-auto w-full max-w-sm p-8" : compact ? "p-5" : "p-6"}`}>
    <div className={`mx-auto overflow-hidden rounded-full bg-emerald-50 ${featured ? "h-28 w-28" : compact ? "h-20 w-20" : "h-24 w-24"}`}>
      {member.avatar_url ? <img src={member.avatar_url} alt={member.name} className="h-full w-full object-cover" /> : <div className="flex h-full w-full items-center justify-center text-2xl font-bold text-emerald-700">{member.name.charAt(0)}</div>}
    </div>
    <p className="mt-5 text-sm font-bold text-emerald-700">{member.designation || "সদস্য"}</p>
    <h2 className={`mt-1 font-bold ${featured ? "text-2xl" : "text-lg"}`}>{member.name}</h2>
    {member.bio && <p className="mt-2 line-clamp-3 text-sm leading-6 text-zinc-500">{member.bio}</p>}
  </article>;
}
