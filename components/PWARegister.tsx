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

    const isMobile = () => window.matchMedia("(max-width: 767px)").matches;
    const onOnline = () => setOnline(true);
    const onOffline = () => setOnline(false);
    const onBeforeInstallPrompt = (event: Event) => {
      event.preventDefault();
      const promptEvent = event as BeforeInstallPromptEvent;
      setInstallEvent(promptEvent);
      setShowInstall(isMobile());
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
        <div className="fixed bottom-3 left-3 z-[65] flex max-w-[calc(100vw-1.5rem)] items-center gap-2 rounded-full border border-zinc-200/80 bg-white/95 py-1.5 pl-1.5 pr-1.5 shadow-lg shadow-zinc-900/10 backdrop-blur-md md:hidden">
          <img
            src="/android-chrome-192x192.png"
            alt=""
            className="h-8 w-8 shrink-0 rounded-full"
          />
          <div className="min-w-0 pr-1">
            <p className="text-xs font-bold leading-4 text-zinc-900">অ্যাপ ইনস্টল করুন</p>
            <p className="text-[10px] leading-3.5 text-zinc-500">দ্রুত অ্যাক্সেস</p>
          </div>
          <button
            type="button"
            onClick={install}
            className="shrink-0 rounded-full bg-emerald-600 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-emerald-700 active:scale-95"
          >
            ইনস্টল
          </button>
          <button
            type="button"
            onClick={() => setShowInstall(false)}
            className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-base leading-none text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600"
            aria-label="ইনস্টল বার্তা বন্ধ করুন"
          >
            ×
          </button>
        </div>
      )}

      {updateReady && (
        <div className="fixed bottom-3 left-3 z-[66] flex max-w-[calc(100vw-1.5rem)] items-center gap-2 rounded-full border border-emerald-200/80 bg-white/95 py-1.5 pl-3 pr-1.5 shadow-lg shadow-zinc-900/10 backdrop-blur-md md:hidden">
          <p className="text-xs font-semibold text-zinc-800">নতুন সংস্করণ প্রস্তুত</p>
          <button
            type="button"
            onClick={update}
            className="rounded-full bg-emerald-600 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-emerald-700 active:scale-95"
          >
            আপডেট
          </button>
        </div>
      )}
    </>
  );
}
