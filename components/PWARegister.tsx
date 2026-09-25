"use client";

import { useEffect, useState } from "react";

type BeforeInstallPromptEvent = Event & {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: "accepted" | "dismissed"; platform: string }>;
};

export default function PWARegister() {
  const [installEvent, setInstallEvent] = useState<BeforeInstallPromptEvent | null>(null);
  const [showInstall, setShowInstall] = useState(false);
  const [online, setOnline] = useState(true);
  const [updateReady, setUpdateReady] = useState(false);

  useEffect(() => {
    setOnline(navigator.onLine);

    const onOnline = () => setOnline(true);
    const onOffline = () => setOnline(false);
    const onBeforeInstallPrompt = (event: Event) => {
      event.preventDefault();
      setInstallEvent(event as BeforeInstallPromptEvent);
      setShowInstall(true);
    };
    const onAppInstalled = () => {
      setInstallEvent(null);
      setShowInstall(false);
    };

    window.addEventListener("online", onOnline);
    window.addEventListener("offline", onOffline);
    window.addEventListener("beforeinstallprompt", onBeforeInstallPrompt);
    window.addEventListener("appinstalled", onAppInstalled);

    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register("/sw.js", { scope: "/" }).then((registration) => {
        registration.addEventListener("updatefound", () => {
          const worker = registration.installing;
          if (!worker) return;
          worker.addEventListener("statechange", () => {
            if (worker.state === "installed" && navigator.serviceWorker.controller) {
              setUpdateReady(true);
            }
          });
        });
      }).catch(() => undefined);
    }

    return () => {
      window.removeEventListener("online", onOnline);
      window.removeEventListener("offline", onOffline);
      window.removeEventListener("beforeinstallprompt", onBeforeInstallPrompt);
      window.removeEventListener("appinstalled", onAppInstalled);
    };
  }, []);

  const install = async () => {
    if (!installEvent) return;
    await installEvent.prompt();
    await installEvent.userChoice;
    setInstallEvent(null);
    setShowInstall(false);
  };

  const update = () => {
    navigator.serviceWorker.controller?.postMessage({ type: "SKIP_WAITING" });
    window.location.reload();
  };

  return (
    <>
      {!online && (
        <div className="fixed inset-x-0 bottom-0 z-[70] border-t border-amber-200 bg-amber-50 px-4 py-3 text-center text-sm font-semibold text-amber-900 shadow-lg">
          আপনি অফলাইনে আছেন — সংযোগ ফিরে এলে অনলাইন ফিচারগুলো আবার কাজ করবে।
        </div>
      )}
      {showInstall && installEvent && (
        <div className="fixed inset-x-4 bottom-4 z-[65] mx-auto flex max-w-xl items-center gap-3 rounded-2xl border border-zinc-200 bg-white p-3 shadow-xl sm:inset-x-auto sm:right-5 sm:ml-auto">
          <img src="/android-chrome-192x192.png" alt="" className="h-12 w-12 rounded-xl" />
          <div className="min-w-0 flex-1">
            <p className="font-bold text-zinc-950">Future Hope অ্যাপ হিসেবে ইনস্টল করুন</p>
            <p className="text-xs text-zinc-500">দ্রুত অ্যাক্সেস ও আরও ভালো app-like অভিজ্ঞতা পান।</p>
          </div>
          <button type="button" onClick={install} className="shrink-0 rounded-full bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">
            ইনস্টল
          </button>
          <button type="button" onClick={() => setShowInstall(false)} className="shrink-0 rounded-full px-2 py-2 text-zinc-500 hover:bg-zinc-100" aria-label="ইনস্টল বার্তা বন্ধ করুন">
            ×
          </button>
        </div>
      )}
      {updateReady && (
        <div className="fixed inset-x-4 bottom-4 z-[66] mx-auto flex max-w-md items-center gap-3 rounded-2xl border border-emerald-200 bg-white p-3 shadow-xl">
          <p className="flex-1 text-sm font-semibold text-zinc-800">নতুন সংস্করণ প্রস্তুত।</p>
          <button type="button" onClick={update} className="rounded-full bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">
            আপডেট
          </button>
        </div>
      )}
    </>
  );
}
