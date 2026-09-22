"use client";

import { ChangeEvent, FormEvent, useCallback, useEffect, useState } from "react";
import AuthDialog from "@/components/AuthDialog";
import { ApiError, getCategories, getPendingWorks, getWorks, submitWork, voteWork, type Category, type Work } from "@/lib/api";
import { useAuth } from "@/components/AuthProvider";

export default function WorksPage() {
  const { user, isMember } = useAuth();
  const [works, setWorks] = useState<Work[]>([]);
  const [pending, setPending] = useState<Work[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [authOpen, setAuthOpen] = useState(false);
  const [form, setForm] = useState({ title: "", description: "", category_id: "", submitted_name: "", submitted_email: "" });
  const [image, setImage] = useState<File | null>(null);
  const [busy, setBusy] = useState(false);
  const [votingId, setVotingId] = useState<string | number | null>(null);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");

  const load = useCallback(async () => {
    try {
      const [published, cats] = await Promise.all([getWorks(), getCategories()]);
      setWorks(published);
      setCategories(cats);
      if (isMember) setPending(await getPendingWorks());
      else setPending([]);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "তথ্য আনা যায়নি।");
    }
  }, [isMember]);

  useEffect(() => { void load(); }, [load]);

  function chooseImage(e: ChangeEvent<HTMLInputElement>) {
    setImage(e.target.files?.[0] ?? null);
  }

  async function submit(e: FormEvent) {
    e.preventDefault();
    setBusy(true); setError(""); setMessage("");
    try {
      const body = new FormData();
      Object.entries(form).forEach(([key, value]) => { if (value) body.append(key, value); });
      if (image) body.append("image", image);
      const result = await submitWork(body);
      setMessage(result.message);
      setForm({ title: "", description: "", category_id: "", submitted_name: "", submitted_email: "" });
      setImage(null);
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "প্রস্তাব জমা দেওয়া যায়নি।");
    } finally { setBusy(false); }
  }

  async function vote(id: string | number) {
    if (!user) { setAuthOpen(true); return; }
    setVotingId(id); setError(""); setMessage("");
    try {
      const result = await voteWork(id);
      setMessage(result.approved ? "১০টি ভোট পূর্ণ হয়েছে—কাজটি অনুমোদিত হয়েছে।" : result.message);
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "ভোট দেওয়া যায়নি।");
    } finally { setVotingId(null); }
  }

  return (
    <main className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 sm:py-16">
      <div className="max-w-3xl">
        <p className="text-sm font-bold text-emerald-700">কাজের প্রস্তাব</p>
        <h1 className="mt-2 text-4xl font-bold sm:text-5xl">একটি ভালো কাজের প্রস্তাব দিন</h1>
        <p className="mt-4 text-lg leading-8 text-zinc-500">ছোট করে বলুন—কী কাজ করা দরকার, কেন দরকার এবং চাইলে একটি ছবি দিন। Visitor বা logged-in user দুজনই প্রস্তাব দিতে পারবেন।</p>
      </div>

      <div className="mt-10 grid gap-8 lg:grid-cols-[.85fr_1.15fr]">
        <form onSubmit={submit} className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200 sm:p-8">
          <h2 className="text-2xl font-bold">প্রস্তাব জমা দিন</h2>
          <div className="mt-6 space-y-4">
            {!user && <>
              <input value={form.submitted_name} onChange={e=>setForm(f=>({...f,submitted_name:e.target.value}))} placeholder="আপনার নাম (ঐচ্ছিক)" className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none focus:border-emerald-500" />
              <input value={form.submitted_email} onChange={e=>setForm(f=>({...f,submitted_email:e.target.value}))} type="email" placeholder="ইমেইল (ঐচ্ছিক)" className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none focus:border-emerald-500" />
            </>}
            <input required value={form.title} onChange={e=>setForm(f=>({...f,title:e.target.value}))} placeholder="কাজের নাম" className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none focus:border-emerald-500" />
            <select value={form.category_id} onChange={e=>setForm(f=>({...f,category_id:e.target.value}))} className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none focus:border-emerald-500">
              <option value="">ক্যাটাগরি নির্বাচন করুন</option>
              {categories.map(c=><option key={c.id} value={c.id}>{c.name}</option>)}
            </select>
            <textarea required rows={6} value={form.description} onChange={e=>setForm(f=>({...f,description:e.target.value}))} placeholder="কাজটি কী এবং কেন দরকার—সংক্ষেপে লিখুন" className="w-full resize-y rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 leading-7 outline-none focus:border-emerald-500" />
            <label className="block rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-sm font-semibold text-zinc-600">
              ছবি (ঐচ্ছিক)
              <input type="file" accept="image/jpeg,image/png,image/webp" onChange={chooseImage} className="mt-2 block w-full text-xs" />
            </label>
            {error && <div role="alert" className="rounded-2xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{error}</div>}
            {message && <div className="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{message}</div>}
            <button disabled={busy} className="w-full rounded-2xl bg-emerald-600 px-5 py-3.5 font-bold text-white hover:bg-emerald-700 disabled:opacity-60">{busy ? "জমা হচ্ছে..." : "প্রস্তাব জমা দিন"}</button>
          </div>
        </form>

        <section>
          <div className="flex items-end justify-between gap-4">
            <div><p className="text-sm font-bold text-emerald-700">অনুমোদিত কাজ</p><h2 className="mt-1 text-2xl font-bold">জনসাধারণের জন্য</h2></div>
            <span className="rounded-full bg-zinc-100 px-3 py-1 text-xs font-bold text-zinc-500">{works.length}টি</span>
          </div>
          <div className="mt-5 space-y-4">
            {works.length === 0 ? <div className="rounded-3xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500">এখনও কোনো অনুমোদিত কাজ প্রকাশিত হয়নি।</div> : works.map(work => <WorkCard key={work.id} work={work} />)}
          </div>

          {isMember && <div className="mt-12">
            <p className="text-sm font-bold text-emerald-700">সদস্য review</p>
            <h2 className="mt-1 text-2xl font-bold">ভোটের অপেক্ষায়</h2>
            <div className="mt-5 space-y-4">
              {pending.length === 0 ? <div className="rounded-3xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500">এই মুহূর্তে pending কাজ নেই।</div> : pending.map(work => <div key={work.id} className="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-zinc-200">
                <div className="flex gap-4">
                  {work.cover_url && <img src={work.cover_url} alt="" className="h-20 w-24 rounded-2xl object-cover" />}
                  <div className="min-w-0 flex-1"><h3 className="font-bold">{work.title}</h3><p className="mt-1 line-clamp-2 text-sm leading-6 text-zinc-500">{work.description}</p></div>
                </div>
                <div className="mt-4">
                  <div className="flex justify-between text-xs font-semibold text-zinc-500"><span>{work.votes_count}/{work.required_votes} ভোট</span><span>{work.vote_progress}%</span></div>
                  <div className="mt-2 h-2 overflow-hidden rounded-full bg-zinc-100"><div className="h-full rounded-full bg-emerald-500" style={{ width: `${work.vote_progress}%` }} /></div>
                </div>
                <button disabled={votingId === work.id} onClick={() => vote(work.id)} className="mt-4 w-full rounded-xl bg-zinc-950 px-4 py-3 text-sm font-bold text-white hover:bg-zinc-800 disabled:opacity-60">{votingId === work.id ? "ভোট হচ্ছে..." : "এই কাজে ভোট দিন"}</button>
              </div>)}
            </div>
          </div>}
        </section>
      </div>
      <AuthDialog open={authOpen} onClose={()=>setAuthOpen(false)} />
    </main>
  );
}

function WorkCard({ work }: { work: Work }) {
  return <article className="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-zinc-200">
    {work.cover_url && <img src={work.cover_url} alt="" className="h-48 w-full object-cover" />}
    <div className="p-6">
      <div className="flex items-center justify-between gap-3"><span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{work.category || "সাধারণ"}</span><span className="text-xs font-semibold text-zinc-400">{work.status}</span></div>
      <h3 className="mt-4 text-xl font-bold">{work.title}</h3>
      <p className="mt-2 leading-7 text-zinc-500">{work.description}</p>
    </div>
  </article>;
}
