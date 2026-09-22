"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { API_BASE_URL, clearStoredToken, exchangeGoogleCode, getMe, getStoredToken, login as loginApi, logout as logoutApi, register as registerApi } from "@/lib/api";
import type { AuthUser } from "@/lib/api";

type AuthContextValue = {
  user: AuthUser | null;
  loading: boolean;
  isMember: boolean;
  isAdmin: boolean;
  login: (email: string, password: string) => Promise<AuthUser | null>;
  register: (name: string, email: string, password: string, image?: File | null) => Promise<AuthUser | null>;
  loginWithGoogle: () => void;
  completeGoogleLogin: (code: string) => Promise<AuthUser | null>;
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

  const register = useCallback(async (name: string, email: string, password: string, image?: File | null) => {
    const result = await registerApi(name, email, password, image);
    if (result.user) {
      setUser(result.user);
      return result.user;
    }
    await refresh();
    return null;
  }, [refresh]);

  const loginWithGoogle = useCallback(() => {
    if (typeof window !== "undefined") {
      window.location.assign(`${API_BASE_URL}/api/auth/google/redirect`);
    }
  }, []);

  const completeGoogleLogin = useCallback(async (code: string) => {
    const result = await exchangeGoogleCode(code);
    if (result.user) {
      setUser(result.user);
      return result.user;
    }
    await refresh();
    return null;
  }, [refresh]);

  const logout = useCallback(async (all = false) => {
    try {
      await logoutApi(all);
    } finally {
      clearStoredToken();
      setUser(null);
    }
  }, []);

  const roles = user?.roles ?? [];
  const value = useMemo(() => ({
    user,
    loading,
    isMember: roles.includes("member") || roles.includes("admin"),
    isAdmin: roles.includes("admin"),
    login,
    register,
    loginWithGoogle,
    completeGoogleLogin,
    logout,
    refresh,
  }), [user, loading, roles, login, register, loginWithGoogle, completeGoogleLogin, logout, refresh]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const value = useContext(AuthContext);
  if (!value) throw new Error("useAuth must be used inside AuthProvider");
  return value;
}
