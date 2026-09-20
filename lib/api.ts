export const API_BASE_URL = (process.env.NEXT_PUBLIC_API_BASE_URL || "https://futurehope.totthobox.com").replace(/\/$/, "");

const TOKEN_KEY = "futurehope_access_token";

type ApiResponse<T = unknown> = {
  data?: T;
  user?: T;
  access_token?: string;
  token?: string;
  message?: string;
  errors?: Record<string, string[] | string>;
};

export type AuthUser = {
  id: number | string;
  name?: string | null;
  email: string;
  avatar?: string | null;
  [key: string]: unknown;
};

export class ApiError extends Error {
  status: number;
  payload: ApiResponse;

  constructor(message: string, status = 500, payload: ApiResponse = {}) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.payload = payload;
  }
}

export function getStoredToken() {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem(TOKEN_KEY);
}

export function clearStoredToken() {
  if (typeof window !== "undefined") window.localStorage.removeItem(TOKEN_KEY);
}

function storeToken(token: string) {
  if (typeof window !== "undefined") window.localStorage.setItem(TOKEN_KEY, token);
}

function errorMessage(payload: ApiResponse, fallback: string) {
  if (payload.message) return payload.message;
  if (payload.errors) {
    const first = Object.values(payload.errors).flat()[0];
    if (first) return String(first);
  }
  return fallback;
}

async function request<T>(path: string, init: RequestInit = {}, authenticated = false): Promise<T> {
  const headers = new Headers(init.headers);
  headers.set("Accept", "application/json");
  if (init.body && !headers.has("Content-Type")) headers.set("Content-Type", "application/json");

  const token = getStoredToken();
  if (authenticated && token) headers.set("Authorization", `Bearer ${token}`);

  let response: Response;
  try {
    response = await fetch(`${API_BASE_URL}/api${path}`, {
      ...init,
      headers,
      credentials: "include",
      cache: "no-store",
    });
  } catch {
    throw new ApiError("সার্ভারের সাথে সংযোগ করা যাচ্ছে না। কিছুক্ষণ পরে আবার চেষ্টা করুন।", 0);
  }

  const text = await response.text();
  let payload: ApiResponse = {};
  try {
    payload = text ? JSON.parse(text) : {};
  } catch {
    payload = {};
  }

  if (!response.ok) {
    throw new ApiError(
      errorMessage(payload, response.status === 401 ? "আপনার লগইন সেশন বৈধ নয়।" : "অনুরোধটি সম্পন্ন করা যায়নি।"),
      response.status,
      payload
    );
  }

  return payload as T;
}

export async function login(email: string, password: string) {
  const payload = await request<ApiResponse>("/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });

  const token = payload.access_token || payload.token ||
    (payload.data && typeof payload.data === "object" && "access_token" in payload.data ? String((payload.data as Record<string, unknown>).access_token) : null) ||
    (payload.data && typeof payload.data === "object" && "token" in payload.data ? String((payload.data as Record<string, unknown>).token) : null);

  if (token) storeToken(token);
  const user = (payload.user || (payload.data && typeof payload.data === "object" && "user" in payload.data ? (payload.data as Record<string, unknown>).user : null)) as AuthUser | null;
  return { token, user };
}

export async function getMe() {
  const payload = await request<ApiResponse>("/auth/me", { method: "GET" }, true);
  return (payload.user || payload.data || payload) as AuthUser;
}

export async function logout(all = false) {
  try {
    await request<ApiResponse>(all ? "/auth/logout-all" : "/auth/logout", { method: "POST" }, true);
  } finally {
    clearStoredToken();
  }
}
