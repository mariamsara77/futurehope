import type { Metadata } from "next";

export const metadata: Metadata={title:"সদস্যবৃন্দ",description:"ফিউচার হোপ অ্যান্ড হিউম্যানিটি ফাউন্ডেশনের সদস্য ও নেতৃত্ব সম্পর্কে তথ্য।"};

const roles=["সভাপতি","সাধারণ সম্পাদক","কোষাধ্যক্ষ","সাংগঠনিক দায়িত্ব","কার্যনির্বাহী সদস্য","স্বেচ্ছাসেবক"];

export default function MembersPage(){return <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8"><div className="max-w-3xl"><p className="text-sm font-bold text-emerald-700">সদস্যবৃন্দ</p><h1 className="mt-2 text-4xl font-bold sm:text-5xl">আমাদের সঙ্গে যারা কাজ করেন</h1><p className="mt-5 leading-8 text-zinc-500">সংগঠনের সদস্য, স্বেচ্ছাসেবক ও দায়িত্বশীলদের সমন্বিত অংশগ্রহণেই আমাদের কার্যক্রম পরিচালিত হয়।</p></div><div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">{roles.map((role,index)=><article key={role} className="rounded-2xl border border-zinc-200 bg-white p-6"><div className="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-700">{index+1}</div><h2 className="mt-5 text-xl font-bold">{role}</h2><p className="mt-2 text-sm leading-6 text-zinc-500">নির্ধারিত দায়িত্ব অনুযায়ী সংগঠনের কার্যক্রমে সহযোগিতা করেন।</p></article>)}</div></div>}
