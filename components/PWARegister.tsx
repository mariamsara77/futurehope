"use client";

import { useEffect, useRef, useState } from "react";

type BeforeInstallPromptEvent = Event & {
  prompt: () => Promise<void>;
  userChoice: Promise<{
    outcome: "accepted" | "dismissed";
    platform: string;
  }>;
};

type Platform = "ios" | "android" | "windows" | "mac" | "linux" | "other";
type Browser =
  | "facebook"
  | "instagram"
  | "safari"
  | "firefox"
  | "opera"
  | "chrome"
  | "edge"
  | "other";

function detectPlatform(): Platform {
  const ua = navigator.userAgent.toLowerCase();
  if (/iphone|ipad|ipod/.test(ua)) return "ios";
  if (/android/.test(ua)) return "android";
  if (/windows/.test(ua)) return "windows";
  if (/macintosh|mac os x/.test(ua)) return "mac";
  if (/linux/.test(ua)) return "linux";
  return "other";
}

function detectBrowser(): Browser {
  const ua = navigator.userAgent.toLowerCase();

  if (/fban|fbav|fb_iab|facebook/.test(ua)) return "facebook";
  if (/instagram/.test(ua)) return "instagram";
  if (/opr\/|opera/.test(ua)) return "opera";
  if (/edg\//.test(ua)) return "edge";
  if (/firefox|fxios/.test(ua)) return "firefox";
  if (/safari/.test(ua) && !/chrome|crios|android/.test(ua)) return "safari";
  if (/chrome|crios/.test(ua)) return "chrome";
  return "other";
}

function isStandalone(): boolean {
  const standaloneNavigator = navigator as Navigator & { standalone?: boolean };
  return (
    window.matchMedia("(display-mode: standalone)").matches ||
    standaloneNavigator.standalone === true
  );
}

export default function PWARegister() {
  const deferredPrompt = useRef<BeforeInstallPromptEvent | null>(null);

  const [online, setOnline] = useState(true);
  const [updateReady, setUpdateReady] = useState(false);
  const [showInstall, setShowInstall] = useState(false);
  const [modalOpen, setModalOpen] = useState(false);
  const [platform, setPlatform] = useState<Platform>("other");
  const [browser, setBrowser] = useState<Browser>("other");
  const [standalone, setStandalone] = useState(false);

  useEffect(() => {
    setOnline(navigator.onLine);

    const currentPlatform = detectPlatform();
    const currentBrowser = detectBrowser();
    const currentStandalone = isStandalone();

    setPlatform(currentPlatform);
    setBrowser(currentBrowser);
    setStandalone(currentStandalone);

    // The install CTA is intentionally available on every normal browser.
    if (!currentStandalone) {
      setShowInstall(true);
    }

    const onOnline = () => setOnline(true);
    const onOffline = () => setOnline(false);

    const onBeforeInstallPrompt = (event: Event) => {
      // Keep the native prompt under our explicit Install button.
      event.preventDefault();
      deferredPrompt.current = event as BeforeInstallPromptEvent;
      setShowInstall(true);
    };

    const onAppInstalled = () => {
      deferredPrompt.current = null;
      setShowInstall(false);
      setModalOpen(false);
    };

    const onInstallClick = (event: Event) => {
      const target = event.target;
      if (!(target instanceof Element)) return;

      const button = target.closest("[data-pwa-install]");
      if (!button) return;

      event.preventDefault();
      event.stopPropagation();

      const promptEvent = deferredPrompt.current;

      if (promptEvent) {
        void (async () => {
          try {
            await promptEvent.prompt();
            await promptEvent.userChoice;
          } catch {
            // The browser may close/reject the prompt; fallback remains available.
          } finally {
            deferredPrompt.current = null;
          }
        })();
        return;
      }

      // Safari, Firefox, Opera and embedded social browsers use the guided fallback.
      setModalOpen(true);
    };

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") setModalOpen(false);
    };

    window.addEventListener("online", onOnline);
    window.addEventListener("offline", onOffline);
    window.addEventListener("beforeinstallprompt", onBeforeInstallPrompt);
    window.addEventListener("appinstalled", onAppInstalled);
    document.addEventListener("click", onInstallClick, true);
    document.addEventListener("keydown", onKeyDown);

    if ("serviceWorker" in navigator) {
      navigator.serviceWorker
        .register("/sw.js", { scope: "/" })
        .then((registration) => {
          registration.addEventListener("updatefound", () => {
            const worker = registration.installing;
            if (!worker) return;

            worker.addEventListener("statechange", () => {
              if (
                worker.state === "installed" &&
                navigator.serviceWorker.controller
              ) {
                setUpdateReady(true);
              }
            });
          });
        })
        .catch(() => undefined);
    }

    return () => {
      window.removeEventListener("online", onOnline);
      window.removeEventListener("offline", onOffline);
      window.removeEventListener("beforeinstallprompt", onBeforeInstallPrompt);
      window.removeEventListener("appinstalled", onAppInstalled);
      document.removeEventListener("click", onInstallClick, true);
      document.removeEventListener("keydown", onKeyDown);
    };
  }, []);

  const update = () => {
    navigator.serviceWorker.controller?.postMessage({ type: "SKIP_WAITING" });
    window.location.reload();
  };

  const inAppBrowser = browser === "facebook" || browser === "instagram";

  // Websites cannot reliably enumerate installed browsers. On Android/iOS we
  // use OS-supported browser launch schemes; the OS handles availability.
  const openSupportedBrowser = () => {
    const currentUrl = window.location.href;
    const encodedUrl = encodeURIComponent(currentUrl);

    if (platform === "android") {
      window.location.href =
        `intent://${window.location.host}${window.location.pathname}${window.location.search}${window.location.hash}#Intent;scheme=https;package=com.android.chrome;S.browser_fallback_url=${encodedUrl};end`;
      return;
    }

    if (platform === "ios") {
      window.location.href =
        `x-safari-https://${window.location.host}${window.location.pathname}${window.location.search}${window.location.hash}`;
      return;
    }

    window.open(currentUrl, "_blank", "noopener,noreferrer");
  };

  const supportedBrowserName =
    platform === "android"
      ? "Chrome"
      : platform === "ios"
        ? "Safari"
        : "Chrome / Edge";

  const instructions = (() => {
    if (inAppBrowser) {
      return {
        title: "ব্রাউজার থেকে অ্যাপটি ইনস্টল করুন",
        description:
          "Facebook বা Instagram-এর ভেতরের ব্রাউজারে PWA ইনস্টল করা যায় না।",
        steps: [
          "উপরের মেনু (⋮) খুলুন।",
          `Open in External Browser বা ${supportedBrowserName} নির্বাচন করুন।`,
          "ব্রাউজারে Future Hope খুলে আবার ‘অ্যাপ ইনস্টল করুন’ চাপুন।",
        ],
      };
    }

    if (platform === "ios") {
      return {
        title: "iPhone / iPad-এ ইনস্টল করুন",
        description: "Safari থেকে Future Hope Home Screen-এ যোগ করুন।",
        steps: [
          "Safari-এর Share আইকনে চাপুন।",
          "Add to Home Screen নির্বাচন করুন।",
          "Add চাপুন।",
        ],
      };
    }

    if (platform === "android") {
      return {
        title: "Android-এ ইনস্টল করুন",
        description: "আপনার ব্রাউজারের menu থেকে অ্যাপটি ইনস্টল করুন।",
        steps: [
          "ব্রাউজারের menu (⋮) খুলুন।",
          "Install app বা Add to Home screen নির্বাচন করুন।",
          "নিশ্চিত করে Install/Add চাপুন।",
        ],
      };
    }

    if (browser === "safari") {
      return {
        title: "Safari থেকে ইনস্টল করুন",
        description: "Safari-এর Share menu ব্যবহার করে Home Screen-এ যোগ করুন।",
        steps: [
          "Share আইকনে ক্লিক/চাপুন।",
          "Add to Home Screen নির্বাচন করুন।",
          "Add দিয়ে নিশ্চিত করুন।",
        ],
      };
    }

    if (browser === "firefox") {
      return {
        title: "Firefox-এ অ্যাপটি ব্যবহার করুন",
        description:
          "এই Firefox পরিবেশে native PWA installation prompt পাওয়া যায়নি।",
        steps: [
          "Firefox-এর menu খুলুন।",
          "Add to Home Screen অপশন থাকলে সেটি ব্যবহার করুন।",
          "অপশনটি না থাকলে Chrome/Edge-এর মতো Chromium browser-এ পেজটি খুলে Install করুন।",
        ],
      };
    }

    if (browser === "opera") {
      return {
        title: "Opera থেকে ইনস্টল করুন",
        description: "Opera-এর browser menu থেকে installation option খুঁজুন।",
        steps: [
          "Opera menu খুলুন।",
          "Install app বা Add to Home screen নির্বাচন করুন।",
          "অপশনটি না থাকলে Chrome/Edge-এ Future Hope খুলে Install করুন।",
        ],
      };
    }

    if (platform === "windows" || platform === "mac" || platform === "linux") {
      return {
        title: "কম্পিউটারে অ্যাপটি ইনস্টল করুন",
        description:
          "এই browser-এ automatic install prompt পাওয়া যায়নি। Chromium browser-এ সবচেয়ে সহজে PWA install করা যায়।",
        steps: [
          "Chrome বা Edge-এ Future Hope খুলুন।",
          "Address bar-এর Install icon অথবা browser menu খুলুন।",
          "Install নির্বাচন করে নিশ্চিত করুন।",
        ],
      };
    }

    return {
      title: "Future Hope অ্যাপটি ইনস্টল করুন",
      description:
        "আপনার browser automatic installation prompt দেয়নি। Browser-এর installation option ব্যবহার করুন।",
      steps: [
        "Browser menu খুলুন।",
        "Install app বা Add to Home screen খুঁজুন।",
        "অপশনটি না থাকলে Chrome, Edge বা Safari-এ Future Hope খুলুন।",
      ],
    };
  })();

  return (
    <>
      {!online && (
        <div className="fixed inset-x-0 bottom-0 z-[70] border-t border-amber-200 bg-amber-50 px-4 py-3 text-center text-sm font-semibold text-amber-900 shadow-lg dark:border-amber-900/60 dark:bg-amber-950/95 dark:text-amber-100">
          আপনি অফলাইনে আছেন — সংযোগ ফিরে এলে অনলাইন ফিচারগুলো আবার কাজ করবে।
        </div>
      )}

      {!standalone && showInstall && (
        <div className="fixed inset-x-0 bottom-3 z-[65] flex justify-center px-3">
          <div className="flex w-fit max-w-full items-center gap-2 rounded-full border border-zinc-200/80 bg-white/95 py-1.5 pl-1.5 pr-1.5 shadow-xl shadow-zinc-900/10 backdrop-blur-md dark:border-zinc-700/80 dark:bg-zinc-900/95 dark:shadow-black/30">
            <img
              src="/android-chrome-192x192-safe.svg"
              alt=""
              className="h-8 w-8 shrink-0 rounded-full"
            />
            <div className="min-w-0 pr-1">
              <p className="text-xs font-bold leading-4 text-zinc-900 dark:text-zinc-100">
                অ্যাপ ইনস্টল করুন
              </p>
              <p className="text-[10px] leading-3.5 text-zinc-500 dark:text-zinc-400">
                Future Hope দ্রুত অ্যাক্সেস
              </p>
            </div>
            <button
              type="button"
              data-pwa-install
              className="shrink-0 rounded-full bg-emerald-600 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-emerald-700 active:scale-95"
            >
              ইনস্টল
            </button>
            <button
              type="button"
              onClick={() => setShowInstall(false)}
              className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-base leading-none text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
              aria-label="ইনস্টল বার্তা বন্ধ করুন"
            >
              ×
            </button>
          </div>
        </div>
      )}

      {modalOpen && (
        <div
          className="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
          role="dialog"
          aria-modal="true"
          aria-labelledby="pwa-install-title"
          onClick={(event) => {
            if (event.target === event.currentTarget) setModalOpen(false);
          }}
        >
          <div className="w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
            <div className="flex items-start justify-between gap-4">
              <div>
                <h2
                  id="pwa-install-title"
                  className="text-lg font-bold text-zinc-950 dark:text-zinc-50"
                >
                  {instructions.title}
                </h2>
                <p className="mt-1 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                  {instructions.description}
                </p>
              </div>
              <button
                type="button"
                onClick={() => setModalOpen(false)}
                className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-lg text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                aria-label="বন্ধ করুন"
              >
                ×
              </button>
            </div>

            <ol className="mt-5 space-y-3">
              {instructions.steps.map((step, index) => (
                <li
                  key={step}
                  className="flex gap-3 text-sm leading-6 text-zinc-700 dark:text-zinc-300"
                >
                  <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">
                    {index + 1}
                  </span>
                  <span>{step}</span>
                </li>
              ))}
            </ol>

            {(inAppBrowser || browser === "firefox" || browser === "opera" || browser === "other") && (
              <button
                type="button"
                onClick={openSupportedBrowser}
                className="mt-6 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700 active:scale-[0.99]"
              >
                {platform === "android"
                  ? "Chrome-এ খুলুন"
                  : platform === "ios"
                    ? "Safari-এ খুলুন"
                    : "Supported Browser-এ খুলুন"}
              </button>
            )}

            <button
              type="button"
              onClick={() => setModalOpen(false)}
              className="mt-2 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-2.5 text-sm font-bold text-zinc-800 transition hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700"
            >
              বন্ধ করুন
            </button>
          </div>
        </div>
      )}

      {updateReady && !standalone && (
        <div className="fixed inset-x-0 bottom-3 z-[66] flex justify-center px-3">
          <div className="flex w-fit max-w-full items-center gap-2 rounded-full border border-emerald-200/80 bg-white/95 py-1.5 pl-3 pr-1.5 shadow-xl shadow-zinc-900/10 backdrop-blur-md dark:border-emerald-900/60 dark:bg-zinc-900/95 dark:shadow-black/30">
            <p className="text-xs font-semibold text-zinc-800 dark:text-zinc-200">
              নতুন সংস্করণ প্রস্তুত
            </p>
            <button
              type="button"
              onClick={update}
              className="rounded-full bg-emerald-600 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-emerald-700 active:scale-95"
            >
              আপডেট
            </button>
          </div>
        </div>
      )}
    </>
  );
}
