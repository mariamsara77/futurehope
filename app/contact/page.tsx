import type { Metadata } from "next";
import ContactForm from "@/components/ContactForm";

export const metadata: Metadata = {
  title: "যোগাযোগ",
  description: "ফিউচার হোপ অ্যান্ড হিউম্যানিটি ফাউন্ডেশনের সঙ্গে যোগাযোগ, সহযোগিতা ও বার্তা পাঠানোর তথ্য।",
};

export default function ContactPage() {
  return (
    <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
      <div className="max-w-3xl">
        <p className="text-sm font-bold text-emerald-700">যোগাযোগ</p>
        <h1 className="mt-2 text-4xl font-bold sm:text-5xl">আমাদের সঙ্গে কথা বলুন</h1>
        <p className="mt-5 text-lg leading-8 text-zinc-500">সংগঠনের কার্যক্রম, সহযোগিতা, স্বেচ্ছাসেবী অংশগ্রহণ বা অন্যান্য বিষয়ে আমাদের বার্তা পাঠাতে নিচের ফর্মটি ব্যবহার করুন।</p>
      </div>

      <div className="mt-12 grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
        <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200 sm:p-8">
          <h2 className="text-2xl font-bold">বার্তা পাঠান</h2>
          <p className="mt-2 mb-7 text-sm leading-6 text-zinc-500">আপনার তথ্য নিরাপদভাবে backend-এ সংরক্ষণ হবে এবং নির্ধারিত যোগাযোগ ইমেইলে পাঠানো হবে।</p>
          <ContactForm />
        </section>

        <div className="space-y-6">
          <div className="rounded-3xl bg-white p-7 shadow-sm ring-1 ring-zinc-200">
            <h2 className="text-xl font-bold">ঠিকানা</h2>
            <p className="mt-4 leading-8 text-zinc-500">গ্রাম: ছোনবুনিয়া, ঘুটাবাছা, গোলবুনিয়া<br />পোস্ট: কালমেঘা<br />উপজেলা: পাথরঘাটা<br />জেলা: বরগুনা</p>
          </div>
          <div className="rounded-3xl bg-white p-7 shadow-sm ring-1 ring-zinc-200">
            <h2 className="text-xl font-bold">সরাসরি যোগাযোগ</h2>
            <a href="tel:01340792677" className="mt-5 block rounded-xl bg-emerald-50 px-4 py-3 font-semibold text-emerald-700 hover:bg-emerald-100">০১৩৪০-৭৯২৬৭৭</a>
            <p className="mt-4 text-sm leading-6 text-zinc-500">ফোনে যোগাযোগের সময় আপনার বিষয়টি সংক্ষেপে জানালে আমাদের জন্য সহায়ক হবে।</p>
          </div>
        </div>
      </div>
    </div>
  );
}