"use client";

import { useEffect } from "react";
import { usePathname } from "next/navigation";
import { getTracker, trackPageView, startNavigation } from "@/lib/tracker";
import { useAuth } from "@/components/AuthProvider";

export default function VisitorTracker() {
  const pathname = usePathname();
  const { user } = useAuth();

  useEffect(() => {
    getTracker().init();

    const onPopState = () => startNavigation();
    const onOnline = () => getTracker().flushOfflineQueue();

    window.addEventListener("popstate", onPopState);
    window.addEventListener("online", onOnline);

    return () => {
      window.removeEventListener("popstate", onPopState);
      window.removeEventListener("online", onOnline);
    };
  }, []);

  useEffect(() => {
    getTracker().setUserId(user?.id ?? null);
  }, [user?.id]);

  useEffect(() => {
    if (!pathname) return;
    const timer = window.setTimeout(() => trackPageView(pathname), 120);
    return () => window.clearTimeout(timer);
  }, [pathname]);

  useEffect(() => {
    const onClick = (event: MouseEvent) => {
      const target = event.target;
      if (!(target instanceof Element)) return;

      const anchor = target.closest("a");
      if (!anchor) return;

      if (
        anchor.href &&
        anchor.origin === window.location.origin &&
        !anchor.target &&
        !anchor.hasAttribute("download") &&
        !event.ctrlKey &&
        !event.metaKey &&
        !event.shiftKey &&
        !event.altKey
      ) {
        startNavigation();
      }
    };

    document.addEventListener("click", onClick, { capture: true, passive: true });
    return () => document.removeEventListener("click", onClick, { capture: true });
  }, []);

  return null;
}
