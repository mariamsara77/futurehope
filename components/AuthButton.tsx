"use client";

import Link from "next/link";
import { useState } from "react";
import { useAuth } from "@/components/AuthProvider";
import AuthDialog from "@/components/AuthDialog";

export default function AuthButton() {
  const { user, loading, logout } = useAuth();
  const [open, setOpen] = useState(false);
  const [menu, setMenu] = useState(false);

  if (loading) return <div className="h-10 w-24 animate-pulse rounded-xl bg-zinc-100" />;

  if (!user) return <><button onClick={() => setOpen(true)} className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">লগইন</button><AuthDialog open={open} onClose={() => setOpen(false)} /></>;

  return (
    <div className="relative">
      <button onClick={() => setMenu((v) => !v)} className="flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-left hover:bg-zinc-50">
        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-sm font-bold text-emerald-700">{(user.name || user.email).charAt(0).toUpperCase()}</span>
        <span className="hidden max-w-28 truncate text-sm font-semibold text-zinc-800 sm:block">{user.name || user.email}</span>
      </button>
      {menu && <div className="absolute right-0 top-12 z-50 w-52 rounded-2xl border border-zinc-200 bg-white p-2 shadow-xl">
        <div className="border-b border-zinc-100 px-3 py-2">
          <p className="truncate text-sm font-semibold text-zinc-900">{user.name || "ব্যবহারকারী"}</p>
          <p className="truncate text-xs text-zinc-500">{user.email}</p>
        </div>
        <Link href="/profile" onClick={() => setMenu(false)} className="mt-1 block rounded-xl px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-emerald-50">আমার Profile</Link>
        <Link href="/works" onClick={() => setMenu(false)} className="block rounded-xl px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-emerald-50">কাজের প্রস্তাব</Link>
        <button onClick={() => { setMenu(false); void logout(); }} className="mt-1 w-full rounded-xl px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50">লগআউট</button>
        <button onClick={() => { setMenu(false); void logout(true); }} className="w-full rounded-xl px-3 py-2 text-left text-sm font-medium text-zinc-600 hover:bg-zinc-50">সব ডিভাইস থেকে লগআউট</button>
      </div>}
    </div>
  );
}
