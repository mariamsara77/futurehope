import Link from "next/link";
import ContactForm from "@/components/ContactForm";
import WorkCard from "@/components/WorkCard";
import { getWorks } from "@/lib/api";

export const dynamic = "force-dynamic";
export const runtime = "edge";

export default async function HomePage() {
  const { works } = await getWorks({ perPage: 3 });

  return <>
    <section className="relative overflow-hidden bg-zinc-950">
      <div className="absolute inset-0 bg-[url('/village.jpg')] bg-cover bg-center opacity-35"/>
      <div className="absolute inset-0 bg-gradient-to-r from-zinc-950 via-zinc-950/85 to-emerald-950/50"/>
      <div className="relative mx-auto grid min-h-[600px] max-w-7xl items-center px-4 py-20 sm:px-6 lg:grid-cols-[1.15fr_.85fr] lg:px-8">
        <div className="max-w-3xl text-white">
          <span className="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-sm font-medium text-emerald-100">মানুষ • মানবতা • ভবিষ্যৎ</span>
          <h1 className="mt-6 text-4xl font-bold leading-tight sm:text-5xl lg:text-6xl">একতাবদ্ধ উদ্যোগে<br/><span className="text-emerald-300">সুন্দর ভবিষ্যৎ</span></h1>
          <p className="mt-6 max-w-2xl text-base leading-8 text-zinc-200 sm:text-lg">ফিউচার হোপ অ্যান্ড হিউম্যানিটি ফাউন্ডেশন মানুষের জীবনমান উন্নয়ন, শিক্ষা, মানবিক সহায়তা ও সামাজিক কল্যাণে কাজ করার একটি সম্মিলিত উদ্যোগ।</p>
          <div className="mt-8 flex flex-col gap-3 sm:flex-row"><Link href="/activities" className="rounded-xl bg-emerald-500 px-5 py-3 text-center font-semibold text-white hover:bg-emerald-400">আমাদের কার্যক্রম</Link><Link href="/contact" className="rounded-xl border border-white/20 bg-white/10 px-5 py-3 text-center font-semibold text-white hover:bg-white/15">যোগাযোগ করুন</Link></div>
        </div>
        <div className="hidden lg:block"><div className="ml-auto max-w-sm rounded-3xl border border-white/10 bg-white/10 p-7 backdrop-blur"><p className="text-sm font-semibold text-emerald-200">আমাদের অঙ্গীকার</p><p className="mt-4 text-2xl font-bold leading-10 text-white">মানুষের বাস্তব প্রয়োজনকে কেন্দ্র করে কার্যকর উদ্যোগ তৈরি করা।</p></div></div>
      </div>
    </section>

    <section className="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
      <div className="max-w-2xl"><p className="text-sm font-bold text-emerald-700">সাম্প্রতিক কার্যক্রম</p><h2 className="mt-2 text-3xl font-bold sm:text-4xl">Backend থেকে প্রকাশিত উদ্যোগ</h2><p className="mt-4 leading-8 text-zinc-500">প্রকাশিত কাজগুলো এখানে স্বয়ংক্রিয়ভাবে দেখানো হয়। নতুন কাজ প্রকাশ করলেই আলাদা করে frontend code পরিবর্তনের প্রয়োজন নেই।</p></div>
      {works.length > 0 ? <div className="mt-10 grid gap-5 lg:grid-cols-3">{works.map(work => <WorkCard key={work.id} work={work} />)}</div> : <div className="mt-10 rounded-3xl border border-zinc-200 bg-white px-6 py-14 text-center shadow-sm"><h3 className="text-xl font-bold">এখনও কোনো প্রকাশিত কার্যক্রম নেই</h3><p className="mt-2 text-zinc-500">Backend থেকে কোনো কাজ প্রকাশিত হলে এই অংশটি স্বয়ংক্রিয়ভাবে পূরণ হবে।</p></div>}
    </section>

    <section className="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
      <div className="grid gap-8 lg:grid-cols-[.8fr_1.2fr] lg:items-start">
        <div><p className="text-sm font-bold text-emerald-700">যোগাযোগ</p><h2 className="mt-2 text-3xl font-bold sm:text-4xl">আপনার কথা আমাদের জানান</h2><p className="mt-4 leading-8 text-zinc-500">কোনো প্রশ্ন, সহযোগিতার প্রস্তাব বা সামাজিক উদ্যোগ নিয়ে কথা বলতে সরাসরি বার্তা পাঠাতে পারেন।</p></div>
        <div className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200 sm:p-8"><ContactForm compact /></div>
      </div>
    </section>

    <section className="bg-emerald-950"><div className="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-16 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8"><div className="text-white"><h2 className="text-3xl font-bold">আপনিও এই উদ্যোগের অংশ হতে পারেন</h2><p className="mt-2 text-emerald-100">আমাদের সম্পর্কে আরও জানুন অথবা সরাসরি যোগাযোগ করুন।</p></div><Link href="/about" className="rounded-xl bg-white px-5 py-3 text-center font-semibold text-emerald-900 hover:bg-emerald-50">আমাদের সম্পর্কে</Link></div></section>
  </>;
}