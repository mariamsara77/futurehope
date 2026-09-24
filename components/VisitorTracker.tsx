"use client";

import { useEffect } from "react";
import { API_BASE_URL } from "@/lib/api";

export default function VisitorTracker() {
  useEffect(() => {
    const controller = new AbortController();

    const send = async (category: string, action: string, payload: Record<string, unknown> = {}) => {
      try {
        await fetch(`${API_BASE_URL}/api/tracking/event`, {
          method: "POST",
          headers: { "Accept": "application/json", "Content-Type": "application/json" },
          credentials: "include",
          keepalive: true,
          signal: controller.signal,
          body: JSON.stringify({ category, action, payload }),
        });
      } catch {
        // Analytics must never block or surface errors to visitors.
      }
    };

    const started = performance.now();
    const page = {
      url: window.location.href,
      path: window.location.pathname,
      title: document.title,
      referrer: document.referrer || null,
      is_pwa: window.matchMedia("(display-mode: standalone)").matches || Boolean((navigator as Navigator & { standalone?: boolean }).standalone),
    };

    void send("page", "view", { ...page, load_time_ms: Math.round(performance.now() - started) });
    void send("system", "device", {
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
      screen_res: `${window.screen.width}x${window.screen.height}`,
    });

    return () => controller.abort();
  }, []);

  return null;
}