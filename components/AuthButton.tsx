"use client";

import Link from "next/link";
import { useState } from "react";
import { useAuth } from "@/components/AuthProvider";
import AuthDialog from "@/components/AuthDialog";

export default function AuthButton() {
  const { user, loading, logout } = useAuth();
  const [open, setOpen] = useState(false);
  const [menu, setMenu] = useState(false);

  if (loading) return <div className="h-10 w-28 animate-pulse rounded-xl bg-zinc-100" />;

  if (!user) {
    return (
      <>
        <button
          onClick={() => setOpen(true)}
          className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
        >
          লগইন
        </button>
        <AuthDialog open={open} onClose={() => setOpen(false)} />
      </>
    );
  }

  const fallback = (user.name || user.email).charAt(0).toUpperCase();

  return (
    <div className="relative">
      <button
        onClick={() => setMenu((v) => !v)}
        aria-expanded={menu}
        aria-label="Account menu"
        className="flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-left hover:bg-zinc-50"
      >
        <span className="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-emerald-100 text-sm font-bold text-emerald-700">
          {user.avatar ? (
            <img src={user.avatar} alt="" className="h-full w-full object-cover" />
          ) : (
            fallback
          )}
        </span>
        <span className="hidden max-w-32 truncate text-sm font-semibold text-zinc-800 sm:block">
          {user.name || user.email}
        </span>
      </button>

      {menu && (
        <div className="absolute right-0 top-12 z-[60] w-60 rounded-2xl border border-zinc-200 bg-white p-2 shadow-xl">
          <div className="flex items-center gap-3 border-b border-zinc-100 px-3 py-3">
            <span className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-emerald-100 font-bold text-emerald-700">
              {user.avatar ? <img src={user.avatar} alt="" className="h-full w-full object-cover" /> : fallback}
            </span>
            <div className="min-w-0">
              <p className="truncate text-sm font-semibold">{user.name || "ব্যবহারকারী"}</p>
              <p className="truncate text-xs text-zinc-500">{user.email}</p>
            </div>
          </div>

          <Link
            href="/profile"
            onClick={() => setMenu(false)}
            className="mt-1 block rounded-xl px-3 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-emerald-50 hover:text-emerald-700"
          >
            আমার Profile
          </Link>

          <button
            onClick={() => { setMenu(false); void logout(); }}
            className="w-full rounded-xl px-3 py-2.5 text-left text-sm font-medium text-red-600 hover:bg-red-50"
          >
            লগআউট
          </button>

          <button
            onClick={() => { setMenu(false); void logout(true); }}
            className="w-full rounded-xl px-3 py-2.5 text-left text-sm font-medium text-zinc-600 hover:bg-zinc-50"
          >
            সব ডিভাইস থেকে লগআউট
          </button>
        </div>
      )}
    </div>
  );
}
