import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "কীভাবে কাজ করে",
  description: "Future Hope Foundation-এর কাজের প্রস্তাব, ভোট, অনুমোদন ও সদস্য profile approval-এর সহজ workflow।",
};

const steps = [
  ["01", "কাজের প্রস্তাব", "Visitor বা user একটি কাজের নাম, সংক্ষিপ্ত বিবরণ ও চাইলে ছবি জমা দেন।"],
  ["02", "সদস্যদের review", "Foundation memberরা pending প্রস্তাব দেখেন এবং প্রয়োজনীয় কাজে ভোট দেন।"],
  ["03", "১০টি ভোট", "একটি প্রস্তাবে ১০টি valid member vote হলে backend নিজে থেকে সেটি approved করে।"],
  ["04", "কাজ প্রকাশ", "Approved কাজ public work list-এ দেখা যায়। পরে running বা completed status-এ নেওয়া যায়।"],
];

export default function HowItWorksPage() {
  return <main className="mx-auto max-w-5xl px-4 py-14 sm:px-6 lg:px-8 sm:py-20">
    <div className="max-w-3xl">
      <p className="text-sm font-bold text-emerald-700">সহজ নির্দেশনা</p>
      <h1 className="mt-2 text-4xl font-bold sm:text-5xl">কীভাবে কাজ করে</h1>
      <p className="mt-4 text-lg leading-8 text-zinc-500">পুরো workflow কয়েকটি ধাপে বুঝে নিন।</p>
    </div>

    <section className="mt-10 grid gap-4 md:grid-cols-2">
      {steps.map(([n, title, text]) => <article key={n} className="rounded-3xl bg-white p-7 shadow-sm ring-1 ring-zinc-200">
        <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-sm font-bold text-emerald-700">{n}</span>
        <h2 className="mt-5 text-2xl font-bold">{title}</h2>
        <p className="mt-3 leading-7 text-zinc-500">{text}</p>
      </article>)}
    </section>

    <section className="mt-12 rounded-3xl bg-zinc-950 p-7 text-white sm:p-9">
      <p className="text-sm font-bold text-emerald-300">Member profile</p>
      <div className="mt-5 space-y-3 text-lg font-semibold">
        <p>Profile update → Admin review → Designation → Priority → Approval</p>
        <p className="text-sm font-normal leading-7 text-zinc-400">Approval-এর আগে profile public member list-এ দেখানো হবে না।</p>
      </div>
    </section>
  </main>;
}
