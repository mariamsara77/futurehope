"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { API_BASE_URL, clearStoredToken, getMe, getStoredToken, login as loginApi, logout as logoutApi } from "@/lib/api";
import type { AuthUser } from "@/lib/api";

type AuthContextValue = {
  user: AuthUser | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<AuthUser | null>;
  isMember: boolean;
  isAdmin: boolean;
  googleLogin: () => Promise<AuthUser>;
  logout: (all?: boolean) => Promise<void>;
  refresh: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export default function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    const token = getStoredToken();
    if (!token) {
      setUser(null);
      return;
    }
    try {
      setUser(await getMe());
    } catch {
      clearStoredToken();
      setUser(null);
    }
  }, []);

  useEffect(() => {
    // Auth hydration intentionally runs once on mount; the callback updates external auth state.
    // eslint-disable-next-line react-hooks/set-state-in-effect
    refresh().finally(() => setLoading(false));
  }, [refresh]);

  const login = useCallback(async (email: string, password: string) => {
    const result = await loginApi(email, password);
    if (result.user) {
      setUser(result.user);
      return result.user;
    }
    await refresh();
    return null;
  }, [refresh]);

  const googleLogin = useCallback(() => {
    return new Promise<AuthUser>((resolve, reject) => {
      const width = 520;
      const height = 680;
      const left = Math.max(0, Math.round(window.screenX + (window.outerWidth - width) / 2));
      const top = Math.max(0, Math.round(window.screenY + (window.outerHeight - height) / 2));
      const popup = window.open(
        API_BASE_URL + "/api/auth/google/redirect",
        "futurehope-google-login",
        "popup=yes,width=" + width + ",height=" + height + ",left=" + left + ",top=" + top + ",resizable=yes,scrollbars=yes",
      );

      if (!popup) {
        reject(new Error("Google login popup blocked. Please allow popups for this site."));
        return;
      }

      let settled = false;
      const timer = window.setInterval(() => {
        if (popup.closed) {
          finish(() => reject(new Error("Google login window was closed before login completed.")));
        }
      }, 500);
      const timeout = window.setTimeout(() => {
        finish(() => reject(new Error("Google login timed out. Please try again.")));
        if (!popup.closed) popup.close();
      }, 120000);

      const cleanup = () => {
        window.removeEventListener("message", onMessage);
        window.clearInterval(timer);
        window.clearTimeout(timeout);
      };

      const finish = (callback: () => void) => {
        if (settled) return;
        settled = true;
        cleanup();
        callback();
      };

      const onMessage = (event: MessageEvent) => {
        if (event.origin !== window.location.origin) return;
        if (event.data?.type === "futurehope-google-auth" && event.data.user) {
          setUser(event.data.user as AuthUser);
          finish(() => resolve(event.data.user as AuthUser));
        } else if (event.data?.type === "futurehope-google-auth-error") {
          finish(() => reject(new Error(String(event.data.message || "Google login failed."))));
        }
      };

      window.addEventListener("message", onMessage);


    });
  }, []);

  const logout = useCallback(async (all = false) => {
    await logoutApi(all);
    setUser(null);
  }, []);

  const roles = user?.roles ?? [];
  const isMember = user?.is_member === true;
  const isAdmin = roles.includes("admin");
  const value = useMemo(() => ({ user, loading, login, googleLogin, logout, refresh, isMember, isAdmin }), [user, loading, login, googleLogin, logout, refresh, isMember, isAdmin]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const value = useContext(AuthContext);
  if (!value) throw new Error("useAuth must be used inside AuthProvider");
  return value;
}
