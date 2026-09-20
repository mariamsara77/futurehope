"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { AuthUser, clearStoredToken, getMe, getStoredToken, login as loginApi, logout as logoutApi } from "@/lib/api";

type AuthContextValue = {
  user: AuthUser | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<AuthUser | null>;
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

  const logout = useCallback(async (all = false) => {
    await logoutApi(all);
    setUser(null);
  }, []);

  const value = useMemo(() => ({ user, loading, login, logout, refresh }), [user, loading, login, logout, refresh]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const value = useContext(AuthContext);
  if (!value) throw new Error("useAuth must be used inside AuthProvider");
  return value;
}
