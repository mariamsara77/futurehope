"use client";

export default function GlobalError() {
  return (
    <html lang="bn">
      <body className="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
        <main className="flex min-h-screen items-center justify-center px-4 py-16">
          <section className="w-full max-w-2xl rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-zinc-200 sm:p-10">
            <h1 className="text-2xl font-bold">সাইটটি সাময়িকভাবে এই অংশটি লোড করতে পারছে না</h1>
            <p className="mt-3 leading-7 text-zinc-500">অনুগ্রহ করে কিছুক্ষণ পরে আবার চেষ্টা করুন। API বা অনলাইন ডেটা সাময়িকভাবে unavailable হলেও সাইটের static অংশ সচল রাখার চেষ্টা করা হয়েছে।</p>
            <button type="button" onClick={() => window.location.reload()} className="mt-6 rounded-xl bg-emerald-600 px-5 py-3 font-semibold text-white transition hover:bg-emerald-700">
              আবার লোড করুন
            </button>
          </section>
        </main>
      </body>
    </html>
  );
}
