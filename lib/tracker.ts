const API_BASE = process.env.NEXT_PUBLIC_API_BASE_URL || "https://futurehope.totthobox.com";

type TrackPayload = Record<string, unknown>;
type DeviceNavigator = Navigator & { deviceMemory?: number; standalone?: boolean };
type QueuedTrackingItem = { url: string; data: TrackingEventPayload; ts: number };
type TrackingEventPayload = {
  category: string;
  action: string;
  js_visitor_id: string;
  session_id: string;
  payload: TrackPayload;
};

class VisitorTracker {
  private readonly visitorId: string;
  private readonly sessionId: string;
  private readonly systemPayload: TrackPayload;
  private initialized = false;
  private lastPageView = "";
  private lastPageViewTime = 0;
  private navigationStart = 0;
  private isNavigating = false;

  constructor() {
    this.visitorId = this.getId("visitor_id", "vis_", false);
    this.sessionId = this.getId("session_id", "ses_", true);
    const nav = navigator as DeviceNavigator;
    this.systemPayload = {
      ram: nav.deviceMemory ?? null,
      cpu_cores: navigator.hardwareConcurrency ?? null,
      screen_res: `${screen.width}x${screen.height}`,
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
      is_pwa: this.detectPwa(),
    };
  }

  private detectPwa(): boolean {
    if (typeof window === "undefined") return false;
    const nav = window.navigator as DeviceNavigator;
    return (
      window.matchMedia("(display-mode: standalone)").matches ||
      window.matchMedia("(display-mode: fullscreen)").matches ||
      window.matchMedia("(display-mode: minimal-ui)").matches ||
      nav.standalone === true ||
      document.referrer.includes("android-app://")
    );
  }

  private storage(key: string, value?: string, useSessionStorage = false): string | null {
    try {
      const store = useSessionStorage ? sessionStorage : localStorage;
      if (value !== undefined) { store.setItem(key, value); return value; }
      return store.getItem(key);
    } catch { return null; }
  }

  private makeId(prefix: string): string {
    const randomId = typeof crypto.randomUUID === "function"
      ? crypto.randomUUID()
      : `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
    return `${prefix}${randomId}`;
  }

  private getId(key: string, prefix: string, useSessionStorage: boolean): string {
    const existingId = this.storage(key, undefined, useSessionStorage);
    if (existingId) return existingId;
    const id = this.makeId(prefix);
    this.storage(key, id, useSessionStorage);
    return id;
  }

  private queueOffline(url: string, data: TrackingEventPayload): void {
    try {
      const raw = this.storage("tracking_queue");
      const parsed: unknown = raw ? JSON.parse(raw) : [];
      const queue: QueuedTrackingItem[] = Array.isArray(parsed)
        ? parsed.filter(this.isQueuedTrackingItem)
        : [];
      queue.push({ url, data, ts: Date.now() });
      this.storage("tracking_queue", JSON.stringify(queue.slice(-25)));
    } catch {}
  }

  private isQueuedTrackingItem(value: unknown): value is QueuedTrackingItem {
    if (!value || typeof value !== "object") return false;
    const item = value as Record<string, unknown>;
    return typeof item.url === "string" && typeof item.ts === "number" && !!item.data && typeof item.data === "object";
  }

  private send(url: string, data: TrackingEventPayload): void {
    if (typeof navigator === "undefined") return;
    if (!navigator.onLine) { this.queueOffline(url, data); return; }
    const json = JSON.stringify(data);
    const isSameOrigin = typeof window !== "undefined" && new URL(url, window.location.href).origin === window.location.origin;

    // The tracking API is normally on the separate Laravel domain.
    // Do not use sendBeacon with application/json cross-origin: browsers can
    // treat that payload as a CORS-preflighted request, and sendBeacon may
    // report it as queued without giving us a usable fallback signal.
    if (isSameOrigin && navigator.sendBeacon) {
      const blob = new Blob([json], { type: "application/json" });
      if (navigator.sendBeacon(url, blob)) return;
    }

    void fetch(url, {
      method: "POST",
      keepalive: true,
      credentials: "include",
      headers: { "Content-Type": "application/json", Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      body: json,
    }).catch(() => this.queueOffline(url, data));
  }

  private scheduleSend(fn: () => void): void {
    if (typeof window !== "undefined") window.setTimeout(fn, 100);
  }

  public startNavigation(): void {
    this.navigationStart = performance.now();
    this.isNavigating = true;
  }

  public trackEvent(category: string, action: string, payload: TrackPayload = {}): void {
    this.scheduleSend(() => this.send(`${API_BASE}/api/tracking/event`, {
      category, action, js_visitor_id: this.visitorId, session_id: this.sessionId,
      payload: { ...payload, ...this.systemPayload },
    }));
  }

  public trackPageView(path?: string, routeName?: string): void {
    if (typeof window === "undefined") return;
    const currentPath = path || window.location.pathname;
    const now = Date.now();
    if (currentPath === this.lastPageView && now - this.lastPageViewTime < 8000) return;
    this.lastPageView = currentPath;
    this.lastPageViewTime = now;

    let loadTimeMs = 0;
    if (this.isNavigating && this.navigationStart > 0) {
      loadTimeMs = Math.round(performance.now() - this.navigationStart);
    } else {
      const navEntry = performance.getEntriesByType("navigation")[0];
      if (navEntry instanceof PerformanceNavigationTiming && navEntry.loadEventEnd > 0) {
        loadTimeMs = Math.round(navEntry.loadEventEnd - navEntry.startTime);
      }
    }
    this.isNavigating = false;
    this.navigationStart = 0;
    if (loadTimeMs > 30000) loadTimeMs = 0;

    this.scheduleSend(() => {
      const params = new URLSearchParams(window.location.search);
      const trackedUrl = new URL(window.location.href);
      trackedUrl.searchParams.delete("token");
      this.send(`${API_BASE}/api/tracking/event`, {
        category: "page", action: "view", js_visitor_id: this.visitorId, session_id: this.sessionId,
        payload: {
          path: currentPath, url: trackedUrl.toString(), referrer: document.referrer || null,
          title: document.title || null, route_name: routeName || null,
          utm_source: params.get("utm_source"), utm_medium: params.get("utm_medium"),
          utm_campaign: params.get("utm_campaign"), is_pwa: this.detectPwa(),
          load_time_ms: loadTimeMs, ...this.systemPayload,
        },
      });
    });
  }

  public syncPwaStatus(isPwa: boolean): void {
    this.scheduleSend(() => {
      const payload = JSON.stringify({ is_pwa: isPwa, has_installed: isPwa });
      void fetch(`${API_BASE}/api/tracking/pwa`, {
        method: "POST",
        keepalive: true,
        credentials: "include",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: payload,
      }).catch(() => undefined);
    });
  }

  public flushOfflineQueue(): void {
    try {
      const raw = this.storage("tracking_queue");
      if (!raw) return;
      const parsed: unknown = JSON.parse(raw);
      const queue: QueuedTrackingItem[] = Array.isArray(parsed) ? parsed.filter(this.isQueuedTrackingItem) : [];
      if (!queue.length) return;
      const activities = queue.map(item => ({
        type: item.data.category, key: item.data.action, value: item.data.payload,
        timestamp: item.ts, id: item.data.js_visitor_id,
      }));
      void fetch(`${API_BASE}/api/tracking/sync`, {
        method: "POST",
        keepalive: true,
        credentials: "include",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ activities }),
      }).catch(() => undefined);
      this.storage("tracking_queue", JSON.stringify([]));
    } catch {}
  }

  public init(): void {
    if (this.initialized || typeof window === "undefined") return;
    this.initialized = true;
    if (!this.storage("hw_tracked", undefined, true)) {
      this.scheduleSend(() => { this.trackEvent("system", "hardware_info"); this.storage("hw_tracked", "1", true); });
    }
    if (this.detectPwa() && !this.storage("pwa_synced", undefined, true)) {
      this.syncPwaStatus(true);
      this.storage("pwa_synced", "1", true);
    }
    document.addEventListener("click", event => {
      const target = event.target;
      if (!(target instanceof Element)) return;
      const element = target.closest<HTMLElement>("[data-track]");
      if (!element) return;
      this.trackEvent("interaction", "click", {
        label: element.dataset.track ?? null,
        element: element.tagName.toLowerCase(),
        href: element instanceof HTMLAnchorElement ? element.href : null,
      });
    }, { passive: true });
    window.addEventListener("online", () => this.flushOfflineQueue());
  }
}

let trackerInstance: VisitorTracker | null = null;
export function getTracker(): VisitorTracker {
  if (typeof window === "undefined") {
    return { trackEvent: () => undefined, trackPageView: () => undefined, startNavigation: () => undefined,
      syncPwaStatus: () => undefined, flushOfflineQueue: () => undefined, init: () => undefined } as unknown as VisitorTracker;
  }
  trackerInstance ??= new VisitorTracker();
  return trackerInstance;
}
export const track = (category: string, action: string, payload?: TrackPayload) => getTracker().trackEvent(category, action, payload);
export const trackPageView = (path?: string, routeName?: string) => getTracker().trackPageView(path, routeName);
export const startNavigation = () => getTracker().startNavigation();
