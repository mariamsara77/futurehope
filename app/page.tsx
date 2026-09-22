import Link from "next/link";
import { getWorks } from "@/lib/api";

const aims = [
  ["মানুষের পাশে থাকা", "প্রয়োজনের সময়ে মানবিক সহায়তা ও বাস্তব উদ্যোগকে সংগঠিত করা।"],
  ["শিক্ষার সুযোগ", "শিক্ষার্থী ও তরুণদের জন্য প্রয়োজনভিত্তিক শিক্ষা ও অংশগ্রহণের সুযোগ তৈরি করা।"],
  ["স্থানীয় উন্নয়ন", "স্থানীয় মানুষের প্রস্তাবকে সদস্যদের অংশগ্রহণে বাস্তব কাজে রূপ দেওয়া।"],
  ["জবাবদিহিতা", "কাজের প্রস্তাব থেকে অনুমোদন পর্যন্ত workflow পরিষ্কার রাখা।"],
];

export default async function HomePage() {
  const works = await getWorks().catch(() => []);

  return <div>
    <section className="relative overflow-hidden bg-zinc-950">
      <div className="absolute inset-0 bg-[url('/village.jpg')] bg-cover bg-center opacity-25" />
      <div className="absolute inset-0 bg-gradient-to-r from-zinc-950 via-zinc-950/90 to-emerald-950/60" />
      <div className="relative mx-auto grid min-h-[650px] max-w-7xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-[1.1fr_.9fr] lg:px-8">
        <div className="max-w-4xl text-white">
          <span className="inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold text-emerald-100">মানুষ • মানবতা • ভবিষ্যৎ</span>
          <h1 className="mt-7 text-5xl font-bold leading-[1.15] sm:text-6xl">একটি ভালো উদ্যোগ<br /><span className="text-emerald-300">সবার অংশগ্রহণে</span></h1>
          <p className="mt-6 max-w-2xl text-lg leading-9 text-zinc-200">একটি কাজের প্রস্তাব দিন, Foundation memberরা review ও vote করবেন, ১০টি valid vote হলে কাজটি স্বয়ংক্রিয়ভাবে approved হবে।</p>
          <div className="mt-8 flex flex-wrap gap-3">
            <Link href="/works" className="rounded-2xl bg-emerald-500 px-6 py-3.5 font-bold text-white hover:bg-emerald-400">কাজের প্রস্তাব দিন</Link>
            <Link href="/how-it-works" className="rounded-2xl border border-white/20 bg-white/10 px-6 py-3.5 font-bold text-white hover:bg-white/15">কীভাবে কাজ করে</Link>
          </div>
        </div>
        <div className="rounded-3xl border border-white/10 bg-white/10 p-7 text-white shadow-2xl backdrop-blur sm:p-9">
          <p className="text-sm font-bold text-emerald-300">Workflow</p>
          <div className="mt-5 space-y-3">
            {["প্রস্তাব জমা", "Member review", "১০টি vote", "Auto approval", "কাজ প্রকাশ"].map((step, i) => <div key={step} className="flex items-center gap-4 rounded-2xl bg-white/5 p-4"><span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-sm font-bold">{i + 1}</span><span className="font-semibold">{step}</span></div>)}
          </div>
        </div>
      </div>
    </section>

    <section className="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
      <div className="max-w-3xl"><p className="text-sm font-bold text-emerald-700">আমাদের উদ্দেশ্য</p><h2 className="mt-2 text-4xl font-bold sm:text-5xl">কম কথা, পরিষ্কার কাজ</h2><p className="mt-4 text-lg leading-8 text-zinc-500">মানুষের প্রয়োজন থেকে প্রস্তাব, সদস্যদের অংশগ্রহণ থেকে অনুমোদন এবং বাস্তব কাজ—পুরো প্রক্রিয়াটি সহজ ও বোঝার মতো রাখা হয়েছে।</p></div>
      <div className="mt-10 grid gap-5 md:grid-cols-2">{aims.map(([title, text], i) => <article key={title} className="rounded-3xl border border-zinc-200 bg-white p-7 shadow-sm"><span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 font-bold text-emerald-700">০{i + 1}</span><h3 className="mt-5 text-xl font-bold">{title}</h3><p className="mt-2 leading-7 text-zinc-500">{text}</p></article>)}</div>
    </section>

    <section className="bg-zinc-100/70 py-20">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="flex items-end justify-between gap-4"><div><p className="text-sm font-bold text-emerald-700">অনুমোদিত কাজ</p><h2 className="mt-1 text-3xl font-bold sm:text-4xl">সাম্প্রতিক উদ্যোগ</h2></div><Link href="/activities" className="font-bold text-emerald-700">সব দেখুন →</Link></div>
        <div className="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
          {works.slice(0, 3).map(work => <article key={work.id} className="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-zinc-200">{work.cover_url && <img src={work.cover_url} alt="" className="h-44 w-full object-cover" />}<div className="p-6"><span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{work.category || "সাধারণ"}</span><h3 className="mt-4 text-xl font-bold">{work.title}</h3><p className="mt-2 line-clamp-3 leading-7 text-zinc-500">{work.description}</p></div></article>)}
          {works.length === 0 && <div className="md:col-span-2 lg:col-span-3 rounded-3xl border border-dashed border-zinc-300 p-10 text-center text-zinc-500">এখনও কোনো approved কাজ প্রকাশিত হয়নি। আপনিই প্রথম প্রস্তাব দিতে পারেন।</div>}
        </div>
      </div>
    </section>

    <section className="bg-emerald-950 py-16"><div className="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8"><div className="text-white"><h2 className="text-3xl font-bold">নিজের profile সম্পূর্ণ রাখুন</h2><p className="mt-2 text-emerald-100">Profile update-এর পরে admin review করে designation ও priority নির্ধারণ করবেন।</p></div><Link href="/profile" className="rounded-2xl bg-white px-6 py-3.5 text-center font-bold text-emerald-900">আমার Profile</Link></div></section>
  </div>;
}
