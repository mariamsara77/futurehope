import type { Metadata } from "next";
import Link from "next/link";

export const metadata: Metadata = {
  title: "কীভাবে কাজ করে",
  description: "Future Hope Foundation-এর account, Google login, profile, work proposal, member vote এবং approval workflow-এর সহজ নির্দেশনা।",
};

const steps = [
  ["01", "Account তৈরি করুন", "Login বাটন থেকে ইমেইল-পাসওয়ার্ড দিয়ে registration করুন। চাইলে registration-এর সময় profile image দিতে পারেন। Google account থাকলে “Google দিয়ে লগইন” চাপুন।"],
  ["02", "Profile image ঠিক করুন", "Google থেকে profile photo পাওয়া গেলে সেটি automatically account-এর image হয়। পরে Profile page থেকে নিজের ছবি upload করলে সেটিই primary image হয়ে যাবে।"],
  ["03", "Profile পূরণ করুন", "Profile page-এ phone, education, address, blood group ও নিজের পরিচিতি লিখে save করুন। Profile update হলে status pending হয় এবং admin review করেন।"],
  ["04", "কাজের প্রস্তাব দিন", "Visitor বা logged-in user কাজের নাম, description, category এবং optional image দিয়ে proposal জমা দিতে পারেন।"],
  ["05", "Member vote", "Foundation-এর অনুমোদিত memberরা pending proposal review করে valid vote দেন। একই member একটি proposal-এ একবারই vote দিতে পারেন।"],
  ["06", "১০টি vote হলে approval", "একটি proposal-এ ১০টি valid member vote পূর্ণ হলে backend সেটিকে approved/published করে public Activities ও Works section-এ দেখায়।"],
];

const faq = [
  ["Google login করলে কি নতুন account হবে?", "প্রথমবার হলে নতুন account তৈরি হবে। একই email-এর existing account থাকলে Google identity সেই account-এর সঙ্গে link হবে।"],
  ["Google-এর ছবি না থাকলে?", "Account-এর fallback avatar automatically ব্যবহার হবে। পরে Profile page থেকে নিজের image upload করা যাবে।"],
  ["Profile update করলে সঙ্গে সঙ্গে member list-এ দেখা যাবে?", "না। Profile update-এর পর status pending হয়। Admin approve করার পর designation ও priority অনুযায়ী public member list-এ দেখা যায়।"],
  ["কাজের proposal দিতে login লাগবে?", "না। Visitor-ও proposal দিতে পারেন। তবে member voting-এর জন্য approved member account প্রয়োজন।"],
];

export default function HowItWorksPage() {
  return (
    <main className="mx-auto max-w-5xl px-4 py-14 sm:px-6 lg:px-8 sm:py-20">
      <header className="max-w-3xl">
        <p className="text-sm font-bold text-emerald-700">সহজ গাইড</p>
        <h1 className="mt-2 text-4xl font-bold sm:text-5xl">Future Hope কীভাবে কাজ করে?</h1>
        <p className="mt-4 text-lg leading-8 text-zinc-500">Account থেকে profile, proposal থেকে member vote—এক নজরে পুরো process বুঝে নিন।</p>
      </header>

      <section className="mt-10 grid gap-4 md:grid-cols-2">
        {steps.map(([number, title, description]) => (
          <article key={number} className="rounded-3xl bg-white p-7 shadow-sm ring-1 ring-zinc-200">
            <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-sm font-bold text-emerald-700">{number}</span>
            <h2 className="mt-5 text-2xl font-bold">{title}</h2>
            <p className="mt-3 leading-7 text-zinc-500">{description}</p>
          </article>
        ))}
      </section>

      <section className="mt-12 rounded-3xl border border-emerald-100 bg-emerald-50 p-7 sm:p-9">
        <p className="text-sm font-bold text-emerald-800">পুরো flow</p>
        <p className="mt-3 text-lg font-bold leading-8 text-zinc-900">Registration / Google Login → Profile → Admin Approval → Work Proposal → Member Vote → 10 Valid Votes → Public Approval</p>
      </section>

      <section className="mt-8 rounded-3xl bg-zinc-950 p-7 text-white sm:p-9">
        <h2 className="text-2xl font-bold">Profile approval কীভাবে হয়?</h2>
        <div className="mt-5 grid gap-3 sm:grid-cols-4">
          {["Profile update", "Admin review", "Designation + Priority", "Approved member"].map((item, index) => (
            <div key={item} className="rounded-2xl bg-white/5 p-4">
              <span className="text-sm font-bold text-emerald-300">০{index + 1}</span>
              <p className="mt-2 font-semibold">{item}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="mt-10">
        <h2 className="text-2xl font-bold">সাধারণ প্রশ্ন</h2>
        <div className="mt-5 space-y-3">
          {faq.map(([question, answer]) => (
            <details key={question} className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-zinc-200">
              <summary className="cursor-pointer font-bold">{question}</summary>
              <p className="mt-3 leading-7 text-zinc-500">{answer}</p>
            </details>
          ))}
        </div>
      </section>

      <section className="mt-10 flex flex-wrap gap-3">
        <Link href="/works" className="rounded-xl bg-emerald-600 px-5 py-3 font-bold text-white hover:bg-emerald-700">কাজের প্রস্তাব দিন</Link>
        <Link href="/profile" className="rounded-xl border border-zinc-200 bg-white px-5 py-3 font-bold text-zinc-700 hover:bg-zinc-50">আমার Profile</Link>
      </section>
    </main>
  );
}