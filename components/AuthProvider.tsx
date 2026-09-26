"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { API_BASE_URL, ApiError, clearStoredToken, getMe, getStoredToken, login as loginApi, logout as logoutApi } from "@/lib/api";
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
    } catch (error) {
      if (error instanceof ApiError && error.status === 401) {
        clearStoredToken();
        setUser(null);
      }
      // A temporary API/network failure must not log the user out or take down the site.
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
    window.location.assign(API_BASE_URL + "/api/auth/google/redirect");
    return new Promise<AuthUser>(() => undefined);
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
