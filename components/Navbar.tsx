"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";
import AuthButton from "@/components/AuthButton";

const links = [
  { href:"/", label:"হোম" },
  { href:"/about", label:"আমাদের সম্পর্কে" },
  { href:"/activities", label:"কার্যক্রম" },
  { href:"/members", label:"সদস্যবৃন্দ" },
  { href:"/contact", label:"যোগাযোগ" },
];

export default function Navbar() {
  const pathname = usePathname();
  const [open,setOpen] = useState(false);
  return <header className="sticky top-0 z-50 border-b border-zinc-200/80 bg-white/95 backdrop-blur">
    <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
      <Link href="/" className="flex min-w-0 items-center gap-3" onClick={()=>setOpen(false)}>
        <span className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-emerald-50"><img src="/500.png" alt="" className="h-9 w-9 object-contain" /></span>
        <span className="min-w-0"><span className="block truncate text-sm font-bold text-zinc-950 sm:text-base">ফিউচার হোপ</span><span className="hidden text-xs text-zinc-500 sm:block">অ্যান্ড হিউম্যানিটি ফাউন্ডেশন</span></span>
      </Link>
      <nav className="hidden items-center gap-1 lg:flex" aria-label="প্রধান নেভিগেশন">{links.map(link=>{const active=link.href==="/" ? pathname==="/" : pathname.startsWith(link.href); return <Link key={link.href} href={link.href} className={`rounded-xl px-3.5 py-2 text-sm font-semibold transition ${active?"bg-emerald-50 text-emerald-700":"text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950"}`}>{link.label}</Link>})}</nav>
      <div className="hidden lg:flex"><AuthButton /></div>
      <button aria-label={open?"মেনু বন্ধ করুন":"মেনু খুলুন"} aria-expanded={open} onClick={()=>setOpen(v=>!v)} className="rounded-xl border border-zinc-200 p-2.5 text-zinc-700 lg:hidden">{open?"×":"☰"}</button>
    </div>
    {open && <div className="border-t border-zinc-100 bg-white px-4 pb-4 pt-2 lg:hidden"><nav className="space-y-1">{links.map(link=><Link key={link.href} href={link.href} onClick={()=>setOpen(false)} className="block rounded-xl px-3 py-3 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">{link.label}</Link>)}</nav><div className="mt-3 border-t border-zinc-100 pt-3"><AuthButton /></div></div>}
  </header>;
}
